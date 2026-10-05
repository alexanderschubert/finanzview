<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\CsvImportService;
use App\Services\PayeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kontoauszug-Import (CSV) in drei Schritten:
 * Datei hochladen → Vorschau/Spalten prüfen → Buchungen anlegen.
 *
 * Die hochgeladene Datei liegt bis zum Abschluss im privaten
 * Speicher (storage/app/private/imports) und wird danach gelöscht.
 */
class TransactionImportController extends Controller
{
    public function __construct(
        private CsvImportService $csv
    ) {
    }


    /**
     * Schritt 1: Datei und Konto wählen
     */
    public function create()
    {
        $accounts = $this->accounts();

        return view('transactions.import.create', compact('accounts'));
    }


    /**
     * Datei speichern und zur Vorschau weiterleiten
     */
    public function upload(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:csv,txt,tsv'],
            'account_id' => ['required', 'integer'],
        ], [
            'file.extensions' => 'Bitte eine CSV-Datei auswählen.',
            'file.max' => 'Die Datei darf höchstens 5 MB groß sein.',
        ]);

        $account = $this->accounts()->firstWhere('id', (int) $validated['account_id']);

        if (! $account) {
            return back()->withErrors(['account_id' => 'Das ausgewählte Konto ist ungültig.'])->withInput();
        }

        $analysis = $this->csv->analyze((string) file_get_contents($request->file('file')->getRealPath()));

        if ($analysis['rows'] === []) {
            return back()->withErrors(['file' => 'In der Datei wurden keine Buchungen gefunden.'])->withInput();
        }

        $this->cleanupOldFiles($user->id);

        $token = Str::random(32);
        Storage::disk('local')->putFileAs($this->directory($user->id), $request->file('file'), $token . '.csv');

        return redirect()->route('transactions.import.preview', [
            'token' => $token,
            'account_id' => $account->id,
        ]);
    }


    /**
     * Schritt 2: Vorschau mit Spaltenzuordnung
     */
    public function preview(Request $request, string $token)
    {
        $user = Auth::user();
        $content = $this->readFile($user->id, $token);

        $accounts = $this->accounts();
        $account = $accounts->firstWhere('id', (int) $request->query('account_id'));

        if ($content === null || ! $account) {
            return redirect()
                ->route('transactions.import.create')
                ->with('error', 'Die Import-Datei ist nicht mehr vorhanden. Bitte erneut hochladen.');
        }

        $analysis = $this->csv->analyze($content, (array) $request->query('map', []));

        // Ohne Angabe: Vorschlag aus der Kopfzeile (z. B. American Express).
        $invert = $request->has('invert') ? $request->boolean('invert') : $analysis['invert_suggested'];

        $items = $this->csv->parse($analysis, $user->id, $account->id, $invert);

        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('type', ['income', 'expense', 'both'])
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'type', 'icon'])
            ->inTreeOrder();

        $mappingComplete = $analysis['mapping']['date'] !== null
            && ($analysis['mapping']['amount'] !== null || $analysis['mapping']['debit'] !== null || $analysis['mapping']['credit'] !== null);

        return view('transactions.import.preview', [
            'token' => $token,
            'account' => $account,
            'accounts' => $accounts,
            'headers' => $analysis['headers'],
            'mapping' => $analysis['mapping'],
            'mappingComplete' => $mappingComplete,
            'items' => $items,
            'categories' => $categories,
            'truncated' => count($analysis['rows']) > CsvImportService::MAX_ROWS,
            'fieldLabels' => CsvImportService::FIELD_LABELS,
            'invert' => $invert,
            'invertSuggested' => $analysis['invert_suggested'],
        ]);
    }


    /**
     * Schritt 3: Ausgewählte Zeilen als Buchungen anlegen
     *
     * Beträge, Daten und Texte werden erneut aus der Datei gelesen;
     * vom Formular kommen nur Auswahl, Kategorie und Zuordnung.
     */
    public function store(Request $request, string $token)
    {
        $user = Auth::user();
        $content = $this->readFile($user->id, $token);

        $account = $this->accounts()->firstWhere('id', (int) $request->input('account_id'));

        if ($content === null || ! $account) {
            return redirect()
                ->route('transactions.import.create')
                ->with('error', 'Die Import-Datei ist nicht mehr vorhanden. Bitte erneut hochladen.');
        }

        /*
         * Auswahl und Kategorien kommen gebündelt in je einem Feld
         * („3,4,7“ bzw. „3:12;4:0“), damit große Dateien nicht an
         * max_input_vars scheitern. Ohne JavaScript: import[] / category[].
         */
        $selected = collect($request->filled('selected')
                ? explode(',', (string) $request->input('selected'))
                : (array) $request->input('import', []))
            ->filter(fn ($value) => ctype_digit((string) $value))
            ->map(fn ($value) => (int) $value)
            ->flip();

        $chosenCategories = (array) $request->input('category', []);

        foreach (array_filter(explode(';', (string) $request->input('categories'))) as $pair) {
            [$index, $categoryId] = array_pad(explode(':', $pair, 2), 2, '');

            if (ctype_digit($index) && ctype_digit($categoryId)) {
                $chosenCategories[(int) $index] = (int) $categoryId;
            }
        }

        $validCategories = Category::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('type', 'id');

        $analysis = $this->csv->analyze($content, (array) $request->input('map', []));
        $items = $this->csv->parse($analysis, $user->id, $account->id, $request->boolean('invert'))
            ->filter(fn ($item) => $item['error'] === null
                && $item['duplicate'] !== 'imported'
                && $selected->has($item['index']));

        if ($items->isEmpty()) {
            return back()->with('error', 'Es wurden keine Buchungen ausgewählt.');
        }

        $payees = app(PayeeService::class);

        DB::transaction(function () use ($items, $user, $account, $chosenCategories, $validCategories, $payees) {
            foreach ($items as $item) {
                $categoryId = array_key_exists($item['index'], $chosenCategories)
                    ? (int) $chosenCategories[$item['index']]
                    : $item['category_id'];

                // Kategorie muss dem Benutzer gehören und zur Buchungsart passen.
                if (! $categoryId || ! in_array($validCategories[$categoryId] ?? null, [$item['type'], 'both'], true)) {
                    $categoryId = null;
                }

                // Empfänger zuordnen; der Händlertext der Bank bleibt unverändert.
                $payeeId = $item['payee_id'] ?? $payees->ensure($user->id, $item['merchant'])?->id;

                Transaction::create([
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                    'payee_id' => $payeeId,
                    'category_id' => $categoryId,
                    'type' => $item['type'],
                    'amount' => $item['amount'],
                    'transaction_date' => $item['date']->toDateString(),
                    'description' => $item['description'],
                    'merchant' => $item['merchant'] !== '' ? $item['merchant'] : null,
                    'external_id' => $item['external_id'],
                    'is_pending' => $item['pending'],
                ]);
            }
        });

        Storage::disk('local')->delete($this->path($user->id, $token));

        $count = $items->count();

        return redirect()
            ->route('transactions.index', ['account_id' => $account->id])
            ->with('success', $count === 1
                ? '1 Buchung wurde importiert.'
                : "{$count} Buchungen wurden importiert.");
    }


    /**
     * Import abbrechen und Datei löschen
     */
    public function destroy(string $token)
    {
        if ($this->validToken($token)) {
            Storage::disk('local')->delete($this->path(Auth::id(), $token));
        }

        return redirect()->route('transactions.index');
    }


    /*
    |--------------------------------------------------------------------------
    | Hilfsfunktionen
    |--------------------------------------------------------------------------
    */

    private function accounts()
    {
        return Account::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function directory(int $userId): string
    {
        return 'imports/' . $userId;
    }

    private function path(int $userId, string $token): string
    {
        return $this->directory($userId) . '/' . $token . '.csv';
    }

    private function validToken(string $token): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{32}$/', $token);
    }

    private function readFile(int $userId, string $token): ?string
    {
        if (! $this->validToken($token)) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = $this->path($userId, $token);

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    /**
     * Nicht abgeschlossene Importe nach einem Tag entfernen.
     */
    private function cleanupOldFiles(int $userId): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files($this->directory($userId)) as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }
}
