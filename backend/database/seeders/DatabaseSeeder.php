<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\BlogPost;
use App\Models\ContentPage;
use App\Models\Faq;
use App\Models\GalleryCategory;
use App\Models\MealPlan;
use App\Models\NotificationTemplate;
use App\Models\Offer;
use App\Models\Room;
use App\Models\RoomAmenity;
use App\Models\RoomCategory;
use App\Models\RoomPrice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_bookings', 'create_booking', 'edit_booking', 'cancel_booking',
            'manage_rooms', 'manage_prices', 'manage_payments', 'process_refunds',
            'manage_content', 'manage_users', 'view_reports', 'manage_settings',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = Role::findOrCreate('owner');
        $manager = Role::findOrCreate('manager');
        $receptionist = Role::findOrCreate('receptionist');
        $owner->givePermissionTo($permissions);
        $manager->givePermissionTo([
            'view_bookings', 'create_booking', 'edit_booking', 'cancel_booking',
            'manage_rooms', 'manage_prices', 'manage_payments',
            'manage_content', 'view_reports',
        ]);
        $receptionist->givePermissionTo([
            'view_bookings', 'create_booking', 'edit_booking', 'manage_payments',
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'owner@stamarbariresort.com')],
            [
                'name' => 'Resort Owner',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'is_active' => true,
            ],
        );
        $admin->assignRole('owner');

        $settings = [
            'phone' => '+880 1839-425157',
            'whatsapp' => '+880 1839-425157',
            'email' => 'stamarbariresort@gmail.com',
            'address' => 'J8M9+46F, Poschim Para, Ctb Beach Road, Saint Martin Island, Bangladesh',
            'maps_url' => 'https://maps.app.goo.gl/u9phkKF6fxJyB74k6',
            'facebook_url' => 'https://www.facebook.com/stamarbari',
            'instagram_url' => '',
            'check_in_time' => '12:00',
            'check_out_time' => '10:00',
            'child_policy_age' => '7',
            'cancellation_hours' => '24',
            'cancellation_refund_percent' => '100',
            'missed_ship_refund' => '0',
            'advance_percent' => '30',
            'booking_hold_minutes' => '15',
            'extra_bed_available' => '1',
            'extra_bed_price' => '0',
            'timezone' => 'Asia/Dhaka',
            'google_reviews_url' => '',
            'tripadvisor_url' => '',
            'trade_license' => '',
            'tourism_registration' => '',
            'wifi_status' => 'Not currently available',
            'electricity_notes' => '',
            'network_notes' => '',
            'drinking_water_notes' => '',
            'emergency_medical' => '',
            'emergency_phone' => '',
            'coast_guard' => '',
            'local_emergency' => '',
            'resort_emergency' => '+880 1839-425157',
            'backup_retention_days' => '14',
        ];
        foreach ($settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        $garden = RoomAmenity::query()->updateOrCreate(['slug' => 'garden-view'], ['name_en' => 'Garden view', 'name_bn' => 'বাগানের দৃশ্য', 'is_available' => true]);
        $bath = RoomAmenity::query()->updateOrCreate(['slug' => 'attached-bathroom'], ['name_en' => 'Attached bathroom', 'name_bn' => 'সংযুক্ত বাথরুম', 'is_available' => true]);
        $hot = RoomAmenity::query()->updateOrCreate(['slug' => 'hot-water'], ['name_en' => 'Hot water', 'name_bn' => 'গরম পানি', 'is_available' => true]);
        RoomAmenity::query()->updateOrCreate(['slug' => 'ac'], ['name_en' => 'Air conditioning', 'name_bn' => 'এসি', 'is_available' => false]);
        RoomAmenity::query()->updateOrCreate(['slug' => 'wifi'], ['name_en' => 'Wi-Fi', 'name_bn' => 'ওয়াই-ফাই', 'is_available' => false]);
        RoomAmenity::query()->updateOrCreate(['slug' => 'tv'], ['name_en' => 'Television', 'name_bn' => 'টেলিভিশন', 'is_available' => false]);

        $deluxe = RoomCategory::query()->updateOrCreate(['slug' => 'deluxe-couple'], [
            'name_en' => 'Deluxe Couple',
            'name_bn' => 'ডিলাক্স কাপল',
            'description_en' => 'A calm couple room with a garden view, attached bathroom and hot water. Simple, comfortable, and close to the sea.',
            'description_bn' => 'বাগানের দৃশ্য, সংযুক্ত বাথরুম ও গরম পানিসহ একটি শান্ত কাপল রুম।',
            'max_adults' => 2,
            'max_children' => 1,
            'view_en' => 'Garden view',
            'view_bn' => 'বাগানের দৃশ্য',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $family = RoomCategory::query()->updateOrCreate(['slug' => 'family-room'], [
            'name_en' => 'Family Room',
            'name_bn' => 'ফ্যামিলি রুম',
            'description_en' => 'A spacious family room for up to four adults, with an attached bathroom, hot water and a garden view.',
            'description_bn' => 'চারজন প্রাপ্তবয়স্ক পর্যন্ত থাকার উপযোগী পরিবারিক রুম।',
            'max_adults' => 4,
            'max_children' => 2,
            'view_en' => 'Garden view',
            'view_bn' => 'বাগানের দৃশ্য',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $deluxe->amenities()->sync([$garden->id, $bath->id, $hot->id]);
        $family->amenities()->sync([$garden->id, $bath->id, $hot->id]);

        $twin = RoomCategory::query()->updateOrCreate(['slug' => 'garden-twin'], [
            'name_en' => 'Garden Twin',
            'name_bn' => 'গার্ডেন টুইন',
            'description_en' => 'A twin room with a garden view, attached bathroom and hot water. Two beds for friends or family travelling together.',
            'description_bn' => 'বাগানের দৃশ্য, সংযুক্ত বাথরুম ও গরম পানিসহ টুইন রুম।',
            'max_adults' => 2,
            'max_children' => 1,
            'view_en' => 'Garden view',
            'view_bn' => 'বাগানের দৃশ্য',
            'is_active' => true,
            'sort_order' => 3,
        ]);
        $twin->amenities()->sync([$garden->id, $bath->id, $hot->id]);

        foreach (['101', '102', '103', '104'] as $code) {
            Room::query()->updateOrCreate(['room_category_id' => $deluxe->id, 'code' => $code], ['name' => 'Room '.$code, 'is_active' => true]);
        }
        foreach (['201', '202'] as $code) {
            Room::query()->updateOrCreate(['room_category_id' => $family->id, 'code' => $code], ['name' => 'Room '.$code, 'is_active' => true]);
        }
        foreach (['301', '302'] as $code) {
            Room::query()->updateOrCreate(['room_category_id' => $twin->id, 'code' => $code], ['name' => 'Room '.$code, 'is_active' => true]);
        }

        if (app()->environment('testing') || env('SEED_DEMO_RATES', false)) {
            RoomPrice::query()->updateOrCreate(['room_category_id' => $deluxe->id, 'type' => 'base'], ['amount' => 4500, 'is_active' => true]);
            RoomPrice::query()->updateOrCreate(['room_category_id' => $family->id, 'type' => 'base'], ['amount' => 7500, 'is_active' => true]);
            RoomPrice::query()->updateOrCreate(['room_category_id' => $deluxe->id, 'type' => 'weekend'], ['amount' => 5000, 'is_active' => true]);
            RoomPrice::query()->updateOrCreate(['room_category_id' => $twin->id, 'type' => 'base'], ['amount' => 3500, 'is_active' => true]);
        }

        MealPlan::query()->updateOrCreate(['slug' => 'room-only'], [
            'name_en' => 'Room Only', 'name_bn' => 'শুধু রুম',
            'description_en' => 'Stay with no meals included.',
            'price_per_adult_per_night' => app()->environment('testing') ? 0 : 0,
            'is_active' => true, 'sort_order' => 1,
        ]);
        MealPlan::query()->updateOrCreate(['slug' => 'breakfast'], [
            'name_en' => 'Breakfast', 'name_bn' => 'নাস্তা',
            'description_en' => 'Room with breakfast.',
            'price_per_adult_per_night' => app()->environment('testing') ? 400 : 0,
            'price_per_child_per_night' => app()->environment('testing') ? 200 : 0,
            'is_active' => true, 'sort_order' => 2,
        ]);
        MealPlan::query()->updateOrCreate(['slug' => 'full-board'], [
            'name_en' => 'Full Board', 'name_bn' => 'ফুল বোর্ড',
            'description_en' => 'Room with applicable meals.',
            'price_per_adult_per_night' => app()->environment('testing') ? 1200 : 0,
            'price_per_child_per_night' => app()->environment('testing') ? 600 : 0,
            'is_active' => true, 'sort_order' => 3,
        ]);

        $addons = [
            ['pickup-ghat', 'Pickup from ship ghat', 'জাহাজ ঘাট থেকে পিকআপ', true],
            ['luggage', 'Luggage assistance', 'লাগেজ সহায়তা', false],
            ['bonfire', 'Bonfire', 'বনফায়ার', false],
            ['snorkeling', 'Snorkeling guide', 'স্নোরকেলিং গাইড', false],
            ['boat-transfer', 'Boat transfer', 'নৌকা ট্রান্সফার', false],
        ];
        foreach ($addons as $i => [$slug, $en, $bn, $pickup]) {
            Addon::query()->updateOrCreate(['slug' => $slug], [
                'name_en' => $en, 'name_bn' => $bn, 'is_pickup' => $pickup,
                'price' => app()->environment('testing') ? 500 : 0,
                'is_active' => true, 'sort_order' => $i + 1,
            ]);
        }

        foreach ([
            ['resort', 'Resort'], ['rooms', 'Rooms'], ['beach', 'Beach'],
            ['food', 'Food'], ['experience', 'Experience'], ['saint-martin', 'Saint Martin'],
        ] as $i => [$slug, $name]) {
            GalleryCategory::query()->updateOrCreate(['slug' => $slug], ['name_en' => $name, 'sort_order' => $i]);
        }

        $this->pages();
        $this->faqs();
        $this->offers();
        $this->blogPosts();

        NotificationTemplate::query()->updateOrCreate(['key' => 'booking_confirmed', 'channel' => 'whatsapp'], [
            'name' => 'Booking confirmed',
            'body' => 'Thank you, {{guest_name}}. Your stay at S T Amar Bari Resort is confirmed. Booking {{booking_number}}. Check-in {{check_in}}. Advance paid ৳{{amount_paid}}. Remaining ৳{{balance}}. Call {{resort_phone}}.',
        ]);
        NotificationTemplate::query()->updateOrCreate(['key' => 'abandoned_booking', 'channel' => 'whatsapp'], [
            'name' => 'Abandoned booking',
            'body' => 'Your booking at S T Amar Bari Resort is still incomplete. Your selected room may be released soon. Booking {{booking_number}}.',
        ]);
    }

    private function pages(): void
    {
        $pages = [
            ['about', 'About Us', "S T Amar Bari Resort is a peaceful place to stay on Saint Martin Island. Close to the sea, with warm hospitality and the feeling of home.\n\nAmar Bari means “My Home” in Bangla. Guests come for quiet rooms with garden views, attached bathrooms and hot water — and time to enjoy the island at its own pace.\n\nWe do not claim amenities we do not have. Air conditioning, Wi-Fi and television are not currently available. This page can be expanded from the admin whenever the family wishes to share more."],
            ['our-story', 'Our story', "Amar Bari means “My Home” in Bangla.\n\nA peaceful place to stay, close to the sea, where guests can experience Saint Martin with the warmth of home.\n\nWe do not tell a longer history than we can stand behind. This page can be expanded from the admin whenever the family wishes to share more."],
            ['experience', 'The island experience', 'Beach time, quiet sunsets, island walks, family moments and simple local food. Experiences listed here can be updated by the resort team as offerings change.'],
            ['house-rules', 'House rules', 'House rules are maintained by the resort team. Please treat this home, the garden and the island with care. Full rules can be updated from the admin panel.'],
            ['privacy-policy', 'Privacy policy', 'We collect only the guest details needed to complete a booking and to contact you about your stay. Payment credentials are never stored in this application.'],
            ['terms', 'Terms & conditions', 'Bookings are subject to room availability, the cancellation policy, and payment of the required advance. Remaining balance is payable at the resort unless otherwise agreed.'],
            ['cancellation-policy', 'Cancellation policy', "Default: cancellations made at least 1 day before check-in may receive a 100% refund of the advance, unless a different policy is published by the resort.\n\nIf a guest misses their ship: no refund.\n\nThe live policy is always the version stored in admin settings."],
            ['saint-martin', 'Saint Martin', 'Saint Martin is Bangladesh’s coral island — quiet beaches, clear water and a slower pace. Travel plans should always be checked close to your dates.'],
            ['saint-martin-travel-guide', 'Travel guide', "Typical route:\nDhaka → Cox's Bazar → Teknaf → boat/ship to Saint Martin → S T Amar Bari Resort.\n\nTransportation schedules change. Please confirm current ship and bus information before you travel."],
            ['saint-martin-tourist-spots', 'Tourist spots', 'Beaches, Chhera Dwip (when accessible), coral views and village walks. Details can be updated from the admin.'],
            ['saint-martin-chera-dwip', 'Chhera Dwip', 'Chhera Dwip is a nearby islet visited in season when weather and tides allow. Access is not guaranteed every day.'],
            ['saint-martin-weather', 'Weather', 'The island is typically warm and humid. Always check a current forecast before travelling. This page is informational, not a live weather feed.'],
            ['saint-martin-tides', 'Tides', 'Tides affect beach walks and some boat trips. Confirm local tide information close to your visit.'],
            ['saint-martin-eco-tourism', 'Eco tourism', 'Carry waste back where you can, avoid damaging coral, and keep the island as quiet and clean as you found it.'],
            ['saint-martin-what-to-pack', 'What to pack', "Comfortable clothes, sun protection, sunglasses, sandals, personal medication, a power bank, waterproof bags and identification.\nAdd other island essentials you personally need."],
            ['saint-martin-first-time-guide', 'First-time visitor guide', "Expect island time: weather, electricity, mobile network and cash access can all be limited.\nBring drinking water plans, respect beach safety, and follow eco-friendly behaviour. Check-in at the resort is typically 12:00 PM."],
            ['saint-martin-emergency-information', 'Emergency information', 'Nearest medical facility, Coast Guard and local emergency numbers should be filled in by the resort in Site Settings. Do not rely on placeholder numbers. The resort phone is +880 1839-425157.'],
            ['saint-martin-facilities', 'Resort facilities', "Available: garden view, attached bathroom, hot water.\nNot currently available: air conditioning, Wi-Fi, television.\nElectricity, mobile network and drinking water notes can be updated by the resort team."],
        ];

        foreach ($pages as [$slug, $title, $body]) {
            ContentPage::query()->updateOrCreate(['slug' => $slug], [
                'title_en' => $title,
                'body_en' => $body,
                'is_published' => true,
                'indexable' => true,
            ]);
        }
    }

    private function faqs(): void
    {
        $faqs = [
            ['What time is check-in?', 'Check-in is 12:00 PM and check-out is 10:00 AM, unless the resort publishes different times.'],
            ['Is there air conditioning?', 'Air conditioning is not currently available.'],
            ['Is there Wi-Fi?', 'Wi-Fi is not currently available.'],
            ['Do you have a television in the rooms?', 'Televisions are not currently available.'],
            ['How do I get to the resort?', 'Most guests travel Dhaka → Cox’s Bazar → Teknaf → boat/ship to Saint Martin. Please confirm current transport information before you travel.'],
            ['What if I miss the ship?', 'If a guest misses their ship, no refund is issued.'],
            ['Can I book for a group?', 'Yes. Please use the group inquiry form and the resort will respond directly.'],
        ];
        foreach ($faqs as $i => [$q, $a]) {
            Faq::query()->updateOrCreate(['question_en' => $q], ['answer_en' => $a, 'is_published' => true, 'sort_order' => $i]);
        }
    }

    private function offers(): void
    {
        $offers = [
            [
                'slug' => 'garden-stay',
                'name_en' => 'Garden stay',
                'name_bn' => 'বাগানের অবস্থান',
                'description_en' => 'A quiet garden-view stay with an attached bathroom and hot water. Air conditioning, Wi-Fi and television are not currently available.',
                'description_bn' => 'সংযুক্ত বাথরুম ও গরম পানিসহ শান্ত বাগান-দৃশ্যের অবস্থান। এয়ার কন্ডিশনার, ওয়াই-ফাই ও টেলিভিশন এখন নেই।',
            ],
            [
                'slug' => 'family-rooms',
                'name_en' => 'Family rooms',
                'name_bn' => 'পারিবারিক রুম',
                'description_en' => 'Family rooms sleep up to four adults. Extra mattress pricing, if offered, is set in admin — it is not assumed here.',
                'description_bn' => 'পারিবারিক রুমে চারজন প্রাপ্তবয়স্ক পর্যন্ত থাকতে পারেন। অতিরিক্ত গদি থাকলে তার দাম অ্যাডমিন থেকে নির্ধারিত হয়।',
            ],
            [
                'slug' => 'book-direct',
                'name_en' => 'Book direct',
                'name_bn' => 'সরাসরি বুকিং',
                'description_en' => 'Reserve on this site or WhatsApp. Rooms are held for 15 minutes while you pay the advance. No invented discount is applied until the resort sets one.',
                'description_bn' => 'এই সাইট বা হোয়াটসঅ্যাপে সংরক্ষণ করুন। অগ্রিম পরিশোধের সময় রুম ১৫ মিনিট ধরে রাখা হয়। রিসোর্ট ছাড় নির্ধারণ না করা পর্যন্ত কোনো কাল্পনিক ছাড় প্রযোজ্য নয়।',
            ],
        ];

        foreach ($offers as $offer) {
            Offer::query()->updateOrCreate(['slug' => $offer['slug']], [
                ...$offer,
                'kind' => 'promo',
                'discount_type' => 'percent',
                'discount_value' => 0,
                'is_active' => true,
            ]);
        }
    }

    private function blogPosts(): void
    {
        $posts = [
            [
                'slug' => 'garden-view-rooms',
                'title_en' => 'Garden-view rooms on the island',
                'title_bn' => 'দ্বীপে বাগান-দৃশ্যের রুম',
                'content_en' => "S T Amar Bari has garden-view rooms with attached bathrooms and hot water.\n\nDeluxe Couple and Garden Twin sleep two adults. The Family Room sleeps up to four. We do not list sea-view rooms, air conditioning, Wi-Fi or television that we do not have.",
                'content_bn' => "এস টি আমার বাড়িতে সংযুক্ত বাথরুম ও গরম পানিসহ বাগান-দৃশ্যের রুম আছে।\n\nডিলাক্স কাপল ও গার্ডেন টুইনে দুজন প্রাপ্তবয়স্ক থাকতে পারেন। ফ্যামিলি রুমে চারজন পর্যন্ত। যে সুবিধা নেই তা আমরা লিখি না।",
                'tags' => 'Rooms, Garden view',
            ],
            [
                'slug' => 'how-to-reach-saint-martin',
                'title_en' => 'How guests usually reach Saint Martin',
                'title_bn' => 'সেন্ট মার্টিনে সাধারণত কীভাবে আসেন',
                'content_en' => "Typical route: Dhaka → Cox's Bazar → Teknaf → boat or ship to Saint Martin → S T Amar Bari Resort on Poschim Para, CTB Beach Road.\n\nSchedules change. Confirm current bus and ship information before you travel. If a guest misses their ship, no refund is issued.",
                'content_bn' => "সাধারণ পথ: ঢাকা → কক্সবাজার → টেকনাফ → সেন্ট মার্টিনের নৌকা বা জাহাজ → পশ্চিম পাড়া, সিটিবি বিচ রোডে এস টি আমার বাড়ি।\n\nসময়সূচি বদলায়। যাত্রার আগে নিশ্চিত করুন। জাহাজ মিস করলে ফেরত দেওয়া হয় না।",
                'tags' => 'Travel, Saint Martin',
            ],
            [
                'slug' => 'what-to-expect',
                'title_en' => 'What to expect at Amar Bari',
                'title_bn' => 'আমার বাড়িতে কী আশা করবেন',
                'content_en' => "Check-in is 12:00 PM and check-out is 10:00 AM unless the resort publishes different times.\n\nAvailable: garden view, attached bathroom, hot water.\nNot currently available: air conditioning, Wi-Fi, television.\nElectricity, mobile network and drinking water notes can be updated by the family in admin.",
                'content_bn' => "চেক-ইন দুপুর ১২টা, চেক-আউট সকাল ১০টা — রিসোর্ট অন্য সময় প্রকাশ না করলে।\n\nআছে: বাগানের দৃশ্য, সংযুক্ত বাথরুম, গরম পানি।\nএখন নেই: এসি, ওয়াই-ফাই, টেলিভিশন।",
                'tags' => 'Stay, Facilities',
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::query()->updateOrCreate(['slug' => $post['slug']], [
                ...$post,
                'is_published' => true,
                'published_at' => now(),
            ]);
        }
    }
}
