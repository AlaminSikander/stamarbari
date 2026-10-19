"use client";

import { CircleAlert } from "lucide-react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { contact } from "@/lib/contact";
import { ButtonLink } from "@/components/ui/Button";

export function BookingFailed() {
  const t = useTranslations("booking");
  const tStatus = useTranslations("bookingStatus");
  const params = useSearchParams();
  const booking = params.get("booking");
  const status = params.get("status") ?? "pending";

  return (
    <div className="mx-auto max-w-2xl rounded-[32px] border border-line bg-white p-8 text-center shadow-[0_40px_80px_-50px_rgba(10,34,54,0.45)] md:p-12">
      <CircleAlert aria-hidden className="mx-auto h-10 w-10 text-muted-red" strokeWidth={1.25} />
      <h2 className="mt-5 font-display text-[2rem] font-light leading-tight text-navy-deep">{t("failed")}</h2>
      <p className="lede mt-4 text-navy/75">{t("failedBody")}</p>
      <p className="mt-6 inline-flex rounded-full bg-sand/70 px-4 py-2 text-[0.68rem] uppercase tracking-[0.18em] text-navy">
        {tStatus("status", { status })}
      </p>
      <p className="mx-auto mt-6 max-w-lg text-sm leading-relaxed text-muted">{tStatus("failedNote")}</p>
      <div className="mt-9 flex flex-wrap justify-center gap-3">
        <ButtonLink href={booking ? `/booking` : "/booking"} arrow>
          {t("tryAgain")}
        </ButtonLink>
        <ButtonLink href={contact.whatsapp} variant="whatsapp">
          {t("contact")}
        </ButtonLink>
      </div>
    </div>
  );
}
