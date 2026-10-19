"use client";

import { Cookie } from "lucide-react";
import { useTranslations } from "next-intl";
import { useState, useSyncExternalStore } from "react";

const KEY = "sab-consent";
const subscribeNoop = () => () => {};

export function CookieBanner() {
  const t = useTranslations("cookie");
  const stored = useSyncExternalStore(subscribeNoop, () => localStorage.getItem(KEY), () => "server");
  const [dismissed, setDismissed] = useState(false);

  if (stored !== null || dismissed) return null;

  const choose = (value: "essential" | "all") => {
    localStorage.setItem(KEY, value);
    setDismissed(true);
  };

  return (
    <div
      role="region"
      aria-label={t("label")}
      className="fixed bottom-[5.5rem] left-3 right-3 z-30 rounded-[22px] border border-white/10 bg-navy-deep/95 p-5 text-sm text-white shadow-[0_30px_60px_-24px_rgba(10,34,54,0.8)] backdrop-blur-xl md:bottom-8 md:left-8 md:right-auto md:max-w-sm"
    >
      <div className="flex gap-3">
        <Cookie aria-hidden className="mt-0.5 h-5 w-5 shrink-0 text-gold" strokeWidth={1.5} />
        <p className="leading-relaxed text-white/80">{t("body")}</p>
      </div>
      <div className="mt-4 flex flex-wrap gap-2 pl-8">
        <button
          className="min-h-11 rounded-full bg-gold px-5 text-[0.68rem] font-medium uppercase tracking-[0.16em] text-navy-deep transition-colors hover:bg-white"
          type="button"
          onClick={() => choose("essential")}
        >
          {t("essential")}
        </button>
        <button
          className="min-h-11 rounded-full border border-white/30 px-5 text-[0.68rem] font-medium uppercase tracking-[0.16em] transition-colors hover:border-white hover:bg-white hover:text-navy-deep"
          type="button"
          onClick={() => choose("all")}
        >
          {t("allow")}
        </button>
      </div>
    </div>
  );
}
