<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Liest Kontoauszüge im CSV-Format (Sparkasse, DKB, ING, comdirect,
 * N26, PayPal, …) und wandelt sie in Buchungsvorschläge um.
 *
 * Kodierung, Trennzeichen, Kopfzeile, Zahlen- und Datumsformat
 * werden automatisch erkannt; die Spaltenzuordnung lässt sich
 * anschließend in der Vorschau anpassen.
 */
class CsvImportService
{
    public const MAX_ROWS = 1000;

    /**
     * Felder, die einer Spalte zugeordnet werden können,
     * mit Schlüsselwörtern für die automatische Erkennung
     * (in absteigender Priorität).
     */
    public const FIELDS = [
        'date' => ['buchungstag', 'buchungsdatum', 'buchung', 'datum', 'date', 'valutadatum', 'valuta', 'wertstellung'],
        'amount' => ['betrag', 'amount', 'umsatz', 'brutto', 'value'],
        'debit' => ['soll', 'ausgang', 'belastung', 'debit'],
        'credit' => ['haben', 'eingang', 'gutschrift', 'credit'],
        'merchant' => ['zahlungsempfänger', 'zahlungsempfaenger', 'empfänger', 'empfaenger', 'begünstigter', 'beguenstigter', 'auftraggeber', 'zahlungspflichtige', 'payee', 'counterparty', 'partner name', 'name'],
        'description' => ['verwendungszweck', 'verwendung', 'beschreibung', 'description', 'payment reference', 'purpose', 'betreff', 'buchungstext', 'vorgang', 'text'],
    ];

    public const FIELD_LABELS = [
        'date' => 'Datum',
        'amount' => 'Betrag',
        'debit' => 'Soll / Ausgang',
        'credit' => 'Haben / Eingang',
        'merchant' => 'Empfänger / Auftraggeber',
        'description' => 'Verwendungszweck',
    ];


    /*
    |--------------------------------------------------------------------------
    | Datei einlesen
    |--------------------------------------------------------------------------
    */

