"use client";

import { ArrowRight, Phone } from "lucide-react";
import { useTranslations } from "next-intl";
import { useEffect, useState } from "react";
import { Link, usePathname } from "@/i18n/navigation";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { contact } from "@/lib/contact";

/** Desktop: floating WhatsApp button. Mobile: sticky booking bar once the hero has scrolled away. */
export function FloatingActions() {
  const t = useTranslations("common");
  const tBar = useTranslations("mobileBar");
  const tRoom = useTranslations("roomDetail");
  const pathname = usePathname();
  const [shown, setShown] = useState(false);

  useEffect(() => {
    const onScroll = () => {
      const nearBottom = window.innerHeight + window.scrollY > document.documentElement.scrollHeight - 160;
      setShown(window.scrollY > window.innerHeight * 0.6 && !nearBottom);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
    return () => {
      window.removeEventListener("scroll", onScroll);
      window.removeEventListener("resize", onScroll);
    };
  }, [pathname]);

  if (pathname.startsWith("/booking")) return null;
  const onRoom = pathname.startsWith("/rooms/");
  const visibility = shown ? "translate-y-0 opacity-100" : "pointer-events-none translate-y-6 opacity-0";

  return (
    <>
      <a
        href={contact.whatsapp}
        target="_blank"
        rel="noreferrer"
        aria-label={t("whatsapp")}
        tabIndex={shown ? undefined : -1}
        className={`group fixed bottom-8 right-8 z-40 hidden h-14 items-center gap-0 overflow-hidden rounded-full bg-[#1f6f5c] pl-[1.05rem] pr-[1.05rem] text-white shadow-[0_20px_40px_-18px_rgba(10,34,54,0.7)] transition-[opacity,translate,gap,padding] duration-500 ease-lux hover:gap-3 hover:pr-6 md:inline-flex ${visibility}`}
      >
        <WhatsAppIcon className="h-5 w-5 shrink-0" />
        <span className="max-w-0 overflow-hidden whitespace-nowrap text-[0.7rem] font-medium uppercase tracking-[0.18em] transition-[max-width] duration-500 ease-lux group-hover:max-w-40">
          {t("whatsapp")}
        </span>
      </a>

      <div
        className={`fixed inset-x-3 bottom-3 z-40 transition-[opacity,translate] duration-500 ease-lux md:hidden ${visibility}`}
        style={{ paddingBottom: "env(safe-area-inset-bottom)" }}
      >
        <div className="flex items-center gap-2 rounded-full border border-white/10 bg-navy-deep/90 p-1.5 text-white shadow-[0_20px_40px_-16px_rgba(10,34,54,0.8)] backdrop-blur-xl">
          {onRoom ? (
            <a
              href="#reserve"
              tabIndex={shown ? undefined : -1}
              className="inline-flex min-h-12 flex-1 items-center justify-between gap-3 rounded-full bg-gold pl-5 pr-2 text-navy-deep"
            >
              <span className="text-[0.72rem] font-medium uppercase tracking-[0.16em]">{tRoom("reserve")}</span>
              <span className="flex h-9 w-9 items-center justify-center rounded-full bg-navy-deep text-white">
                <ArrowRight aria-hidden className="h-4 w-4" strokeWidth={1.5} />
              </span>
            </a>
          ) : (
            <Link
              href="/booking"
              tabIndex={shown ? undefined : -1}
              className="inline-flex min-h-12 flex-1 items-center justify-between gap-3 rounded-full bg-gold pl-5 pr-2 text-navy-deep"
            >
              <span className="leading-tight">
                <span className="block text-[0.72rem] font-medium uppercase tracking-[0.16em]">{tBar("book")}</span>
                <span className="block text-[0.6rem] uppercase tracking-[0.16em] opacity-70">{tBar("from")}</span>
              </span>
              <span className="flex h-9 w-9 items-center justify-center rounded-full bg-navy-deep text-white">
                <ArrowRight aria-hidden className="h-4 w-4" strokeWidth={1.5} />
              </span>
            </Link>
          )}
          <a
            href={contact.whatsapp}
            target="_blank"
            rel="noreferrer"
            aria-label={t("whatsapp")}
            tabIndex={shown ? undefined : -1}
            className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1f6f5c]"
          >
            <WhatsAppIcon className="h-5 w-5" />
          </a>
          <a
            href={contact.tel}
            aria-label={t("call")}
            tabIndex={shown ? undefined : -1}
            className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-white/20"
          >
            <Phone aria-hidden className="h-5 w-5" strokeWidth={1.5} />
          </a>
        </div>
      </div>
    </>
  );
}
