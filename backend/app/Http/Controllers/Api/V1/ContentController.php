<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\BlogPost;
use App\Models\ContentPage;
use App\Models\Faq;
use App\Models\GalleryCategory;
use App\Models\Offer;
use App\Models\Package;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\ApiResponse;
use App\Support\HomepageContent;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function page(string $slug, Request $request)
    {
        $locale = $request->query('locale', 'en');
        $page = ContentPage::query()->published()->where('slug', $slug)->firstOrFail();

        return ApiResponse::success([
            'slug' => $page->slug,
            'title' => $locale === 'bn' && $page->title_bn ? $page->title_bn : $page->title_en,
            'body' => $locale === 'bn' && $page->body_bn ? $page->body_bn : $page->body_en,
            'excerpt' => $locale === 'bn' && $page->excerpt_bn ? $page->excerpt_bn : $page->excerpt_en,
            'hero_image' => $page->hero_image,
            'seo' => [
                'title' => $page->seo_title,
                'description' => $page->meta_description,
                'og_title' => $page->og_title,
                'og_description' => $page->og_description,
                'og_image' => $page->og_image,
                'canonical' => $page->canonical_url,
                'indexable' => $page->indexable,
                'schema_type' => $page->schema_type,
            ],
        ]);
    }

    public function gallery()
    {
        $categories = GalleryCategory::query()->with(['items' => fn ($q) => $q->where('is_published', true)])->orderBy('sort_order')->get();

        return ApiResponse::success($categories);
    }

    public function faqs()
    {
        return ApiResponse::success(Faq::query()->where('is_published', true)->orderBy('sort_order')->get());
    }

    public function blog(Request $request)
    {
        $posts = BlogPost::query()->published()->latest('published_at')->paginate(12);

        return ApiResponse::success($posts);
    }

    public function blogShow(string $slug)
    {
        $post = BlogPost::query()->published()->where('slug', $slug)->firstOrFail();

        return ApiResponse::success($post);
    }

    public function offers()
    {
        return ApiResponse::success(Offer::query()->where('is_active', true)->latest()->get());
    }

    public function packages()
    {
        return ApiResponse::success(Package::query()->where('is_active', true)->latest()->get());
    }

    public function reviews()
    {
        return ApiResponse::success(Review::query()->where('is_approved', true)->latest()->get());
    }

    public function testimonials()
    {
        return ApiResponse::success(Testimonial::query()->where('is_published', true)->latest()->get());
    }

    public function announcements(Request $request)
    {
        $query = Announcement::query()->live();
        if ($request->boolean('booking')) {
            $query->where('show_on_booking', true);
        }

        return ApiResponse::success($query->orderByDesc('id')->get());
    }

    public function site()
    {
        $keys = [
            'phone', 'whatsapp', 'email', 'address', 'maps_url', 'facebook_url', 'instagram_url',
            'check_in_time', 'check_out_time', 'google_reviews_url', 'tripadvisor_url',
            'trade_license', 'tourism_registration', 'ga4_id', 'meta_pixel_id',
            'electricity_notes', 'network_notes', 'drinking_water_notes', 'wifi_status',
            'emergency_medical', 'emergency_phone', 'coast_guard', 'local_emergency', 'resort_emergency',
        ];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = Setting::getValue($key);
        }

        return ApiResponse::success($data);
    }

    public function homepage(Request $request)
    {
        $locale = $request->query('locale') === 'bn' ? 'bn' : 'en';

        return ApiResponse::success(HomepageContent::forLocale($locale));
    }
}
