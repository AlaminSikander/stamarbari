"use client";

import { CalendarDays, Check, Loader2, Plus, ShieldCheck, Users, X } from "lucide-react";
import Image from "next/image";
import { useSearchParams } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { useEffect, useRef, useState, type ReactNode } from "react";
import { Stepper } from "@/components/Stepper";
import { useRouter } from "@/i18n/navigation";
import { ApiRequestError, apiGet, apiPost, formatBdt } from "@/lib/api";
import { markSubmitted, newRequestKey, submitBooking, successPath } from "@/lib/booking";
import { roomImage } from "@/lib/rooms";
import type { AvailabilityResult, BookingPolicies, Quote, QuoteItem, RoomSummary, RoomType } from "@/lib/types";

/** Room category id → number of rooms. */
type Selection = Record<number, number>;

const MAX_ROOMS = 20;

function localDate(offsetDays: number): string {
  const date = new Date();
  date.setDate(date.getDate() + offsetDays);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function clampInt(value: string | null, min: number, max: number, fallback: number): number {
  const n = Number.parseInt(value ?? "", 10);
  return Number.isFinite(n) ? Math.min(max, Math.max(min, n)) : fallback;
}

function parseSelection(value: string | null): Selection {
  const selection: Selection = {};
  for (const part of (value ?? "").split(",")) {
    const [id, qty] = part.split(":").map((v) => Number.parseInt(v, 10));
    if (id > 0 && qty > 0) selection[id] = Math.min(MAX_ROOMS, qty);
  }
  return selection;
}

function serializeSelection(selection: Selection): string {
  return Object.entries(selection)
    .filter(([, qty]) => qty > 0)
    .map(([id, qty]) => `${id}:${qty}`)
    .join(",");
}

function toRooms(key: string) {
  return Object.entries(parseSelection(key)).map(([id, quantity]) => ({ room_category_id: Number(id), quantity }));
}

function errorAvailability(error: unknown): Record<number, number> | null {
  if (!(error instanceof ApiRequestError)) return null;
  const errors = error.body?.errors as { availability?: Record<string, number> } | undefined;
  if (!errors?.availability) return null;
  return Object.fromEntries(Object.entries(errors.availability).map(([id, count]) => [Number(id), count]));
}

export function BookingPageClient() {
  const t = useTranslations("multiBooking");
  const tPanel = useTranslations("bookingPanel");
  const tSearch = useTranslations("search");
  const tRooms = useTranslations("rooms");
  const tRequest = useTranslations("bookingRequest");
  const locale = useLocale();
  const params = useSearchParams();
  const router = useRouter();

  const [checkIn, setCheckIn] = useState(() => params.get("check_in") ?? localDate(1));
  const [checkOut, setCheckOut] = useState(() => params.get("check_out") ?? localDate(2));
  const [adults, setAdults] = useState(() => clampInt(params.get("adults"), 1, 40, 2));
  const [children, setChildren] = useState(() => clampInt(params.get("children"), 0, 40, 0));
  const [selection, setSelection] = useState<Selection>(() => {
    const fromUrl = parseSelection(params.get("rooms"));
    const single = Number(params.get("room_category_id"));
    return Object.keys(fromUrl).length || !single ? fromUrl : { [single]: 1 };
  });
  const [filter, setFilter] = useState<"all" | RoomType>("all");
  const [rooms, setRooms] = useState<RoomSummary[] | null>(null);
  const [loadError, setLoadError] = useState("");
  const [policies, setPolicies] = useState<BookingPolicies | null>(null);
  const [availability, setAvailability] = useState<{ key: string; units: Record<number, number>; failed: boolean } | null>(null);
  const [quoteResult, setQuoteResult] = useState<{ key: string; quote: Quote | null; error: string } | null>(null);
  const [guest, setGuest] = useState({ full_name: "", phone: "", email: "" });
  const [notes, setNotes] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const inFlight = useRef(false);
  const requestKey = useRef<{ attempt: string; key: string } | null>(null);

  const rangeError = !checkIn || !checkOut || checkOut <= checkIn;
  const stayKey = `${checkIn}|${checkOut}`;
  const selectionKey = serializeSelection(selection);
  const quoteKey = `${stayKey}|${adults}|${children}|${selectionKey}`;

  useEffect(() => {
    const slug = params.get("room");
    apiGet<RoomSummary[]>("/rooms", locale)
      .then((list) => {
        setRooms(list);
        const match = slug ? list.find((room) => room.slug === slug) : null;
        if (match) setSelection((current) => (Object.keys(current).length ? current : { [match.id]: 1 }));
      })
      .catch((error: unknown) => setLoadError(error instanceof ApiRequestError ? error.message : t("errLoad")));
    apiGet<BookingPolicies>("/bookings/policies", locale).then(setPolicies).catch(() => undefined);
    // Load once; later URL updates come from this page itself.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (rangeError) return;
    let active = true;
    apiPost<AvailabilityResult>("/availability", { check_in: checkIn, check_out: checkOut, adults: 1, children: 0 })
      .then((data) => {
        if (!active) return;
        setAvailability({ key: stayKey, units: Object.fromEntries(data.rooms.map((room) => [room.id, room.units_available])), failed: false });
      })
      .catch(() => {
        if (active) setAvailability({ key: stayKey, units: {}, failed: true });
      });
    return () => {
      active = false;
    };
  }, [stayKey, rangeError, checkIn, checkOut]);

  useEffect(() => {
    if (rangeError || !selectionKey) return;
    let active = true;
    const timer = window.setTimeout(() => {
      apiPost<Quote>("/bookings/quote", {
        check_in: checkIn,
        check_out: checkOut,
        adults,
        children,
        rooms: toRooms(selectionKey),
        locale,
      })
        .then((quote) => {
          if (active) setQuoteResult({ key: quoteKey, quote, error: "" });
        })
        .catch((error: unknown) => {
          if (!active) return;
          const units = errorAvailability(error);
          if (units) setAvailability((prev) => (prev && prev.key === stayKey ? { ...prev, units: { ...prev.units, ...units } } : prev));
          setQuoteResult({ key: quoteKey, quote: null, error: error instanceof ApiRequestError ? error.message : t("errQuote") });
        });
    }, 250);
    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [quoteKey, rangeError, selectionKey, checkIn, checkOut, adults, children, stayKey, locale, t]);

  useEffect(() => {
    const url = new URL(window.location.href);
    url.searchParams.set("check_in", checkIn);
    url.searchParams.set("check_out", checkOut);
    url.searchParams.set("adults", String(adults));
    url.searchParams.set("children", String(children));
    if (selectionKey) url.searchParams.set("rooms", selectionKey);
    else url.searchParams.delete("rooms");
    url.searchParams.delete("room_category_id");
    url.searchParams.delete("room");
    window.history.replaceState(window.history.state, "", url);
  }, [checkIn, checkOut, adults, children, selectionKey]);

  const units = !rangeError && availability?.key === stayKey ? availability.units : null;
  const availabilityFailed = !rangeError && availability?.key === stayKey && availability.failed;
  const current = quoteResult?.key === quoteKey ? quoteResult : null;
  const quote = selectionKey && !rangeError ? (current?.quote ?? null) : null;
  const quoteError = selectionKey && !rangeError ? (current?.error ?? "") : "";
  const quoting = Boolean(selectionKey) && !rangeError && !current;

  const byId = new Map((rooms ?? []).map((room) => [room.id, room]));
  const selected = Object.entries(selection)
    .map(([id, quantity]) => ({ room: byId.get(Number(id)), quantity }))
    .filter((entry): entry is { room: RoomSummary; quantity: number } => Boolean(entry.room) && entry.quantity > 0);
  const roomCount = selected.reduce((sum, entry) => sum + entry.quantity, 0);
  const capacity = selected.reduce(
    (sum, entry) => ({ adults: sum.adults + entry.room.max_adults * entry.quantity, children: sum.children + entry.room.max_children * entry.quantity }),
    { adults: 0, children: 0 },
  );
  const items = new Map((quote?.items ?? []).map((item) => [item.room_category_id, item]));
  const types = new Map<string, string>();
  for (const room of rooms ?? []) {
    if (room.room_type && room.room_type_label) types.set(room.room_type, room.room_type_label);
  }
  const visible = (rooms ?? []).filter((room) => filter === "all" || room.room_type === filter);

  function setQuantity(id: number, quantity: number) {
    setSubmitError("");
    setSelection((prev) => {
      const next = { ...prev };
      if (quantity <= 0) delete next[id];
      else next[id] = Math.min(MAX_ROOMS, quantity);
      return next;
    });
  }

  async function onProceed() {
    if (inFlight.current) return;
    if (!guest.full_name.trim() || !guest.phone.trim()) {
      setSubmitError(t("errGuest"));
      return;
    }
    if (!quote) return;
    inFlight.current = true;
    setSubmitting(true);
    setSubmitError("");
    // The same stay and phone reuse one request key, so a retry returns the booking already saved.
    const attempt = `${quoteKey}|${guest.phone.trim()}`;
    if (requestKey.current?.attempt !== attempt) requestKey.current = { attempt, key: newRequestKey() };
    let leaving = false;
    try {
      const created = await submitBooking(
        {
          check_in: checkIn,
          check_out: checkOut,
          adults,
          children,
          rooms: toRooms(selectionKey),
          special_requests: notes.trim() || undefined,
          locale,
          guest: { full_name: guest.full_name.trim(), phone: guest.phone.trim(), email: guest.email.trim() || undefined },
        },
        requestKey.current.key,
      );
      if (created.payment_required) {
        const payment = await apiPost<{ payment_url: string | null }>(`/bookings/${created.booking_number}/payments`, {});
        if (payment.payment_url) {
          leaving = true;
          window.location.href = payment.payment_url;
          return;
        }
        setSubmitError(tPanel("errPayment"));
        return;
      }
      leaving = true;
      markSubmitted(created.booking_number);
      router.push(successPath(created));
    } catch (error: unknown) {
      const unitsFromError = errorAvailability(error);
      if (unitsFromError) setAvailability((prev) => (prev ? { ...prev, units: { ...prev.units, ...unitsFromError } } : prev));
      setSubmitError(error instanceof ApiRequestError ? error.message : tPanel("errGeneric"));
    } finally {
      if (!leaving) {
        inFlight.current = false;
        setSubmitting(false);
      }
    }
  }

  if (loadError) {
    return (
      <Sheet className="py-24">
        <p role="alert" className="mx-auto max-w-xl rounded-[28px] border border-muted-red/25 bg-white p-10 text-center text-muted-red">
          {loadError}
        </p>
      </Sheet>
    );
  }

  const message = submitError || quoteError;
  const paymentsOn = policies?.online_payment_enabled ?? false;

  return (
    <Sheet className="pb-40 pt-12 md:pt-16 lg:pb-28">
      <div className="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_400px] lg:gap-14">
        <div className="min-w-0 space-y-12">
          <section aria-labelledby="stay-heading" className="rounded-[28px] border border-line bg-white p-6 md:p-8">
            <p className="eyebrow text-gold">{t("stay")}</p>
            <h2 id="stay-heading" className="sr-only">
              {t("stay")}
            </h2>
            <p className="mt-3 text-sm text-muted">{t("stayHint")}</p>
            <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <label className="block">
                <span className="field-label inline-flex items-center gap-2">
                  <CalendarDays aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
                  {tSearch("checkIn")}
                </span>
                <input type="date" className="field" value={checkIn} min={localDate(0)} onChange={(e) => setCheckIn(e.target.value)} />
              </label>
              <label className="block">
                <span className="field-label inline-flex items-center gap-2">
                  <CalendarDays aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
                  {tSearch("checkOut")}
                </span>
                <input type="date" className="field" value={checkOut} min={checkIn || localDate(1)} onChange={(e) => setCheckOut(e.target.value)} />
              </label>
              <div>
                <p className="field-label">{tSearch("adults")}</p>
                <div className="mt-2">
                  <Stepper label={tPanel("adultsLabel")} value={adults} min={1} max={40} onChange={setAdults} />
                </div>
              </div>
              <div>
                <p className="field-label">{tSearch("children")}</p>
                <div className="mt-2">
                  <Stepper label={tPanel("childrenLabel")} value={children} min={0} max={40} onChange={setChildren} />
                </div>
              </div>
            </div>
            {rangeError ? (
              <p role="alert" className="mt-4 text-sm text-muted-red">
                {t("errDates")}
              </p>
            ) : null}
          </section>

          <section aria-labelledby="rooms-heading">
            <div className="flex flex-wrap items-end justify-between gap-4">
              <div>
                <h2 id="rooms-heading" className="display-md text-navy-deep">
                  {t("chooseRooms")}
                </h2>
                <p className="mt-2 text-sm text-muted">{t("chooseRoomsHint")}</p>
              </div>
              {roomCount ? (
                <span className="rounded-full bg-navy-deep px-4 py-2 text-[0.7rem] font-medium uppercase tracking-[0.14em] text-white" aria-live="polite">
                  {t("selectedCount", { count: roomCount })}
                </span>
              ) : null}
            </div>

            {types.size > 1 ? (
              <div role="group" aria-label={t("filterLabel")} className="mt-6 flex flex-wrap gap-2">
                {[{ id: "all", label: t("filterAll") }, ...Array.from(types, ([id, label]) => ({ id, label }))].map((option) => (
                  <button
                    key={option.id}
                    type="button"
                    aria-pressed={filter === option.id}
                    onClick={() => setFilter(option.id as "all" | RoomType)}
                    className={`min-h-10 rounded-full border px-5 text-[0.7rem] font-medium uppercase tracking-[0.14em] transition-colors ${
                      filter === option.id ? "border-navy-deep bg-navy-deep text-white" : "border-line bg-white text-navy hover:border-navy-deep"
                    }`}
                  >
                    {option.label}
                  </button>
                ))}
              </div>
            ) : null}

            {availabilityFailed ? (
              <p role="alert" className="mt-6 rounded-2xl border border-muted-red/25 bg-muted-red/5 px-4 py-3 text-sm text-muted-red">
                {t("errAvailability")}
              </p>
            ) : null}

            {rooms === null ? (
              <div className="mt-8 space-y-4">
                {[0, 1, 2].map((i) => (
                  <div key={i} className="skeleton h-36 rounded-[24px]" />
                ))}
              </div>
            ) : visible.length === 0 ? (
              <p className="mt-8 rounded-[24px] border border-line bg-white p-8 text-navy/70">{t("noneMatch")}</p>
            ) : (
              <ul className="mt-8 space-y-4">
                {visible.map((room) => (
                  <RoomOption
                    key={room.id}
                    room={room}
                    quantity={selection[room.id] ?? 0}
                    free={units ? (units[room.id] ?? 0) : null}
                    checking={!rangeError && !units && !availabilityFailed}
                    onChange={(quantity) => setQuantity(room.id, quantity)}
                  />
                ))}
              </ul>
            )}
          </section>
        </div>

        <aside id="booking-summary" aria-labelledby="summary-heading" className="scroll-mt-28 lg:sticky lg:top-28">
          <div className="overflow-hidden rounded-[28px] border border-line bg-white shadow-[0_40px_80px_-50px_rgba(10,34,54,0.45)]">
            <div className="relative bg-navy-deep px-6 py-6 text-white md:px-7">
              <p className="eyebrow text-[#e4c891]">{t("summary")}</p>
              <h2 id="summary-heading" className="mt-3 font-display text-[1.8rem] font-light leading-tight">
                {roomCount ? t("selectedCount", { count: roomCount }) : t("chooseRooms")}
              </h2>
              <span aria-hidden className="absolute bottom-0 left-6 h-px w-12 bg-gold md:left-7" />
            </div>

            <div className="space-y-6 p-6 md:p-7">
              <dl className="grid grid-cols-2 gap-4 text-sm">
                <SummaryFact label={t("dates")}>
                  {rangeError ? "—" : t("datesValue", { checkIn, checkOut })}
                  {quote ? <span className="block text-muted">{t("nights", { count: quote.nights })}</span> : null}
                </SummaryFact>
                <SummaryFact label={t("guests")}>
                  {t("guestsValue", { adults, children })}
                  {roomCount ? (
                    <span className="block text-muted">
                      {t("capacity", { value: tRooms("capacity", { adults: capacity.adults, children: capacity.children }) })}
                    </span>
                  ) : null}
                </SummaryFact>
              </dl>

              {selected.length === 0 ? (
                <p className="rounded-[20px] bg-ivory p-5 text-sm text-navy/70">{t("empty")}</p>
              ) : (
                <ul className="divide-y divide-line border-y border-line">
                  {selected.map(({ room, quantity }) => (
                    <SummaryLine
                      key={room.id}
                      room={room}
                      quantity={quantity}
                      item={items.get(room.id) ?? null}
                      nights={quote?.nights ?? null}
                      failed={Boolean(quoteError)}
                      onRemove={() => setQuantity(room.id, 0)}
                    />
                  ))}
                </ul>
              )}

              {quoting ? (
                <p className="flex items-center gap-2 text-xs text-muted" aria-live="polite">
                  <Loader2 aria-hidden className="h-3.5 w-3.5 animate-spin text-gold" />
                  {t("updating")}
                </p>
              ) : null}

              {message ? (
                <p role="alert" className="rounded-2xl border border-muted-red/25 bg-muted-red/5 px-4 py-3 text-sm text-muted-red">
                  {message}
                </p>
              ) : null}

              {quote ? (
                <div className="space-y-2.5 rounded-[20px] bg-ivory p-5 text-sm text-navy/80">
                  <Row label={t("roomTotal")} value={formatBdt(quote.room_subtotal)} />
                  {quote.discount_total > 0 ? <Row label={t("discount")} value={`−${formatBdt(quote.discount_total)}`} /> : null}
                  {paymentsOn ? (
                    <>
                      <Row label={t("advance", { percent: quote.advance_percent })} value={formatBdt(quote.advance_amount)} strong />
                      <Row label={t("remaining")} value={formatBdt(quote.balance_amount)} />
                    </>
                  ) : null}
                  <div className="flex items-baseline justify-between gap-4 border-t border-line pt-3 text-navy-deep">
                    <span className="field-label text-navy-deep">{tPanel("total")}</span>
                    <span className="font-display text-2xl tabular-nums">{formatBdt(quote.grand_total)}</span>
                  </div>
                  {policies ? (
                    <p className="pt-1 text-xs leading-relaxed text-muted">
                      {tPanel("cancellationNote", {
                        advance: policies.advance_percent,
                        days: policies.cancellation_days,
                        refund: policies.cancellation_refund_percent,
                      })}
                    </p>
                  ) : null}
                  <p className="text-xs leading-relaxed text-muted">{tPanel("priceNote")}</p>
                </div>
              ) : null}

              {selected.length ? (
                <fieldset className="space-y-4 border-t border-line pt-6">
                  <legend className="sr-only">{tPanel("guestDetails")}</legend>
                  <p aria-hidden className="font-display text-xl text-navy-deep">
                    {tPanel("guestDetails")}
                  </p>
                  <label className="block">
                    <span className="field-label">{tPanel("fullName")}</span>
                    <input className="field" autoComplete="name" value={guest.full_name} onChange={(e) => setGuest({ ...guest, full_name: e.target.value })} required />
                  </label>
                  <label className="block">
                    <span className="field-label">{tPanel("phone")}</span>
                    <input className="field" type="tel" autoComplete="tel" value={guest.phone} onChange={(e) => setGuest({ ...guest, phone: e.target.value })} required />
                  </label>
                  <label className="block">
                    <span className="field-label">
                      {tPanel("email")} <span className="normal-case tracking-normal text-muted/70">{tPanel("optional")}</span>
                    </span>
                    <input type="email" autoComplete="email" className="field" value={guest.email} onChange={(e) => setGuest({ ...guest, email: e.target.value })} />
                  </label>
                  <label className="block">
                    <span className="field-label">{tPanel("notes")}</span>
                    <textarea className="field min-h-20 resize-y" placeholder={tPanel("notesPlaceholder")} value={notes} onChange={(e) => setNotes(e.target.value)} />
                  </label>
                </fieldset>
              ) : null}

              {selected.length ? (
                <div className="space-y-3">
                  <button
                    type="button"
                    disabled={submitting || quoting || !quote}
                    onClick={onProceed}
                    className="group btn-shine relative inline-flex min-h-14 w-full items-center justify-center gap-3 overflow-hidden rounded-full bg-navy-deep text-[0.76rem] font-medium uppercase tracking-[0.18em] text-white transition-colors duration-500 hover:text-navy-deep disabled:pointer-events-none disabled:opacity-45"
                  >
                    <span aria-hidden className="absolute inset-0 origin-left scale-x-0 rounded-full bg-gold transition-transform duration-500 ease-lux group-hover:scale-x-100" />
                    {submitting ? <Loader2 aria-hidden className="relative h-4 w-4 animate-spin" /> : null}
                    <span className="relative">
                      {paymentsOn ? (submitting ? tPanel("starting") : t("proceed")) : submitting ? tRequest("submitting") : tRequest("submit")}
                    </span>
                  </button>
                  {paymentsOn && policies ? (
                    <p className="text-center text-xs text-muted">{t("payNote", { minutes: policies.hold_minutes })}</p>
                  ) : (
                    <p className="text-center text-xs text-muted">{tRequest("noPaymentNote")}</p>
                  )}
                  <p className="flex items-start gap-2 pt-1 text-xs leading-relaxed text-muted">
                    <ShieldCheck aria-hidden className="mt-0.5 h-4 w-4 shrink-0 text-gold" strokeWidth={1.5} />
                    {paymentsOn ? tPanel("secure") : tRequest("secure")}
                  </p>
                </div>
              ) : null}
            </div>
          </div>
        </aside>
      </div>

      {roomCount ? (
        <div className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-white/95 px-4 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] pt-3 shadow-[0_-20px_40px_-30px_rgba(10,34,54,0.5)] backdrop-blur lg:hidden">
          <div className="mx-auto flex max-w-xl items-center justify-between gap-4">
            <div className="min-w-0">
              <p className="text-[0.68rem] uppercase tracking-[0.14em] text-muted">{t("selectedCount", { count: roomCount })}</p>
              <p className="font-display text-xl tabular-nums text-navy-deep">
                {quote ? formatBdt(quote.grand_total) : quoting ? "…" : "—"}
              </p>
            </div>
            <a
              href="#booking-summary"
              className="inline-flex min-h-12 items-center rounded-full bg-navy-deep px-6 text-[0.7rem] font-medium uppercase tracking-[0.16em] text-white"
            >
              {t("review")}
            </a>
          </div>
        </div>
      ) : null}
    </Sheet>
  );
}

function RoomOption({
  room,
  quantity,
  free,
  checking,
  onChange,
}: {
  room: RoomSummary;
  quantity: number;
  free: number | null;
  checking: boolean;
  onChange: (quantity: number) => void;
}) {
  const t = useTranslations("multiBooking");
  const tRooms = useTranslations("rooms");
  const image = roomImage(room);
  const unavailable = free !== null && free === 0;
  const over = free !== null && quantity > free;
  const isSelected = quantity > 0;

  return (
    <li
      className={`grid grid-cols-[88px_minmax(0,1fr)] gap-4 rounded-[24px] border bg-white p-4 transition-colors sm:grid-cols-[120px_minmax(0,1fr)_auto] sm:items-center sm:gap-5 sm:p-5 ${
        over ? "border-muted-red/50" : isSelected ? "border-gold ring-1 ring-gold/40" : "border-line"
      } ${unavailable && !isSelected ? "opacity-60" : ""}`}
    >
      <div className="relative aspect-square overflow-hidden rounded-[18px] bg-sand">
        <Image src={image.src} alt={image.alt} fill sizes="120px" className="object-cover" />
        {isSelected ? (
          <span className="absolute left-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-gold text-navy-deep">
            <Check aria-hidden className="h-3.5 w-3.5" strokeWidth={2} />
            <span className="sr-only">{t("selected")}</span>
          </span>
        ) : null}
      </div>
      <div className="min-w-0">
        {room.room_type_label ? <p className="eyebrow text-gold">{room.room_type_label}</p> : null}
        <h3 className="mt-1 font-display text-[1.6rem] font-light leading-tight text-navy-deep">{room.name}</h3>
        <p className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[0.72rem] uppercase tracking-[0.12em] text-navy/70">
          <span className="inline-flex items-center gap-1.5">
            <Users aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
            {tRooms("capacity", { adults: room.max_adults, children: room.max_children })}
          </span>
          <span>{room.starting_price ? t("perNight", { price: formatBdt(room.starting_price) }) : tRooms("noRate")}</span>
        </p>
        <p className={`mt-2 text-xs ${unavailable || over ? "text-muted-red" : "text-[#1f6f5c]"}`} aria-live="polite">
          {checking ? t("checking") : free === null ? "" : unavailable ? t("unavailable") : over ? t("limit", { count: free }) : t("available", { count: free })}
        </p>
      </div>
      <div className="col-span-2 flex items-center justify-end gap-3 sm:col-span-1 sm:w-44 sm:flex-col sm:items-stretch">
        {isSelected ? (
          <>
            <div className="w-40 sm:w-full">
              <Stepper
                label={t("quantityLabel", { name: room.name })}
                value={quantity}
                min={0}
                max={Math.max(quantity, Math.min(MAX_ROOMS, free ?? room.inventory ?? 1))}
                onChange={onChange}
              />
            </div>
            <button
              type="button"
              onClick={() => onChange(0)}
              className="inline-flex min-h-11 items-center justify-center gap-1.5 text-[0.7rem] font-medium uppercase tracking-[0.14em] text-muted hover:text-muted-red"
              aria-label={t("removeNamed", { name: room.name })}
            >
              <X aria-hidden className="h-3.5 w-3.5" strokeWidth={1.75} />
              {t("remove")}
            </button>
          </>
        ) : (
          <button
            type="button"
            disabled={unavailable || checking || !room.starting_price}
            onClick={() => onChange(1)}
            aria-label={t("addNamed", { name: room.name })}
            className="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-navy-deep px-5 text-[0.7rem] font-medium uppercase tracking-[0.16em] text-navy-deep transition-colors hover:bg-navy-deep hover:text-white disabled:pointer-events-none disabled:border-line disabled:text-muted sm:w-full"
          >
            <Plus aria-hidden className="h-4 w-4" strokeWidth={1.5} />
            {t("add")}
          </button>
        )}
      </div>
    </li>
  );
}

function SummaryLine({
  room,
  quantity,
  item,
  nights,
  failed,
  onRemove,
}: {
  room: RoomSummary;
  quantity: number;
  item: QuoteItem | null;
  nights: number | null;
  failed: boolean;
  onRemove: () => void;
}) {
  const t = useTranslations("multiBooking");
  return (
    <li className="flex items-start justify-between gap-3 py-4">
      <div className="min-w-0">
        <p className="font-display text-lg leading-tight text-navy-deep">
          {quantity > 1 ? `${quantity} × ` : ""}
          {room.name}
        </p>
        {room.room_type_label ? <p className="text-xs text-muted">{room.room_type_label}</p> : null}
        {item && nights ? (
          <p className="mt-1 text-xs tabular-nums text-navy/70">{t("line", { quantity, price: formatBdt(item.nightly_rate), nights })}</p>
        ) : null}
      </div>
      <div className="flex shrink-0 items-center gap-2">
        <span className="text-sm tabular-nums text-navy-deep">{item ? formatBdt(item.subtotal) : failed ? "—" : "…"}</span>
        <button
          type="button"
          onClick={onRemove}
          aria-label={t("removeNamed", { name: room.name })}
          className="inline-flex h-9 w-9 items-center justify-center rounded-full text-muted transition-colors hover:bg-muted-red/10 hover:text-muted-red"
        >
          <X aria-hidden className="h-4 w-4" strokeWidth={1.75} />
        </button>
      </div>
    </li>
  );
}

function SummaryFact({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div>
      <dt className="field-label">{label}</dt>
      <dd className="mt-1 text-navy-deep">{children}</dd>
    </div>
  );
}

function Row({ label, value, strong = false }: { label: string; value: string; strong?: boolean }) {
  return (
    <div className={`flex justify-between gap-4 ${strong ? "text-navy-deep" : ""}`}>
      <span>{label}</span>
      <span className={`tabular-nums ${strong ? "font-medium" : ""}`}>{value}</span>
    </div>
  );
}

/** Ivory sheet with rounded top corners that slides over the page hero. */
export function Sheet({ children, className = "" }: { children: ReactNode; className?: string }) {
  return (
    <div className="rounded-top-lux relative z-10 -mt-10 bg-ivory md:-mt-14">
      <div className={`container-lux ${className}`}>{children}</div>
    </div>
  );
}
