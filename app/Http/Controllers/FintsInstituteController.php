<?php

namespace App\Http\Controllers;

use App\Models\FintsInstitute;
use App\Services\Fints\InstituteListImporter;
use Illuminate\Http\Request;

/**
 * Bankensuche für die Einrichtung (alle Benutzer) und Import der
 * Bankenliste (nur Administratoren).
 */
class FintsInstituteController extends Controller
{
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $results = FintsInstitute::query()
            ->search($term)
            ->orderBy('name')
            ->limit(12)
            ->get(['bank_code', 'bic', 'name', 'city', 'url']);

        return response()->json($results);
    }

    public function import(Request $request, InstituteListImporter $importer)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->validate([
            'list' => ['required', 'file', 'max:5120', 'extensions:csv,txt'],
        ], [
            'list.required' => 'Bitte die Bankenliste (CSV) auswählen.',
            'list.max' => 'Die Datei darf höchstens 5 MB groß sein.',
            'list.extensions' => 'Bitte die CSV-Datei der Bankenliste auswählen.',
        ]);

        try {
            $count = $importer->import((string) file_get_contents($request->file('list')->getRealPath()));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['list' => $e->getMessage()]);
        }

        return redirect()
            ->route('bank-connections.index')
            ->with('success', number_format($count, 0, ',', '.') . ' Banken importiert.');
    }
}
