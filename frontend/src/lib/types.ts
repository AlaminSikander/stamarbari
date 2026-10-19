export type RoomSummary = {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  max_adults: number;
  max_children: number;
  view: string | null;
  tour_360_url: string | null;
  featured_image: string | null;
  starting_price: number | null;
  currency: string;
  amenities: { slug: string; name: string }[];
  images: { path: string; alt: string | null }[];
  extra_bed_allowed?: boolean;
  inventory?: number;
};

export type Offer = {
  id: number;
  slug: string;
  name_en: string;
  name_bn?: string | null;
  description_en?: string | null;
  description_bn?: string | null;
  discount_type?: string;
  discount_value?: number;
  image?: string | null;
};

export type Testimonial = {
  id: number;
  guest_name: string;
  quote_en: string;
  quote_bn?: string | null;
};

export type AvailabilityResult = {
  check_in: string;
  check_out: string;
  rooms: {
    id: number;
    slug: string;
    name: string;
    max_adults: number;
    max_children?: number;
    available_rooms: number;
    fits_guests: boolean;
    quote: Quote | null;
  }[];
};

export type Quote = {
  nights: number;
  nightly_rate?: number;
  room_count?: number;
  extra_beds?: number;
  room_subtotal: number;
  meal_total: number;
  addon_total: number;
  extra_bed_total: number;
  discount_total: number;
  grand_total: number;
  advance_percent: number;
  advance_amount: number;
  balance_amount: number;
  currency: string;
};

export type BookingPayload = {
  booking_number: string;
  status: string;
  check_in: string;
  check_out: string;
  adults: number;
  children: number;
  room: string | null;
  meal_plan: string | null;
  guest: { full_name: string; phone: string; email?: string | null } | null;
  grand_total: number;
  advance_amount: number;
  amount_paid: number;
  balance_amount: number;
  hold_expires_at: string | null;
  price_breakdown: Quote | null;
  invoice_available: boolean;
};

export type ApiSuccess<T> = {
  success: true;
  message: string;
  data: T;
};

export type ApiError = {
  success: false;
  message: string;
  errors: Record<string, string[]> | object;
};

export type BookingPolicies = {
  check_in_time: string;
  check_out_time: string;
  child_policy_age: number;
  cancellation_hours: number;
  cancellation_refund_percent: number;
  missed_ship_refund: boolean;
  advance_percent: number;
  hold_minutes: number;
  extra_bed_available: boolean;
  extra_bed_price: number;
  max_extra_beds_per_room: number;
  missed_ship_policy: string;
};