    /**
     * Wandelt den Dateiinhalt nach UTF-8 um (viele Banken liefern
     * Windows-1252 bzw. ISO-8859-1) und entfernt das BOM.
     */
    public function normalize(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    public function detectDelimiter(string $content): string
    {
        $sample = implode("\n", array_slice(explode("\n", $content), 0, 30));

        $counts = [];

        foreach ([';', ',', "\t", '|'] as $delimiter) {
            $counts[$delimiter] = substr_count($sample, $delimiter);
        }

        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function rows(string $content, string $delimiter): array
    {
        $file = new \SplTempFileObject();
        $file->fwrite($content);
        $file->rewind();
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);
        $file->setCsvControl($delimiter, '"', '');

        $rows = [];

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $row = array_map(fn ($cell) => trim((string) $cell), $row);

            if (implode('', $row) === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Manche Banken stellen Kontoinformationen vor die eigentliche
     * Tabelle. Kopfzeile = erste Zeile mit Datums- und Betragsspalte.
     */
    public function detectHeaderRow(array $rows): int
    {
        foreach (array_slice($rows, 0, 25, true) as $index => $row) {
            $mapping = $this->guessMapping($row);

            if ($mapping['date'] !== null && ($mapping['amount'] !== null || $mapping['debit'] !== null || $mapping['credit'] !== null)) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * Ordnet Kopfzeilen-Spalten den Feldern zu.
     *
     * @return array<string, int|null>
     */
    public function guessMapping(array $headers): array
    {
        $normalized = array_map(fn ($header) => mb_strtolower(trim((string) $header)), $headers);

        $mapping = array_fill_keys(array_keys(self::FIELDS), null);
        $used = [];

        foreach (self::FIELDS as $field => $keywords) {
            foreach ($keywords as $keyword) {
                foreach ($normalized as $index => $header) {
                    if (isset($used[$index]) || $header === '') {
                        continue;
                    }

                    // Kurze Wörter (z. B. „soll“, „buchung“) nur als ganzes Wort,
                    // damit „Buchung“ nicht auf „Buchungstext“ passt.
                    $matches = mb_strlen($keyword) <= 7
                        ? preg_match('/(^|[^\pL])' . preg_quote($keyword, '/') . '($|[^\pL])/u', $header)
                        : str_contains($header, $keyword);

                    if ($matches) {
                        $mapping[$field] = $index;
                        $used[$index] = true;
                        continue 3;
                    }
                }
            }
        }

        // Eine eigene Betragsspalte hat Vorrang vor Soll/Haben.
        if ($mapping['amount'] !== null) {
            $mapping['debit'] = null;
            $mapping['credit'] = null;
        }

        return $mapping;
    }


    /*
    |--------------------------------------------------------------------------
    | Werte umwandeln
    |--------------------------------------------------------------------------
    */

    /**
     * Ermittelt das Dezimaltrennzeichen einer Spalte: „,“ (deutsch)
     * oder „.“ (englisch), anhand der Stellen hinter dem letzten Trenner.
     */
    public function detectDecimalSeparator(array $values): string
    {
        $comma = 0;
        $dot = 0;

        foreach ($values as $value) {
            $value = preg_replace('/[^\d.,]/', '', (string) $value);

            if (preg_match('/,\d{1,2}$/', $value)) {
                $comma++;
            } elseif (preg_match('/\.\d{1,2}$/', $value)) {
                $dot++;
            }
        }

        return $dot > $comma ? '.' : ',';
    }

    public function parseAmount(?string $value, string $decimal = ','): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $negative = str_starts_with($value, '-')
            || str_ends_with($value, '-')
            || str_starts_with($value, '(')
            || str_starts_with($value, '−');

        $value = preg_replace('/[^\d.,]/', '', $value);

        if ($value === '') {
            return null;
        }

        $thousands = $decimal === ',' ? '.' : ',';
        $value = str_replace($thousands, '', $value);
        $value = str_replace($decimal, '.', $value);

        if (! is_numeric($value)) {
            return null;
        }

        $amount = round((float) $value, 2);

        return $negative ? -$amount : $amount;
    }

    public function parseDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['d.m.Y', 'd.m.y', 'Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return Carbon::instance($date);
            }
        }

        // Datum mit Uhrzeit (z. B. „2026-09-01 14:03:22“ bei PayPal/N26).
        if (preg_match('/^(\d{4}-\d{2}-\d{2}|\d{2}\.\d{2}\.\d{4})[ T]/', $value, $match)) {
            return $this->parseDate($match[1]);
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Datei analysieren
    |--------------------------------------------------------------------------
    */

    /**
     * Liest die Datei und liefert Kopfzeile, Datenzeilen und die
     * erkannte bzw. übergebene Spaltenzuordnung.
     */
    public function analyze(string $content, array $mapping = []): array
    {
        $content = $this->normalize($content);
        $delimiter = $this->detectDelimiter($content);
        $rows = $this->rows($content, $delimiter);

        $headerIndex = $this->detectHeaderRow($rows);
        $headers = $rows[$headerIndex] ?? [];
        $dataRows = array_values(array_slice($rows, $headerIndex + 1));

        $guessed = $this->guessMapping($headers);
        $columnCount = count($headers);

        $resolved = [];

        foreach (array_keys(self::FIELDS) as $field) {
            if (array_key_exists($field, $mapping)) {
                $column = $mapping[$field];
                $resolved[$field] = is_numeric($column) && (int) $column >= 0 && (int) $column < $columnCount
                    ? (int) $column
                    : null;
            } else {
                $resolved[$field] = $guessed[$field];
            }
        }

        return [
            'headers' => $headers,
            'rows' => $dataRows,
            'mapping' => $resolved,
        ];
    }

    /**
     * Wandelt die Datenzeilen anhand der Zuordnung in
     * Buchungsvorschläge um.
     *
     * @return Collection<int, array>
     */
    public function parse(array $analysis, int $userId, int $accountId): Collection
    {
        $mapping = $analysis['mapping'];
        $rows = array_slice($analysis['rows'], 0, self::MAX_ROWS);

        $cell = fn (array $row, ?int $column) => $column === null ? '' : (string) ($row[$column] ?? '');

        $amountColumns = array_filter([$mapping['amount'], $mapping['debit'], $mapping['credit']], fn ($c) => $c !== null);
        $decimal = $this->detectDecimalSeparator(
            collect($rows)->flatMap(fn ($row) => array_map(fn ($c) => $cell($row, $c), $amountColumns))->all()
        );

        $categorySuggestions = $this->categorySuggestions($userId);
        $occurrences = [];

        $items = collect($rows)->map(function (array $row, int $index) use ($mapping, $cell, $decimal, $accountId, $categorySuggestions, &$occurrences) {

            $date = $this->parseDate($cell($row, $mapping['date']));

            if ($mapping['amount'] !== null) {
                $amount = $this->parseAmount($cell($row, $mapping['amount']), $decimal);
            } else {
                $debit = $this->parseAmount($cell($row, $mapping['debit']), $decimal);
                $credit = $this->parseAmount($cell($row, $mapping['credit']), $decimal);

                $amount = ($debit === null && $credit === null)
                    ? null
                    : round(abs($credit ?? 0) - abs($debit ?? 0), 2);
            }

            $merchant = Str::limit(Str::squish($cell($row, $mapping['merchant'])), 250, '');
            $description = Str::limit(Str::squish($cell($row, $mapping['description'])), 250, '');

            $error = match (true) {
                $date === null => 'Datum fehlt oder unbekanntes Format',
                $amount === null => 'Betrag fehlt',
                $amount == 0 => 'Betrag ist 0',
                default => null,
            };

            $type = ($amount ?? 0) < 0 ? 'expense' : 'income';

            /*
             * Fingerabdruck gegen doppelten Import. Gleiche Zeilen
             * innerhalb einer Datei werden durchgezählt.
             */
            $key = implode('|', [$accountId, $date?->toDateString(), $amount, mb_strtolower($merchant), mb_strtolower($description)]);
            $occurrences[$key] = ($occurrences[$key] ?? 0) + 1;
            $externalId = 'csv:' . sha1($key . '|' . $occurrences[$key]);

            return [
                'index' => $index,
                'date' => $date,
                'amount' => $amount === null ? null : abs($amount),
                'type' => $type,
                'merchant' => $merchant,
                'description' => $description !== '' ? $description : ($merchant !== '' ? $merchant : 'CSV-Import'),
                'external_id' => $externalId,
                'category_id' => $error === null ? $this->suggestCategory($categorySuggestions, $type, $merchant, $description) : null,
                'error' => $error,
                'duplicate' => null,
            ];
        });

        return $this->markDuplicates($items, $userId, $accountId);
    }

    /**
     * „imported“ = genau diese Zeile wurde schon importiert,
     * „possible“ = gleiche Buchung (Datum, Betrag, Art) existiert bereits,
     * z. B. von Hand erfasst.
     */
    private function markDuplicates(Collection $items, int $userId, int $accountId): Collection
    {
        $valid = $items->whereNull('error');

        if ($valid->isEmpty()) {
            return $items;
        }

        $imported = Transaction::query()
            ->where('user_id', $userId)
            ->whereIn('external_id', $valid->pluck('external_id'))
            ->pluck('external_id')
            ->flip();

        $existing = Transaction::query()
            ->where('user_id', $userId)
            ->where('account_id', $accountId)
            ->whereDate('transaction_date', '>=', $valid->min('date')->toDateString())
            ->whereDate('transaction_date', '<=', $valid->max('date')->toDateString())
            ->where(fn ($q) => $q->whereNull('external_id')->orWhere('external_id', 'not like', 'csv:%'))
            ->get(['transaction_date', 'amount', 'type'])
            ->countBy(fn ($t) => $t->transaction_date->toDateString() . '|' . number_format((float) $t->amount, 2, '.', '') . '|' . $t->type);

        return $items->map(function (array $item) use ($imported, &$existing) {
            if ($item['error'] !== null) {
                return $item;
            }

            if ($imported->has($item['external_id'])) {
                $item['duplicate'] = 'imported';

                return $item;
            }

            $key = $item['date']->toDateString() . '|' . number_format($item['amount'], 2, '.', '') . '|' . $item['type'];

            if (($existing[$key] ?? 0) > 0) {
                $item['duplicate'] = 'possible';
                $existing[$key]--;
            }

            return $item;
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Kategorie-Vorschläge
    |--------------------------------------------------------------------------
    */

    /**
     * Lernt aus den bisherigen Buchungen: Empfänger bzw. Beschreibung
     * → zuletzt verwendete Kategorie.
     */
    private function categorySuggestions(int $userId): array
    {
        $suggestions = [];

        Transaction::query()
            ->where('user_id', $userId)
            ->whereNotNull('category_id')
            ->whereIn('type', ['income', 'expense'])
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(3000)
            ->get(['type', 'merchant', 'description', 'category_id'])
            ->each(function ($transaction) use (&$suggestions) {
                foreach ([$transaction->merchant, $transaction->description] as $text) {
                    $key = $this->suggestionKey($text);

                    if ($key !== '') {
                        $suggestions[$transaction->type][$key] ??= $transaction->category_id;
                    }
                }
            });

        return $suggestions;
    }

    private function suggestCategory(array $suggestions, string $type, string $merchant, string $description): ?int
    {
        foreach ([$merchant, $description] as $text) {
            $key = $this->suggestionKey($text);

            if ($key !== '' && isset($suggestions[$type][$key])) {
                return $suggestions[$type][$key];
            }
        }

        return null;
    }

    /**
     * Vergleichsschlüssel ohne Ziffern, Satzzeichen und Groß-/Kleinschreibung,
     * damit z. B. „REWE Markt 1234“ und „Rewe Markt 5678“ zusammenpassen.
     */
    private function suggestionKey(?string $text): string
    {
        $text = mb_strtolower((string) $text);
        $text = preg_replace('/[^\pL ]+/u', ' ', $text);

        return Str::limit(Str::squish($text), 40, '');
    }
}
