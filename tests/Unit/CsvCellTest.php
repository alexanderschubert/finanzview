<?php

namespace Tests\Unit;

use App\Support\CsvCell;
use PHPUnit\Framework\TestCase;

class CsvCellTest extends TestCase
{
    public function test_formulas_are_neutralized_and_numbers_kept(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://evil\")", CsvCell::safe('=HYPERLINK("http://evil")'));
        $this->assertSame("'+49 30 123", CsvCell::safe('+49 30 123'));
        $this->assertSame("'@SUM(A1)", CsvCell::safe('@SUM(A1)'));
        $this->assertSame("'-2+3", CsvCell::safe('-2+3'));

        $this->assertSame('-12,34', CsvCell::safe('-12,34'));
        $this->assertSame('1234.56', CsvCell::safe('1234.56'));
        $this->assertSame('REWE Markt', CsvCell::safe('REWE Markt'));
        $this->assertSame(42, CsvCell::safe(42));
        $this->assertNull(CsvCell::safe(null));
    }
}
