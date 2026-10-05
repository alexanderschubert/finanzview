<?php

namespace App\Services\Fints;

use App\Models\ApplicationSetting;
use App\Models\FintsInstitute;
use App\Services\CsvImportService;
use Illuminate\Support\Facades\DB;

/**
 * Liest die FinTS-Bankenliste der Deutschen Kreditwirtschaft
 * („fints_institute … Master.csv“: Semikolon, Windows-1252) und
 * ersetzt damit die gespeicherte Liste.
 *
 * Es zählen nur Banken mit PIN/TAN-Adresse; Zeilen derselben Bank mit
 * gleicher Adresse (je Filiale/Ort) werden zusammengefasst.
 */
class InstituteListImporter
{
    public function __construct(
        private CsvImportService $csv,
    ) {
    }

    /**
     * @return int Anzahl importierter Banken
     *
     * @throws \InvalidArgumentException wenn die Datei kein Bankenverzeichnis ist
     */
    public function import(string $content): int
    {
        $rows = $this->parse($content);

        DB::transaction(function () use ($rows) {
            FintsInstitute::query()->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                FintsInstitute::query()->insert($chunk);
            }

            ApplicationSetting::set('fints_institutes_imported_at', now()->toIso8601String());
        });

        return count($rows);
    }

    /**
     * @return list<array{bank_code: string, bic: ?string, name: string, city: ?string, url: string}>
     */
    public function parse(string $content): array
    {
        $content = $this->csv->normalize($content);
        $lines = $this->csv->rows($content, ';');

        $header = null;
        $columns = [];

        foreach (array_slice($lines, 0, 5, true) as $index => $line) {
            $found = $this->columns($line);

            if (isset($found['bank_code'], $found['url'], $found['name'])) {
                $header = $index;
                $columns = $found;
                break;
            }
        }

        if ($header === null) {
            throw new \InvalidArgumentException('Das ist keine FinTS-Bankenliste (Spalten BLZ, Institut und PIN/TAN-Zugang URL fehlen).');
        }

        $rows = [];

        foreach (array_slice($lines, $header + 1) as $line) {
            $bankCode = preg_replace('/\D/', '', $line[$columns['bank_code']] ?? '');
            $url = trim($line[$columns['url']] ?? '');
            $name = trim($line[$columns['name']] ?? '');

            if (strlen($bankCode) !== 8 || $name === '' || ! preg_match('#^https://#i', $url)) {
                continue;
            }

            $key = $bankCode . '|' . strtolower($url);

            $rows[$key] ??= [
                'bank_code' => $bankCode,
                'bic' => isset($columns['bic']) ? (trim($line[$columns['bic']] ?? '') ?: null) : null,
                'name' => mb_substr($name, 0, 255),
                'city' => isset($columns['city']) ? (trim($line[$columns['city']] ?? '') ?: null) : null,
                'url' => mb_substr($url, 0, 255),
            ];
        }

        return array_values($rows);
    }

    /**
     * Spaltennamen der Liste sind uneinheitlich geschrieben
     * („HBCI- Zugang     IP-Adresse“), deshalb Stichwörter statt exakter Namen.
     */
    private function columns(array $header): array
    {
        $found = [];

        foreach ($header as $index => $title) {
            $title = mb_strtolower(trim($title));

            match (true) {
                $title === 'blz' => $found['bank_code'] = $index,
                $title === 'bic' => $found['bic'] = $index,
                $title === 'institut' => $found['name'] = $index,
                $title === 'ort' => $found['city'] = $index,
                str_contains($title, 'pin/tan') && str_contains($title, 'url') => $found['url'] = $index,
                default => null,
            };
        }

        return $found;
    }
}
