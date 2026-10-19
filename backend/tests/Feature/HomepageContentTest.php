<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HomepageContent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_homepage_settings_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('owner');

        $this->actingAs($user)
            ->get('/admin/homepage-settings')
            ->assertOk()
            ->assertSee('Homepage &amp; Imagery', false);
    }

    public function test_homepage_endpoint_returns_null_defaults_when_nothing_is_configured(): void
    {
        $response = $this->getJson('/api/v1/homepage');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hero.media_type', 'image')
            ->assertJsonPath('data.hero.image', null)
            ->assertJsonPath('data.hero.title_lead', null)
            ->assertJsonPath('data.experiences', [])
            ->assertJsonPath('data.welcome.stats', [])
            ->assertJsonPath('data.page_heroes.rooms', null);
    }

    public function test_homepage_endpoint_localizes_and_falls_back_to_english(): void
    {
        HomepageContent::save([
            'hero' => [
                'media_type' => 'video',
                'video' => 'homepage/video/sunrise.mp4',
                'poster' => 'homepage/poster.jpg',
                'title_lead_en' => 'Where the island',
                'title_lead_bn' => 'যেখানে দ্বীপ',
                'title_accent_en' => 'slows down',
                'title_accent_bn' => '',
            ],
            'welcome' => [
                'stats' => [
                    ['value' => '5', 'label_en' => 'Rooms', 'label_bn' => 'রুম'],
                    ['value' => '', 'label_en' => 'Ignored'],
                ],
            ],
            'experiences' => [
                ['image' => 'homepage/reef.jpg', 'title_en' => 'Reef walks', 'body_en' => 'Low tide.'],
                ['title_en' => '', 'body_en' => 'Untitled cards are skipped.'],
            ],
            'page_heroes' => ['gallery' => 'page-heroes/gallery.jpg'],
        ]);

        $this->getJson('/api/v1/homepage?locale=bn')
            ->assertOk()
            ->assertJsonPath('data.hero.media_type', 'video')
            ->assertJsonPath('data.hero.video', 'homepage/video/sunrise.mp4')
            ->assertJsonPath('data.hero.title_lead', 'যেখানে দ্বীপ')
            ->assertJsonPath('data.hero.title_accent', 'slows down')
            ->assertJsonCount(1, 'data.welcome.stats')
            ->assertJsonPath('data.welcome.stats.0.label', 'রুম')
            ->assertJsonCount(1, 'data.experiences')
            ->assertJsonPath('data.experiences.0.title', 'Reef walks')
            ->assertJsonPath('data.page_heroes.gallery', 'page-heroes/gallery.jpg')
            ->assertJsonPath('data.page_heroes.rooms', null);

        $this->getJson('/api/v1/homepage?locale=en')
            ->assertJsonPath('data.hero.title_lead', 'Where the island')
            ->assertJsonPath('data.welcome.stats.0.label', 'Rooms');
    }
}
