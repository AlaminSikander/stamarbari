"use client";

import { ArrowRight, CalendarDays, Users } from "lucide-react";
import { FormEvent, useState } from "react";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/navigation";

const fieldLabel = "flex items-center gap-2 text-[0.62rem] font-medium uppercase tracking-[0.22em] text-muted";
const fieldInput =
  "mt-1.5 w-full min-h-10 bg-transparent font-display text-xl text-navy-deep outline-none [color-scheme:light] placeholder:text-muted/60";

export function BookingSearch({
  compact = false,
  title,
}: {
  compact?: boolean;
  title?: string;
}) {
  const t = useTranslations("search");
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const checkIn = String(data.get("check_in") ?? "");
    const checkOut = String(data.get("check_out") ?? "");
    if (!checkIn || !checkOut) {
      setError(t("errorRequired"));
      return;
    }
    if (checkOut <= checkIn) {
      setError(t("errorOrder"));
      return;
    }
    const params = new URLSearchParams({
      check_in: checkIn,
      check_out: checkOut,
      adults: String(data.get("adults") ?? "2"),
      children: String(data.get("children") ?? "0"),
    });
    const room = String(data.get("room") ?? "");
    if (room) params.set("room_category_id", room);
    router.push(`/booking?${params.toString()}`);
  }

  const cell = compact
    ? "rounded-2xl border border-line px-5 py-3 transition-colors focus-within:border-gold"
    : "px-5 py-3 transition-colors focus-within:bg-white/70 md:rounded-2xl md:px-6";

  return (
    <form
      onSubmit={onSubmit}
      aria-label={title ?? t("barLabel")}
      className={
        compact
          ? "rounded-[28px] border border-line bg-white p-6 shadow-[var(--shadow-soft)]"
          : "rounded-[28px] border border-white/70 bg-ivory/80 p-2 shadow-[0_40px_80px_-40px_rgba(10,34,54,0.55)] backdrop-blur-2xl md:p-2.5"
      }
    >
      {title && compact ? <h2 className="mb-6 font-display text-3xl text-navy-deep">{title}</h2> : null}
      <div
        className={
          compact
            ? "grid gap-3"
            : "grid divide-y divide-line md:grid-cols-[1.15fr_1.15fr_0.8fr_0.8fr_auto] md:items-center md:divide-x md:divide-y-0"
        }
      >
        <label className={cell}>
          <span className={fieldLabel}>
            <CalendarDays aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
            {t("checkIn")}
          </span>
          <input required className={fieldInput} type="date" name="check_in" />
        </label>
        <label className={cell}>
          <span className={fieldLabel}>
            <CalendarDays aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
            {t("checkOut")}
          </span>
          <input required className={fieldInput} type="date" name="check_out" />
        </label>
        <label className={cell}>
          <span className={fieldLabel}>
            <Users aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
            {t("adults")}
          </span>
          <input className={fieldInput} type="number" min={1} defaultValue={2} name="adults" />
        </label>
        <label className={cell}>
          <span className={fieldLabel}>
            <Users aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
            {t("children")}
          </span>
          <input className={fieldInput} type="number" min={0} defaultValue={0} name="children" />
        </label>
        <div className={compact ? "pt-2" : "p-2 md:border-l-0 md:p-0 md:pl-2"}>
          <button
            type="submit"
            className="group btn-shine relative inline-flex min-h-14 w-full items-center justify-center gap-3 overflow-hidden rounded-[22px] bg-navy-deep px-8 text-[0.72rem] font-medium uppercase tracking-[0.18em] text-white transition-[transform,background-color] duration-500 ease-lux hover:-translate-y-0.5 md:min-h-[4.5rem]"
          >
            <span
              aria-hidden
              className="absolute inset-0 -z-10 origin-left scale-x-0 bg-gold transition-transform duration-500 ease-lux group-hover:scale-x-100"
            />
            <span className="relative transition-colors duration-500 group-hover:text-navy-deep">{t("submit")}</span>
            <ArrowRight
              aria-hidden
              className="relative h-4 w-4 transition-[transform,color] duration-500 ease-lux group-hover:translate-x-1 group-hover:text-navy-deep"
              strokeWidth={1.5}
            />
          </button>
        </div>
      </div>
      {error ? (
        <p role="alert" className="px-5 pb-2 pt-3 text-sm text-muted-red">
          {error}
        </p>
      ) : null}
    </form>
  );
}
