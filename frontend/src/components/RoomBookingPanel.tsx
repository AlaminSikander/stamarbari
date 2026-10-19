"use client";

import { CalendarDays, Loader2, ShieldCheck } from "lucide-react";
import { useTranslations } from "next-intl";
import { useEffect, useMemo, useState } from "react";
import { apiPost, formatBdt, ApiRequestError } from "@/lib/api";
import { contact } from "@/lib/contact";
import type { AvailabilityResult, BookingPayload, BookingPolicies, Quote, RoomSummary } from "@/lib/types";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { Stepper } from "@/components/Stepper";

type AvailabilityState = {
  key: string;
  available: number | null;
  quote: Quote | null;
  message: string;
  failed: boolean;
};

function isoDate(offsetDays: number): string {
  const date = new Date();
  date.setDate(date.getDate() + offsetDays);
  return date.toISOString().slice(0, 10);
}

export function RoomBookingPanel({
  room,
  policies,
}: {
  room: RoomSummary;
  policies: BookingPolicies;
}) {
  const t = useTranslations("bookingPanel");
  const tSearch = useTranslations("search");
  const tRooms = useTranslations("rooms");
  const [checkIn, setCheckIn] = useState(isoDate(1));
  const [checkOut, setCheckOut] = useState(isoDate(2));
  const [roomsCount, setRoomsCount] = useState(1);
  const [adults, setAdults] = useState(1);
  const [children, setChildren] = useState(0);
  const [extraBeds, setExtraBeds] = useState(0);
  const [notes, setNotes] = useState("");
  const [guest, setGuest] = useState({ full_name: "", phone: "", email: "" });
  const [result, setResult] = useState<AvailabilityState | null>(null);
  const [actionMessage, setActionMessage] = useState("");
  const [paying, setPaying] = useState(false);

  const maxExtra = (policies.max_extra_beds_per_room ?? 2) * roomsCount;
  const extraAllowed = policies.extra_bed_available && (room.extra_bed_allowed ?? true);

  const searchKey = useMemo(
    () => `${checkIn}|${checkOut}|${roomsCount}|${adults}|${children}|${extraBeds}|${room.id}`,
    [checkIn, checkOut, roomsCount, adults, children, extraBeds, room.id],
  );
  const rangeError = !checkIn || !checkOut || checkOut <= checkIn;

  // A new search clears messages from the previous attempt.
  const [messageKey, setMessageKey] = useState(searchKey);
  if (messageKey !== searchKey) {
    setMessageKey(searchKey);
    setActionMessage("");
  }

  const current = !rangeError && result?.key === searchKey ? result : null;
  const status: "idle" | "loading" | "error" = rangeError ? "error" : !current ? "loading" : current.failed ? "error" : "idle";
  const available = rangeError ? null : (result?.available ?? null);
  const quote = rangeError || current?.failed ? null : (result?.quote ?? null);
  const message = actionMessage || (rangeError ? t("errOrder") : (current?.message ?? ""));
  useEffect(() => {
    if (rangeError) return;
    let active = true;
    apiPost<AvailabilityResult>("/availability", {
      check_in: checkIn,
      check_out: checkOut,
      adults,
      children,
      room_category_id: room.id,
      rooms_count: roomsCount,
      extra_beds: extraBeds,
    })
      .then((data) => {
        if (!active) return;
        const match = data.rooms.find((item) => item.id === room.id);
        setResult({
          key: searchKey,
          available: match?.available_rooms ?? 0,
          quote: match?.quote ?? null,
          message: !match || (match.available_rooms ?? 0) < roomsCount ? t("errNone") : "",
          failed: false,
        });
      })
      .catch((error: unknown) => {
        if (!active) return;
        setResult((previous) => ({
          key: searchKey,
          available: previous?.available ?? null,
          quote: null,
          message: error instanceof ApiRequestError ? error.message : t("errCheck"),
          failed: true,
        }));
      });
    return () => {
      active = false;
    };
  }, [searchKey, rangeError, checkIn, checkOut, adults, children, extraBeds, room.id, roomsCount, t]);

  async function onBook() {
    if (!guest.full_name || !guest.phone) {
      setActionMessage(t("errGuest"));
      return;
    }
    if (!quote || (available ?? 0) < roomsCount) {
      setActionMessage(t("errGone"));
      return;
    }
    setPaying(true);
    setActionMessage("");
    try {
      const created = await apiPost<BookingPayload>("/bookings", {
        check_in: checkIn,
        check_out: checkOut,
        adults,
        children,
        room_category_id: room.id,
        rooms_count: roomsCount,
        extra_beds: extraBeds,
        extra_bed: extraBeds > 0,
        special_requests: notes || undefined,
        guest,
      });
      const payment = await apiPost<{ payment_url: string | null }>(
        `/bookings/${created.booking_number}/payments`,
        {},
      );
      if (payment.payment_url) {
        window.location.href = payment.payment_url;
        return;
      }
      setActionMessage(t("errPayment"));
    } catch (error: unknown) {
      setActionMessage(error instanceof ApiRequestError ? error.message : t("errGeneric"));
    } finally {
      setPaying(false);
    }
  }

  const nights = quote?.nights ?? 1;
  const nightly = quote?.nightly_rate ?? room.starting_price ?? 0;
  const extraLine = extraBeds > 0 ? extraBeds * policies.extra_bed_price * nights : 0;
  const whatsappText = t("whatsappText", { room: room.name, checkIn, checkOut });

  return (
    <aside className="overflow-hidden rounded-[28px] border border-line bg-white shadow-[0_40px_80px_-50px_rgba(10,34,54,0.45)]">
      <div className="relative bg-navy-deep px-6 py-6 text-white md:px-7">
        <p className="eyebrow text-[#e4c891]">{t("title")}</p>
        <p className="mt-3 font-display text-[2rem] font-light leading-none">
          {room.starting_price ? tRooms("fromShort", { price: formatBdt(room.starting_price) }) : tRooms("noRate")}
        </p>
        <span aria-hidden className="absolute bottom-0 left-6 h-px w-12 bg-gold md:left-7" />
      </div>

      <div className="space-y-6 p-6 md:p-7">
        <div className="grid grid-cols-2 gap-3">
          <label className="block">
            <span className="field-label inline-flex items-center gap-2">
              <CalendarDays aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
              {tSearch("checkIn")}
            </span>
            <input
              type="date"
              className="field"
              value={checkIn}
              min={isoDate(0)}
              onChange={(e) => setCheckIn(e.target.value)}
            />
          </label>
          <label className="block">
            <span className="field-label inline-flex items-center gap-2">
              <CalendarDays aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
              {tSearch("checkOut")}
            </span>
            <input
              type="date"
              className="field"
              value={checkOut}
              min={checkIn || isoDate(1)}
              onChange={(e) => setCheckOut(e.target.value)}
            />
          </label>
        </div>

        <div>
          <p className="field-label">{t("rooms")}</p>
          <div className="mt-2">
            <Stepper
              label={t("roomsLabel")}
              value={roomsCount}
              min={1}
              max={Math.max(1, available ?? room.inventory ?? 8)}
              onChange={setRoomsCount}
            />
          </div>
          <p className="mt-2 flex min-h-5 items-center gap-2 text-xs text-muted" aria-live="polite">
            {status === "loading" ? (
              <>
                <Loader2 aria-hidden className="h-3.5 w-3.5 animate-spin text-gold" />
                {t("checking")}
              </>
            ) : available === null ? (
              ""
            ) : (
              t("available", { count: available })
            )}
          </p>
        </div>

        <div>
          <p className="field-label">{t("guests", { max: room.max_adults })}</p>
          <div className="mt-2 grid grid-cols-2 gap-3">
            <div>
              <p className="mb-1.5 text-xs text-muted">{tSearch("adults")}</p>
              <Stepper label={t("adultsLabel")} value={adults} min={1} max={room.max_adults} onChange={setAdults} />
            </div>
            <div>
              <p className="mb-1.5 text-xs text-muted">{tSearch("children")}</p>
              <Stepper label={t("childrenLabel")} value={children} min={0} max={room.max_children} onChange={setChildren} />
            </div>
          </div>
        </div>

        {extraAllowed ? (
          <div>
            <p className="field-label">
              {t("extra")} {policies.extra_bed_price > 0 ? t("extraPrice", { price: formatBdt(policies.extra_bed_price) }) : ""}
            </p>
            <div className="mt-2">
              <Stepper label={t("extraLabel")} value={extraBeds} min={0} max={maxExtra} onChange={setExtraBeds} />
            </div>
            <p className="mt-2 text-xs text-muted">{t("extraMax", { max: maxExtra, count: roomsCount })}</p>
          </div>
        ) : null}

        <label className="block">
          <span className="field-label">{t("notes")}</span>
          <textarea
            className="field min-h-24 resize-y"
            placeholder={t("notesPlaceholder")}
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
          />
        </label>

        <fieldset className="space-y-4 border-t border-line pt-6">
          <legend className="sr-only">{t("guestDetails")}</legend>
          <p aria-hidden className="font-display text-xl text-navy-deep">
            {t("guestDetails")}
          </p>
          <label className="block">
            <span className="field-label">{t("fullName")}</span>
            <input
              className="field"
              autoComplete="name"
              value={guest.full_name}
              onChange={(e) => setGuest({ ...guest, full_name: e.target.value })}
              required
            />
          </label>
          <label className="block">
            <span className="field-label">{t("phone")}</span>
            <input
              className="field"
              type="tel"
              autoComplete="tel"
              value={guest.phone}
              onChange={(e) => setGuest({ ...guest, phone: e.target.value })}
              required
            />
          </label>
          <label className="block">
            <span className="field-label">
              {t("email")} <span className="normal-case tracking-normal text-muted/70">{t("optional")}</span>
            </span>
            <input
              type="email"
              autoComplete="email"
              className="field"
              value={guest.email}
              onChange={(e) => setGuest({ ...guest, email: e.target.value })}
            />
          </label>
        </fieldset>

        {quote ? (
          <div className="space-y-2.5 rounded-[20px] bg-ivory p-5 text-sm text-navy/80">
            <div className="flex justify-between gap-4">
              <span>{t("lineRooms", { price: formatBdt(nightly), rooms: roomsCount, nights })}</span>
              <span className="tabular-nums">{formatBdt(quote.room_subtotal)}</span>
            </div>
            {extraLine > 0 ? (
              <div className="flex justify-between gap-4">
                <span>{t("extraLine")}</span>
                <span className="tabular-nums">{formatBdt(quote.extra_bed_total)}</span>
              </div>
            ) : null}
            <div className="flex justify-between gap-4">
              <span>{t("subtotal")}</span>
              <span className="tabular-nums">{formatBdt(quote.room_subtotal + quote.extra_bed_total)}</span>
            </div>
            <div className="flex justify-between gap-4 text-navy-deep">
              <span>{t("advance", { percent: quote.advance_percent })}</span>
              <span className="tabular-nums font-medium">{formatBdt(quote.advance_amount)}</span>
            </div>
            <div className="flex justify-between gap-4">
              <span>{t("remaining")}</span>
              <span className="tabular-nums">{formatBdt(quote.balance_amount)}</span>
            </div>
            <div className="flex items-baseline justify-between gap-4 border-t border-line pt-3 text-navy-deep">
              <span className="field-label text-navy-deep">{t("total")}</span>
              <span className="font-display text-2xl tabular-nums">{formatBdt(quote.grand_total)}</span>
            </div>
            <p className="pt-1 text-xs leading-relaxed text-muted">{t("priceNote")}</p>
          </div>
        ) : null}

        {message ? (
          <p role="alert" className="rounded-2xl border border-muted-red/25 bg-muted-red/5 px-4 py-3 text-sm text-muted-red">
            {message}
          </p>
        ) : null}

        <div className="space-y-3">
          <button
            type="button"
            disabled={paying || status === "loading" || !quote}
            onClick={onBook}
            className="group btn-shine relative inline-flex min-h-14 w-full items-center justify-center gap-3 overflow-hidden rounded-full bg-navy-deep text-[0.76rem] font-medium uppercase tracking-[0.18em] text-white transition-colors duration-500 hover:text-navy-deep disabled:pointer-events-none disabled:opacity-45"
          >
            <span aria-hidden className="absolute inset-0 origin-left scale-x-0 rounded-full bg-gold transition-transform duration-500 ease-lux group-hover:scale-x-100" />
            {paying ? <Loader2 aria-hidden className="relative h-4 w-4 animate-spin" /> : null}
            <span className="relative">{paying ? t("starting") : t("book")}</span>
          </button>
          <a
            href={`${contact.whatsapp}?text=${encodeURIComponent(whatsappText)}`}
            target="_blank"
            rel="noreferrer"
            className="inline-flex min-h-12 w-full items-center justify-center gap-2.5 rounded-full border border-line text-[0.72rem] font-medium uppercase tracking-[0.16em] text-navy transition-colors duration-500 hover:border-[#1f6f5c] hover:bg-[#1f6f5c] hover:text-white"
          >
            <WhatsAppIcon className="h-4 w-4" />
            {t("whatsapp")}
          </a>
          <p className="flex items-start gap-2 pt-1 text-xs leading-relaxed text-muted">
            <ShieldCheck aria-hidden className="mt-0.5 h-4 w-4 shrink-0 text-gold" strokeWidth={1.5} />
            {t("secure")}
          </p>
        </div>
      </div>
    </aside>
  );
}
