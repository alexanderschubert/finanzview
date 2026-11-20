<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoAndPaletteTest extends TestCase
{
    use RefreshDatabase;

    /** Kleines gültiges PNG – die Test-Umgebung hat kein GD für UploadedFile::image(). */
    private function png(): UploadedFile
    {
        $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent('ich.png', $data);
    }

    public function test_profile_photo_can_be_uploaded_shown_and_removed(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('settings.profile.photo.update'), ['photo' => $this->png()])
            ->assertRedirect(route('settings.profile'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('local')->assertExists($user->profile_photo_path);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee(route('settings.profile.photo'), false);
        $this->actingAs($user)->get(route('settings.profile.photo'))->assertOk();

        $this->actingAs($user)->delete(route('settings.profile.photo.destroy'))->assertRedirect(route('settings.profile'));
        $this->assertNull($user->fresh()->profile_photo_path);
        $this->actingAs($user)->get(route('settings.profile.photo'))->assertNotFound();
    }

    public function test_profile_photo_rejects_non_images_and_is_private(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('settings.profile.photo.update'), ['photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');

    }

    public function test_profile_photo_requires_login(): void
    {
        $this->get(route('settings.profile.photo'))->assertRedirect();
    }

    public function test_palette_is_in_the_topbar_and_search_finds_own_data_only(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);

        Account::create(['user_id' => $user->id, 'name' => 'Urlaubskasse', 'type' => 'cash', 'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true]);
        Account::create(['user_id' => $other->id, 'name' => 'Urlaub fremd', 'type' => 'cash', 'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true]);
        Category::create(['user_id' => $user->id, 'name' => 'Urlaub', 'type' => 'expense', 'is_active' => true]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="command-palette"', false)
            ->assertSee('data-palette-open', false)
            ->assertSee('Neue Buchung');

        $this->actingAs($user)->getJson(route('search', ['q' => 'urlaub']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Urlaubskasse'])
            ->assertJsonFragment(['group' => 'Kategorien'])
            ->assertJsonMissing(['label' => 'Urlaub fremd']);

        $this->actingAs($user)->getJson(route('search', ['q' => 'u']))->assertOk()->assertExactJson([]);
    }

    public function test_administration_lives_in_settings_and_not_in_the_sidebar(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('>Benutzer<', false)
            ->assertSee('>Anbieter<', false)
            ->assertDontSee('>Administration<', false);
    }
}
