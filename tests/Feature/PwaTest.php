<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_valid_and_icons_exist(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('FinanzView', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertStringStartsWith('/dashboard', $manifest['start_url']);

        $purposes = collect($manifest['icons'])->pluck('purpose')->unique()->values()->all();
        $this->assertContains('maskable', $purposes);
        $this->assertContains('any', $purposes);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_never_caches_pages(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        // Seitenaufrufe gehen immer ins Netz, nur ohne Netz kommt die Offline-Seite.
        $this->assertStringContainsString("request.mode === 'navigate'", $sw);
        $this->assertStringContainsString('fetch(request).catch(() => caches.match(OFFLINE_URL))', $sw);
        $this->assertFileExists(public_path('offline.html'));
    }

    public function test_layouts_link_manifest_and_register_service_worker(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false);

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('rel="manifest"', false);
    }

    public function test_back_button_only_on_sub_pages(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertDontSee('onclick="history.back()"', false);

        $this->actingAs($user)
            ->get(route('transactions.create'))
            ->assertSee('onclick="history.back()"', false);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertSee('data-install', false);
    }
}
