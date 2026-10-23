"use client";

import { CheckCircle2, Clock3, Download, Info, Loader2, Phone } from "lucide-react";
import { useSearchParams } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { useCallback, useEffect, useState, type ReactNode } from "react";
import { ApiRequestError, API_URL, apiGet, formatBdt } from "@/lib/api";
import { resortWhatsAppNumber, takeSubmitted, whatsAppChatUrl } from "@/lib/booking";
import { contact } from "@/lib/contact";
import type { BookingPayload } from "@/lib/types";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { ButtonLink } from "@/components/ui/Button";
import { Toast } from "@/components/ui/Toast";

const RESORT_WHATSAPP = resortWhatsAppNumber();

function formatDate(value: string, locale: string): string {
  const [y, m, d] = value.split("-").map(Number);
  if (!y || !m || !d) return value;
  return new Intl.DateTimeFormat(locale === "bn" ? "bn-BD" : "en-GB", {
    weekday: "short",
    day: "numeric",
    month: "short",
    year: "numeric",
  }).format(new Date(y, m - 1, d));
}

export function BookingSuccess() {
  const t = useTranslations("booking");
  const tStatus = useTranslations("bookingStatus");
  const tRequest = useTranslations("bookingRequest");
  const locale = useLocale();
  const params = useSearchParams();
  const number = params.get("booking") ?? "";
  const token = params.get("token") ?? "";
  const [result, setResult] = useState<{ booking: BookingPayload | null; error: string } | null>(null);
  const [showToast, setShowToast] = useState(false);
  const hideToast = useCallback(() => setShowToast(false), []);

  useEffect(() => {
    if (!number || !token) return;
    let active = true;
    apiGet<BookingPayload>(`/bookings/${encodeURIComponent(number)}?token=${encodeURIComponent(token)}`, locale)
      .then((booking) => {
        if (!active) return;
        setResult({ booking, error: "" });
        // Only after the saved booking has been read back, and only once per submission.
        if (takeSubmitted(booking.booking_number)) setShowToast(true);
      })
      .catch((error: unknown) => {
        if (!active) return;
        setResult({
          booking: null,
          error: error instanceof ApiRequestError && error.status !== 404 && error.status < 500 ? error.message : tRequest("loadError"),
        });
      });
    return () => {
      active = false;
    };
  }, [number, token, locale, tRequest]);

  if (!number || !token) return <Notice text={tRequest("missing")} />;
  if (result?.error) return <Notice text={result.error} reference={number} referenceLabel={tRequest("reference")} />;
  const booking = result?.booking;
  if (!booking) {
    return (
      <p className="flex items-center justify-center gap-3 py-10 text-muted" aria-live="polite">
        <Loader2 aria-hidden className="h-4 w-4 animate-spin text-gold" />
        {tStatus("loadingConfirmation")}
      </p>
    );
  }

  const isRequest = booking.status === "pending_confirmation";
  const lines = booking.rooms ?? [];
  const roomsValue = lines.length ? (
    <ul className="space-y-1 text-right">
      {lines.map((line) => (
        <li key={line.room_category_id}>
          {line.quantity > 1 ? `${line.quantity} × ` : ""}
          {line.name}
          {line.room_type_label ? <span className="block font-sans text-xs text-muted">{line.room_type_label}</span> : null}
        </li>
      ))}
    </ul>
  ) : (
    booking.room
  );
  const dates = (
    <span className="block text-right">
      {formatDate(booking.check_in, locale)} → {formatDate(booking.check_out, locale)}
      {booking.nights ? <span className="block font-sans text-xs text-muted">{tRequest("nights", { count: booking.nights })}</span> : null}
    </span>
  );

  const rows: { label: string; value: ReactNode }[] = isRequest
    ? [
        { label: tRequest("reference"), value: <span className="tracking-wide">{booking.booking_number}</span> },
        { label: tRequest("guest"), value: booking.guest?.full_name ?? "—" },
        { label: tRequest("rooms"), value: roomsValue },
        { label: tRequest("dates"), value: dates },
        { label: tRequest("guests"), value: tRequest("guestsValue", { adults: booking.adults, children: booking.children }) },
        ...(booking.addons.length
          ? [{ label: tRequest("addons"), value: booking.addons.map((a) => `${a.quantity > 1 ? `${a.quantity} × ` : ""}${a.name ?? ""}`).join(", ") }]
          : []),
        ...(booking.special_requests ? [{ label: tRequest("requests"), value: <span className="font-sans text-base">{booking.special_requests}</span> }] : []),
        {
          label: tRequest("total"),
          value: (
            <span className="block text-right">
              {formatBdt(booking.grand_total)}
              <span className="block max-w-xs font-sans text-xs leading-relaxed text-muted">{tRequest("totalNote")}</span>
            </span>
          ),
        },
        {
          label: tRequest("status"),
          value: (
            <span className="inline-flex items-center gap-2 rounded-full bg-gold-wash px-3 py-1 font-sans text-xs font-medium uppercase tracking-[0.12em] text-navy-deep">
              <Clock3 aria-hidden className="h-3.5 w-3.5 text-gold-dark" strokeWidth={1.75} />
              {booking.status_label}
            </span>
          ),
        },
        ...(booking.payment_status === "unpaid" ? [{ label: tRequest("payment"), value: <span className="font-sans text-sm">{tRequest("paymentNone")}</span> }] : []),
      ]
    : [
        { label: t("number"), value: booking.booking_number },
        { label: lines.length > 1 || (lines[0]?.quantity ?? 1) > 1 ? tStatus("rooms") : tStatus("room"), value: roomsValue },
        { label: tStatus("dates"), value: dates },
        { label: t("advance"), value: formatBdt(booking.amount_paid) },
        { label: t("remaining"), value: formatBdt(booking.balance_amount) },
      ];

  const whatsAppUrl = RESORT_WHATSAPP ? whatsAppChatUrl(RESORT_WHATSAPP, booking.whatsapp_message) : null;

  return (
    <>
      {showToast ? <Toast message={tRequest("toast")} dismissLabel={tRequest("dismiss")} onDismiss={hideToast} /> : null}
      <div className="mx-auto max-w-2xl overflow-hidden rounded-[32px] border border-line bg-white shadow-[0_40px_80px_-50px_rgba(10,34,54,0.45)]">
        <div className="bg-navy-deep px-8 py-10 text-center text-white md:px-12">
          <CheckCircle2 aria-hidden className="mx-auto h-10 w-10 text-[#e4c891]" strokeWidth={1.25} />
          <p className="eyebrow mt-5 text-[#e4c891]">{isRequest ? tRequest("eyebrow") : t("confirmed")}</p>
          <h2 className="mt-3 font-display text-[2.2rem] font-light leading-tight">
            {isRequest ? tRequest("thanks", { name: booking.guest?.full_name ?? "" }) : t("thanks", { name: booking.guest?.full_name ?? "" })}
          </h2>
        </div>

        {isRequest ? (
          <div className="mx-8 mt-8 flex items-start gap-3 rounded-[20px] border border-gold/40 bg-gold-wash/50 px-5 py-4 text-sm leading-relaxed text-navy-deep md:mx-12">
            <Info aria-hidden className="mt-0.5 h-4 w-4 shrink-0 text-gold-dark" strokeWidth={1.75} />
            <p>
              {tRequest("notConfirmed")} <span className="block pt-1 text-navy/70">{tRequest("saveReference")}</span>
            </p>
          </div>
        ) : null}

        <dl className="divide-y divide-line px-8 md:px-12">
          {rows.map((row) => (
            <div key={row.label} className="flex flex-wrap items-baseline justify-between gap-3 py-5">
              <dt className="field-label">{row.label}</dt>
              <dd className="font-display text-xl text-navy-deep">{row.value}</dd>
            </div>
          ))}
        </dl>

        {isRequest ? (
          <section aria-labelledby="whatsapp-heading" className="border-t border-line px-8 py-8 md:px-12">
            <h3 id="whatsapp-heading" className="font-display text-2xl text-navy-deep">
              {tRequest("whatsappTitle")}
            </h3>
            {whatsAppUrl ? (
              <>
                <p className="mt-2 text-sm leading-relaxed text-navy/75">{tRequest("whatsappBody")}</p>
                <a
                  className="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2.5 rounded-full bg-[#1f6f5c] px-7 text-[0.72rem] font-medium uppercase tracking-[0.16em] text-white transition-colors hover:bg-[#155446] sm:w-auto"
                  href={whatsAppUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <WhatsAppIcon className="h-4 w-4" />
                  {tRequest("whatsappButton")}
                </a>
              </>
            ) : (
              <>
                <p className="mt-2 text-sm leading-relaxed text-navy/75">{tRequest("whatsappMissing")}</p>
                <button
                  type="button"
                  disabled
                  className="mt-5 inline-flex min-h-12 w-full cursor-not-allowed items-center justify-center gap-2.5 rounded-full bg-line px-7 text-[0.72rem] font-medium uppercase tracking-[0.16em] text-muted sm:w-auto"
                >
                  <WhatsAppIcon className="h-4 w-4" />
                  {tRequest("whatsappButton")}
                </button>
                {process.env.NODE_ENV !== "production" ? (
                  <p className="mt-3 rounded-xl bg-ivory px-4 py-3 font-mono text-xs leading-relaxed text-navy/70">{tRequest("whatsappSetup")}</p>
                ) : null}
              </>
            )}
          </section>
        ) : null}

        <div className="flex flex-wrap justify-center gap-3 border-t border-line px-8 py-8 md:px-12">
          {booking.invoice_available ? (
            <a
              className="inline-flex min-h-12 items-center gap-2.5 rounded-full bg-navy-deep px-7 text-[0.72rem] font-medium uppercase tracking-[0.16em] text-white transition-colors hover:bg-gold hover:text-navy-deep"
              href={`${API_URL}/bookings/${encodeURIComponent(booking.booking_number)}/invoice?token=${encodeURIComponent(booking.access_token)}`}
            >
              <Download aria-hidden className="h-4 w-4" strokeWidth={1.5} />
              {t("download")}
            </a>
          ) : null}
          {isRequest ? (
            <a
              className="inline-flex min-h-12 items-center gap-2.5 rounded-full border border-navy/25 px-7 text-[0.72rem] font-medium uppercase tracking-[0.16em] text-navy transition-colors hover:border-navy-deep hover:bg-navy-deep hover:text-white"
              href={contact.tel}
            >
              <Phone aria-hidden className="h-4 w-4" strokeWidth={1.5} />
              {tRequest("call")}
            </a>
          ) : (
            <a
              className="inline-flex min-h-12 items-center gap-2.5 rounded-full bg-[#1f6f5c] px-7 text-[0.72rem] font-medium uppercase tracking-[0.16em] text-white transition-colors hover:bg-[#155446]"
              href={contact.whatsapp}
              target="_blank"
              rel="noreferrer"
            >
              <WhatsAppIcon className="h-4 w-4" />
              {t("whatsapp")}
            </a>
          )}
          <ButtonLink href="/" variant="ghost-dark">
            {t("home")}
          </ButtonLink>
        </div>
      </div>
    </>
  );
}

function Notice({ text, reference, referenceLabel }: { text: string; reference?: string; referenceLabel?: string }) {
  return (
    <div role="alert" className="mx-auto max-w-xl rounded-[28px] border border-line bg-white p-10 text-center">
      <p className="lede text-navy/80">{text}</p>
      {reference ? (
        <p className="mt-4 text-sm text-muted">
          {referenceLabel}: <span className="font-medium text-navy-deep">{reference}</span>
        </p>
      ) : null}
    </div>
  );
}
