import { apiPost } from "./api";
import type { BookingPayload } from "./types";

const SUBMITTED_KEY = "stamarbari:booking-submitted";

/** Random key sent with a booking so retries and double clicks reuse the same saved booking. */
export function newRequestKey(): string {
  // randomUUID only exists on secure origins (HTTPS or localhost).
  if (typeof crypto.randomUUID === "function") return crypto.randomUUID();
  const bytes = new Uint8Array(16);
  crypto.getRandomValues(bytes);
  return Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("");
}

export type BookingRequestBody = {
  check_in: string;
  check_out: string;
  adults: number;
  children: number;
  rooms?: { room_category_id: number; quantity: number }[];
  room_category_id?: number;
  rooms_count?: number;
  special_requests?: string;
  locale?: string;
  guest: { full_name: string; phone: string; email?: string };
};

export function submitBooking(body: BookingRequestBody, requestKey: string): Promise<BookingPayload> {
  return apiPost<BookingPayload>("/bookings", { ...body, request_key: requestKey });
}

/** Success-page URL for a saved booking. The token lets that page read the booking back. */
export function successPath(booking: Pick<BookingPayload, "booking_number" | "access_token">): string {
  return `/booking/success?${new URLSearchParams({ booking: booking.booking_number, token: booking.access_token })}`;
}

/** Marks a booking as just submitted so the success page shows the confirmation toast once. */
export function markSubmitted(bookingNumber: string): void {
  try {
    window.sessionStorage.setItem(SUBMITTED_KEY, bookingNumber);
  } catch {
    // Storage can be unavailable (private mode); the success page still works without the toast.
  }
}

export function takeSubmitted(bookingNumber: string): boolean {
  try {
    if (window.sessionStorage.getItem(SUBMITTED_KEY) !== bookingNumber) return false;
    window.sessionStorage.removeItem(SUBMITTED_KEY);
    return true;
  } catch {
    return false;
  }
}

/**
 * The resort's WhatsApp number from NEXT_PUBLIC_WHATSAPP_NUMBER, as digits in
 * international format (country code first, no "+", no leading 0). Null when unset or invalid.
 */
export function resortWhatsAppNumber(value: string | undefined = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER): string | null {
  const digits = (value ?? "").trim().replace(/^\+/, "").replace(/^00/, "").replace(/[\s\-()]/g, "");
  return /^[1-9]\d{7,14}$/.test(digits) ? digits : null;
}

/** Click-to-chat link that opens WhatsApp (app or web) with the message filled in for the guest to send. */
export function whatsAppChatUrl(number: string, message: string): string {
  return `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
}
