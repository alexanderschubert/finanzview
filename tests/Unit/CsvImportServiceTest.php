<?php

namespace Tests\Unit;

use App\Services\CsvImportService;
use PHPUnit\Framework\TestCase;

class CsvImportServiceTest extends TestCase
{
    private CsvImportService $csv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csv = new CsvImportService();
    }

    public function test_amounts_in_german_and_english_format(): void
    {
        $this->assertSame(-1234.56, $this->csv->parseAmount('-1.234,56', ','));
        $this->assertSame(1234.56, $this->csv->parseAmount('1.234,56 €', ','));
        $this->assertSame(-12.5, $this->csv->parseAmount('12,50-', ','));
        $this->assertSame(-1234.56, $this->csv->parseAmount('-1,234.56', '.'));
        $this->assertSame(42.0, $this->csv->parseAmount('42', ','));
        $this->assertNull($this->csv->parseAmount('', ','));
        $this->assertNull($this->csv->parseAmount('n/a', ','));
    }

    public function test_decimal_separator_detection(): void
    {
        $this->assertSame(',', $this->csv->detectDecimalSeparator(['-12,50', '1.200,00', '3']));
        $this->assertSame('.', $this->csv->detectDecimalSeparator(['-12.50', '1,200.00', '3']));
    }

    public function test_dates_in_common_formats(): void
    {
        $this->assertSame('2026-09-01', $this->csv->parseDate('01.09.2026')?->toDateString());
        $this->assertSame('2026-09-01', $this->csv->parseDate('01.09.26')?->toDateString());
        $this->assertSame('2026-09-01', $this->csv->parseDate('2026-09-01')?->toDateString());
        $this->assertSame('2026-09-01', $this->csv->parseDate('2026-09-01 14:03:22')?->toDateString());
        $this->assertNull($this->csv->parseDate('32.13.2026'));
        $this->assertNull($this->csv->parseDate('gestern'));
    }

    public function test_sparkasse_header_is_mapped(): void
    {
        $mapping = $this->csv->guessMapping(['Auftragskonto', 'Buchungstag', 'Valutadatum', 'Buchungstext', 'Verwendungszweck', 'Beguenstigter/Zahlungspflichtiger', 'Kontonummer/IBAN', 'BIC (SWIFT-Code)', 'Betrag', 'Waehrung', 'Info']);

        $this->assertSame(['date' => 1, 'amount' => 8, 'debit' => null, 'credit' => null, 'merchant' => 5, 'description' => 4], $mapping);
    }

    public function test_ing_header_does_not_confuse_buchung_and_buchungstext(): void
    {
        $mapping = $this->csv->guessMapping(['Buchung', 'Wertstellungsdatum', 'Auftraggeber/Empfänger', 'Buchungstext', 'Verwendungszweck', 'Saldo', 'Währung', 'Betrag', 'Währung']);

        $this->assertSame(0, $mapping['date']);
        $this->assertSame(7, $mapping['amount']);
        $this->assertSame(2, $mapping['merchant']);
        $this->assertSame(4, $mapping['description']);
    }

    public function test_n26_english_header_is_mapped(): void
    {
        $mapping = $this->csv->guessMapping(['Booking Date', 'Value Date', 'Partner Name', 'Partner Iban', 'Type', 'Payment Reference', 'Account Name', 'Amount (EUR)', 'Original Amount']);

        $this->assertSame(0, $mapping['date']);
        $this->assertSame(7, $mapping['amount']);
        $this->assertSame(2, $mapping['merchant']);
        $this->assertSame(5, $mapping['description']);
    }

    public function test_debit_credit_columns(): void
    {
        $mapping = $this->csv->guessMapping(['Datum', 'Text', 'Soll', 'Haben']);

        $this->assertSame(['date' => 0, 'amount' => null, 'debit' => 2, 'credit' => 3, 'merchant' => null, 'description' => 1], $mapping);
    }

    public function test_preamble_lines_are_skipped_and_encoding_converted(): void
    {
        $content = "\"Kontonummer:\";\"DE12 3456\";\n"
            . "\"Kontostand vom 28.09.2026:\";\"1.234,56 EUR\";\n"
            . "\n"
            . "\"Buchungsdatum\";\"Zahlungsempf\xE4nger*in\";\"Verwendungszweck\";\"Betrag (\x80)\"\n"
            . "\"01.09.26\";\"B\xE4ckerei M\xFCller\";\"Br\xF6tchen\";\"-3,20\"\n";

        $analysis = $this->csv->analyze($content);

        $this->assertSame('Zahlungsempfänger*in', $analysis['headers'][1]);
        $this->assertSame('Betrag (€)', $analysis['headers'][3]);
        $this->assertCount(1, $analysis['rows']);
        $this->assertSame('Bäckerei Müller', $analysis['rows'][0][1]);
        $this->assertSame(['date' => 0, 'amount' => 3, 'debit' => null, 'credit' => null, 'merchant' => 1, 'description' => 2], $analysis['mapping']);
    }

    public function test_comma_delimited_file_with_quoted_commas(): void
    {
        $content = "Date,Payee,Description,Amount\n2026-09-02,\"Shop, Inc.\",Order 1,\"-1,200.50\"\n";

        $analysis = $this->csv->analyze($content);

        $this->assertSame('Shop, Inc.', $analysis['rows'][0][1]);
        $this->assertSame('-1,200.50', $analysis['rows'][0][3]);
    }
}
