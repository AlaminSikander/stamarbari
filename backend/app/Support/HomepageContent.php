<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Admin-editable homepage and page-hero content, stored as one JSON setting.
 * Empty values are returned as null so the frontend can fall back to its defaults.
 */
class HomepageContent
{
    public const GROUP = 'homepage';

    public const KEY = 'content';

    /** Routes whose hero image can be overridden from the admin. */
    public const PAGE_HEROES = [
        'rooms', 'gallery', 'about', 'our-story', 'experience', 'contact',
        'offers', 'packages', 'blog', 'faq', 'saint-martin', 'booking',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function raw(): array
    {
        $value = Setting::getValue(self::KEY, null, self::GROUP);
        if (! is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data): void
    {
        Setting::setValue(self::KEY, $data, self::GROUP);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forLocale(string $locale): array
    {
        $raw = self::raw();
        $pick = fn (array $source, string $field): ?string => self::text(
            $locale === 'bn' && filled($source["{$field}_bn"] ?? null)
                ? $source["{$field}_bn"]
                : ($source["{$field}_en"] ?? null)
        );

        $hero = $raw['hero'] ?? [];
        $welcome = $raw['welcome'] ?? [];
        $saintMartin = $raw['saint_martin'] ?? [];
        $finalCta = $raw['final_cta'] ?? [];
        $pageHeroes = $raw['page_heroes'] ?? [];

        return [
            'hero' => [
                'media_type' => ($hero['media_type'] ?? 'image') === 'video' ? 'video' : 'image',
                'image' => self::path($hero['image'] ?? null),
                'video' => self::path($hero['video'] ?? null),
                'poster' => self::path($hero['poster'] ?? null),
                'eyebrow' => $pick($hero, 'eyebrow'),
                'title_lead' => $pick($hero, 'title_lead'),
                'title_accent' => $pick($hero, 'title_accent'),
                'body' => $pick($hero, 'body'),
            ],
            'welcome' => [
                'image' => self::path($welcome['image'] ?? null),
                'stats' => collect($welcome['stats'] ?? [])
                    ->filter(fn ($stat) => filled($stat['value'] ?? null))
                    ->map(fn ($stat) => ['value' => (string) $stat['value'], 'label' => $pick($stat, 'label')])
                    ->values()
                    ->all(),
            ],
            'experiences' => collect($raw['experiences'] ?? [])
                ->filter(fn ($item) => filled($item['title_en'] ?? null))
                ->map(fn ($item) => [
                    'image' => self::path($item['image'] ?? null),
                    'title' => $pick($item, 'title'),
                    'body' => $pick($item, 'body'),
                ])
                ->values()
                ->all(),
            'saint_martin' => [
                'image_primary' => self::path($saintMartin['image_primary'] ?? null),
                'image_secondary' => self::path($saintMartin['image_secondary'] ?? null),
            ],
            'final_cta' => [
                'image' => self::path($finalCta['image'] ?? null),
            ],
            'page_heroes' => collect(self::PAGE_HEROES)
                ->mapWithKeys(fn ($route) => [$route => self::path($pageHeroes[$route] ?? null)])
                ->all(),
        ];
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function path(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        return self::text($value);
    }
}
