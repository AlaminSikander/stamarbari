"use client";

import { Loader2 } from "lucide-react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { useEffect, useState } from "react";
import { apiPost, ApiRequestError } from "@/lib/api";

export function VerifyMessage({ text, busy = true }: { text: string; busy?: boolean }) {
  return (
    <div className="mx-auto max-w-xl rounded-[28px] border border-line bg-white p-10 text-center" aria-live="polite">
      {busy ? <Loader2 aria-hidden className="mx-auto h-8 w-8 animate-spin text-gold" strokeWidth={1.5} /> : null}
      <p className={`lede text-navy/80 ${busy ? "mt-5" : ""}`}>{text}</p>
    </div>
  );
}

export function BookingVerify() {
  const t = useTranslations("bookingStatus");
  const params = useSearchParams();
  const booking = params.get("booking") ?? params.get("reference");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!booking) return;
    apiPost<{ booking_status: string }>(`/payments/callback`, {
      booking,
      paymentID: params.get("paymentID"),
      status: params.get("status") ?? "success",
      amount: params.get("amount"),
      trxID: params.get("trxID"),
    })
      .then((data) => {
        if (data.booking_status === "confirmed") {
          window.location.href = `/booking/success?booking=${booking}`;
        } else {
          window.location.href = `/booking/failed?booking=${booking}&status=${data.booking_status}`;
        }
      })
      .catch((err: unknown) => {
        setError(err instanceof ApiRequestError ? err.message : t("verifyError"));
      });
  }, [booking, params, t]);

  if (!booking) return <VerifyMessage text={t("missingRef")} busy={false} />;
  if (error) return <VerifyMessage text={error} busy={false} />;
  return <VerifyMessage text={t("verifying")} />;
}
