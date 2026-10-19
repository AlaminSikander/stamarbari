"use client";

import { CheckCircle2, Download, Loader2 } from "lucide-react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { useEffect, useState } from "react";
import { apiGet, formatBdt } from "@/lib/api";
import { contact } from "@/lib/contact";
import type { BookingPayload } from "@/lib/types";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { ButtonLink } from "@/components/ui/Button";

export function BookingSuccess() {
  const t = useTranslations("booking");
  const tStatus = useTranslations("bookingStatus");
  const params = useSearchParams();
  const number = params.get("booking") ?? "";
  const [booking, setBooking] = useState<BookingPayload | null>(null);

  useEffect(() => {
    if (!number) return;
    apiGet<BookingPayload>(`/bookings/${number}`).then(setBooking).catch(() => undefined);
  }, [number]);

  if (!booking) {
    return (
      <p className="flex items-center justify-center gap-3 py-10 text-muted" aria-live="polite">
        <Loader2 aria-hidden className="h-4 w-4 animate-spin text-gold" />
        {tStatus("loadingConfirmation")}
      </p>
    );
  }

  const rows = [
    { label: t("number"), value: booking.booking_number },
    ...(booking.room ? [{ label: tStatus("room"), value: booking.room }] : []),
    { label: tStatus("dates"), value: tStatus("checkInOut", { checkIn: booking.check_in, checkOut: booking.check_out }) },
    { label: t("advance"), value: formatBdt(booking.amount_paid) },
    { label: t("remaining"), value: formatBdt(booking.balance_amount) },
  ];

  return (
    <div className="mx-auto max-w-2xl overflow-hidden rounded-[32px] border border-line bg-white shadow-[0_40px_80px_-50px_rgba(10,34,54,0.45)]">
      <div className="bg-navy-deep px-8 py-10 text-center text-white md:px-12">
        <CheckCircle2 aria-hidden className="mx-auto h-10 w-10 text-[#e4c891]" strokeWidth={1.25} />
        <p className="eyebrow mt-5 text-[#e4c891]">{t("confirmed")}</p>
        <h2 className="mt-3 font-display text-[2.2rem] font-light leading-tight">{t("thanks", { name: booking.guest?.full_name ?? "" })}</h2>
      </div>
      <dl className="divide-y divide-line px-8 md:px-12">
        {rows.map((row) => (
          <div key={row.label} className="flex flex-wrap items-baseline justify-between gap-3 py-5">
            <dt className="field-label">{row.label}</dt>
            <dd className="font-display text-xl text-navy-deep">{row.value}</dd>
          </div>
        ))}
      </dl>
      <div className="flex flex-wrap justify-center gap-3 border-t border-line px-8 py-8 md:px-12">
        {booking.invoice_available ? (
          <a
            className="inline-flex min-h-12 items-center gap-2.5 rounded-full bg-navy-deep px-7 text-[0.72rem] font-medium uppercase tracking-[0.16em] text-white transition-colors hover:bg-gold hover:text-navy-deep"
            href={`${process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1"}/bookings/${booking.booking_number}/invoice`}
          >
            <Download aria-hidden className="h-4 w-4" strokeWidth={1.5} />
            {t("download")}
          </a>
        ) : null}
        <a
          className="inline-flex min-h-12 items-center gap-2.5 rounded-full bg-[#1f6f5c] px-7 text-[0.72rem] font-medium uppercase tracking-[0.16em] text-white transition-colors hover:bg-[#155446]"
          href={contact.whatsapp}
          target="_blank"
          rel="noreferrer"
        >
          <WhatsAppIcon className="h-4 w-4" />
          {t("whatsapp")}
        </a>
        <ButtonLink href="/" variant="ghost-dark">
          {t("home")}
        </ButtonLink>
      </div>
    </div>
  );
}
