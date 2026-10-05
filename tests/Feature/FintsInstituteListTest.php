<?php

namespace Tests\Feature;

use App\Models\FintsInstitute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FintsInstituteListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** Auszug im Format der DK-Liste (Windows-1252, Semikolon, uneinheitliche Kopfzeile). */
    private function list(): string
    {
        $csv = "Nr.;BLZ;BIC;Institut;Ort;RZ;Organisation;HBCI-Zugang DNS;HBCI- Zugang     IP-Adresse;HBCI-Version;DDV;RDH-1;RDH-2;RDH-3;RDH-4;RDH-5;RDH-6;RDH-7;RDH-8;RDH-9;RDH-10;RAH-7;RAH-9;RAH-10;PIN/TAN-Zugang URL;Version;Datum letzte Änderung\r\n"
            . "2;10010010;PBNKDEFFXXX;Postbank;Berlin;eigenes Rechenzentrum;BdB;;;;;;;;;;;;;;;;;;https://hbci.postbank.de/banking/hbci.do;FinTS V3.0;04.02.2022\r\n"
            . "4;10020890;HYVEDEMM488;UniCredit Bank - HypoVereinsbank AG;Berlin;eigenes Rechenzentrum;BdB;;;3.0;;;;;;;;;;;;;ja;ja;https://hbci-01.hypovereinsbank.de/bank/hbci;FinTS V3.0;14.10.2024\r\n"
            . "5;10020890;HYVEDEMM488;UniCredit Bank - HypoVereinsbank AG;Kleinmachnow;eigenes Rechenzentrum;BdB;;;3.0;;;;;;;;;;;;;ja;ja;https://hbci-01.hypovereinsbank.de/bank/hbci;FinTS V3.0;14.10.2024\r\n"
            . "9;10030000;ALTBANK0XXX;Alte Bank ohne PIN/TAN;Berlin;eigenes Rechenzentrum;BdB;;;3.0;;;;;;;;;;;;;;;;FinTS V2.2;\r\n"
            . "16;10050000;BELADEBEXXX;Berliner Sparkasse;Berlin;Finanz Informatik GmbH & Co. KG;DSGV;;;;;;;;;;;;;;;;;;https://banking-be3.s-fints-pt-be.de/fints30;FinTS V3.0;09.02.2022\r\n"
            . "17;40050150;WELADED1MST;Sparkasse Münsterland Ost;Münster;Finanz Informatik GmbH & Co. KG;DSGV;;;;;;;;;;;;;;;;;;https://banking-nw1.s-fints-pt-nw.de/fints30;FinTS V3.0;09.02.2022\r\n";

        return mb_convert_encoding($csv, 'ISO-8859-1', 'UTF-8');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->forceFill(['is_admin' => true])->save();
    }

    private function import(?User $user = null, ?string $content = null)
    {
        return $this->actingAs($user ?? $this->admin)->post(route('bank-connections.institutes.import'), [
            'list' => UploadedFile::fake()->createWithContent('fints_institute Master.csv', $content ?? $this->list()),
        ]);
    }

    public function test_import_reads_windows_1252_skips_banks_without_pin_tan_and_merges_branches(): void
    {
        $this->import()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bank-connections.index'))
            ->assertSessionHas('success', '4 Banken importiert.');

        $this->assertSame(4, FintsInstitute::count());
        $this->assertNull(FintsInstitute::where('bank_code', '10030000')->first());

        $sparkasse = FintsInstitute::where('bank_code', '10050000')->firstOrFail();
        $this->assertSame('Berliner Sparkasse', $sparkasse->name);
        $this->assertSame('BELADEBEXXX', $sparkasse->bic);
        $this->assertSame('https://banking-be3.s-fints-pt-be.de/fints30', $sparkasse->url);

        $this->assertSame('Sparkasse Münsterland Ost', FintsInstitute::where('bank_code', '40050150')->value('name'));
        $this->assertSame(1, FintsInstitute::where('bank_code', '10020890')->count());
    }

    public function test_reimport_replaces_the_list_and_shows_status(): void
    {
        $this->import();
        $this->import();

        $this->assertSame(4, FintsInstitute::count());

        $this->actingAs($this->admin)
            ->get(route('bank-connections.index'))
            ->assertOk()
            ->assertSee('4 Banken')
            ->assertSee('Liste ersetzen');
    }

    public function test_search_by_name_words_and_bank_code(): void
    {
        $this->import();

        $this->actingAs($this->admin)
            ->getJson(route('bank-connections.institutes.search', ['q' => 'berliner spar']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.bank_code', '10050000')
            ->assertJsonPath('0.url', 'https://banking-be3.s-fints-pt-be.de/fints30');

        $this->actingAs($this->admin)
            ->getJson(route('bank-connections.institutes.search', ['q' => '4005']))
            ->assertJsonPath('0.name', 'Sparkasse Münsterland Ost');

        $this->actingAs($this->admin)
            ->getJson(route('bank-connections.institutes.search', ['q' => 'sparkasse']))
            ->assertJsonCount(2);

        $this->actingAs($this->admin)->getJson(route('bank-connections.institutes.search', ['q' => 'x']))->assertExactJson([]);
        $this->actingAs($this->admin)->getJson(route('bank-connections.institutes.search', ['q' => '%%']))->assertExactJson([]);
    }

    public function test_form_offers_search_only_when_list_exists(): void
    {
        config(['services.fints.product_id' => 'TEST123']);

        $this->actingAs($this->admin)->get(route('bank-connections.create'))
            ->assertOk()
            ->assertSee('data-institute-search', false)
            ->assertSee('Importiere sie auf der Seite');

        $this->import();

        $page = $this->actingAs($this->admin)->get(route('bank-connections.create'))->assertOk();

        // Nur das Suchfeld-Element prüfen (im Skript kommt „hidden“ ebenfalls vor).
        preg_match('/<div data-institute-search[^>]*>/', $page->getContent(), $tag);
        $this->assertNotEmpty($tag);
        $this->assertStringNotContainsString('hidden', $tag[0]);
        $page->assertSee('Du kannst die Felder auch von Hand ändern.');
    }

    public function test_only_admins_can_import_and_invalid_files_are_rejected(): void
    {
        $member = User::factory()->create(['is_active' => true]);

        $this->import($member)->assertForbidden();
        $this->assertSame(0, FintsInstitute::count());

        $this->import($this->admin, "Datum;Betrag\n01.09.2026;5,00\n")->assertSessionHasErrors('list');

        $this->actingAs($member)
            ->getJson(route('bank-connections.institutes.search', ['q' => 'sparkasse']))
            ->assertOk(); // Suche steht allen Benutzern offen
    }
}
