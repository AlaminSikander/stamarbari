"use client";

import { useSearchParams } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { API_URL, apiPost, formatBdt, ApiRequestError } from "@/lib/api";
import type { AvailabilityResult, BookingPayload } from "@/lib/types";
import { Link } from "@/i18n/navigation";

type Meal = { id: number; name: string; price_per_adult_per_night: number };
type Addon = { id: number; name: string; price: number; is_pickup: boolean };

export function BookingClient() {
  const t = useTranslations("search");
  const b = useTranslations("booking");
  const params = useSearchParams();
  const checkIn = params.get("check_in") ?? "";
  const checkOut = params.get("check_out") ?? "";
  const adults = Number(params.get("adults") ?? 2);
  const children = Number(params.get("children") ?? 0);
  const roomCategoryId = params.get("room_category_id");

  const [status, setStatus] = useState<"idle" | "loading" | "error" | "empty" | "ready">("idle");
  const [message, setMessage] = useState("");
  const [results, setResults] = useState<AvailabilityResult["rooms"]>([]);
  const [selected, setSelected] = useState<AvailabilityResult["rooms"][number] | null>(null);
  const [meals, setMeals] = useState<Meal[]>([]);
  const [addons, setAddons] = useState<Addon[]>([]);
  const [mealId, setMealId] = useState<number | undefined>();
  const [addonIds, setAddonIds] = useState<number[]>([]);
  const [guest, setGuest] = useState({ full_name: "", phone: "", email: "", whatsapp: "" });
  const [booking, setBooking] = useState<BookingPayload | null>(null);
  const [paying, setPaying] = useState(false);

  const searchKey = useMemo(
    () => `${checkIn}|${checkOut}|${adults}|${children}|${roomCategoryId ?? ""}`,
    [checkIn, checkOut, adults, children, roomCategoryId],
  );

  useEffect(() => {
    if (!checkIn || !checkOut) {
      setStatus("idle");
      return;
    }
    setStatus("loading");
    apiPost<AvailabilityResult>("/availability", {
      check_in: checkIn,
      check_out: checkOut,
      adults,
      children,
      room_category_id: roomCategoryId ? Number(roomCategoryId) : undefined,
    })
      .then((data) => {
        setResults(data.rooms);
        setStatus(data.rooms.length ? "ready" : "empty");
      })
      .catch((error: unknown) => {
        setStatus("error");
        setMessage(error instanceof ApiRequestError ? error.message : t("error"));
      });
  }, [searchKey, checkIn, checkOut, adults, children, roomCategoryId, t]);

  useEffect(() => {
    fetch(`${API_URL}/meal-plans`)
      .then((r) => r.json())
      .then((json) => setMeals(json.data ?? []))
      .catch(() => undefined);
    fetch(`${API_URL}/addons`)
      .then((r) => r.json())
      .then((json) => setAddons(json.data ?? []))
      .catch(() => undefined);
  }, []);

  async function holdRoom() {
    if (!selected) return;
    setPaying(true);
    setMessage("");
    try {
      const created = await apiPost<BookingPayload>("/bookings", {
        check_in: checkIn,
        check_out: checkOut,
        adults,
        children,
        room_category_id: selected.id,
        meal_plan_id: mealId,
        addons: addonIds.map((id) => ({ id, quantity: 1 })),
        guest,
      });
      setBooking(created);
      const payment = await apiPost<{ payment_url: string | null }>(
        `/bookings/${created.booking_number}/payments`,
        {},
      );
      if (payment.payment_url) {
        window.location.href = payment.payment_url;
      }
    } catch (error: unknown) {
      setMessage(error instanceof ApiRequestError ? error.message : t("error"));
    } finally {
      setPaying(false);
    }
  }

  return (
    <div className="mx-auto max-w-4xl px-4 pb-32 pt-28">
      <h1 className="font-display text-5xl">Book your stay</h1>
      <ol className="mt-6 flex gap-3 text-sm text-navy/60">
        {["01 Dates", "02 Room", "03 Details", "04 Pay"].map((step) => (
          <li key={step} className="rounded-full bg-white px-3 py-2">
            {step}
          </li>
        ))}
      </ol>

      {status === "loading" ? <p className="mt-8">{t("loading")}</p> : null}
      {status === "error" ? <p className="mt-8 text-muted-red">{message || t("error")}</p> : null}
      {status === "empty" ? <p className="mt-8">{t("empty")}</p> : null}
      {status === "idle" ? (
        <p className="mt-8">
          Choose dates from the home page, or <Link href="/" className="text-teal">start a search</Link>.
        </p>
      ) : null}

      <ul className="mt-8 space-y-4">
        {results.map((room) => (
          <li key={room.id}>
            <button
              type="button"
              onClick={() => setSelected(room)}
              className={`w-full rounded-2xl border p-5 text-left ${selected?.id === room.id ? "border-gold bg-gold/10" : "border-navy/10 bg-white"}`}
            >
              <div className="flex items-center justify-between gap-4">
                <div>
                  <p className="font-display text-2xl">{room.name}</p>
                  <p className="text-sm text-navy/70">{room.available_rooms} available</p>
                </div>
                <p className="text-lg">
                  {room.quote ? formatBdt(room.quote.grand_total) : "TBD"}
                </p>
              </div>
            </button>
          </li>
        ))}
      </ul>

      {selected ? (
        <div className="mt-10 space-y-6 rounded-3xl bg-white p-6">
          <h2 className="font-display text-3xl">{b("guest")}</h2>
          <div className="grid gap-3 md:grid-cols-2">
            <input className="min-h-11 rounded-xl border px-3" placeholder="Full name" value={guest.full_name} onChange={(e) => setGuest({ ...guest, full_name: e.target.value })} />
            <input className="min-h-11 rounded-xl border px-3" placeholder="Phone" value={guest.phone} onChange={(e) => setGuest({ ...guest, phone: e.target.value })} />
            <input className="min-h-11 rounded-xl border px-3" placeholder="WhatsApp" value={guest.whatsapp} onChange={(e) => setGuest({ ...guest, whatsapp: e.target.value })} />
            <input className="min-h-11 rounded-xl border px-3" placeholder="Email" value={guest.email} onChange={(e) => setGuest({ ...guest, email: e.target.value })} />
          </div>
          <div>
            <h3 className="font-medium">{b("meal")}</h3>
            <div className="mt-2 space-y-2">
              {meals.map((meal) => (
                <label key={meal.id} className="flex min-h-11 items-center gap-2">
                  <input type="radio" name="meal" onChange={() => setMealId(meal.id)} />
                  {meal.name} {meal.price_per_adult_per_night ? `· ${formatBdt(meal.price_per_adult_per_night)} / adult / night` : ""}
                </label>
              ))}
            </div>
          </div>
          <div>
            <h3 className="font-medium">{b("addons")}</h3>
            {addons.map((addon) => (
              <label key={addon.id} className="flex min-h-11 items-center gap-2">
                <input
                  type="checkbox"
                  onChange={(e) =>
                    setAddonIds((ids) =>
                      e.target.checked ? [...ids, addon.id] : ids.filter((id) => id !== addon.id),
                    )
                  }
                />
                {addon.name} {addon.price ? `· ${formatBdt(addon.price)}` : ""}
              </label>
            ))}
          </div>
          <p className="text-sm text-navy/70">{b("hold")}</p>
          <p className="text-sm">{b("policy")}</p>
          {selected.quote ? (
            <div className="rounded-2xl bg-off p-4">
              <p>Total {formatBdt(selected.quote.grand_total)}</p>
              <p>Advance {formatBdt(selected.quote.advance_amount)}</p>
              <p>Remaining {formatBdt(selected.quote.balance_amount)}</p>
            </div>
          ) : null}
          {message ? <p className="text-muted-red">{message}</p> : null}
          <button
            type="button"
            disabled={paying || !guest.full_name || !guest.phone}
            onClick={holdRoom}
            className="min-h-12 rounded-full bg-gold px-6 font-medium text-navy disabled:opacity-50"
          >
            {paying ? "Starting payment…" : b("pay")}
          </button>
          {booking ? <p>Booking {booking.booking_number}</p> : null}
        </div>
      ) : null}
    </div>
  );
}
