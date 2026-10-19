import { ArrowLeft, Phone } from "lucide-react";
import { getTranslations } from "next-intl/server";
import type { ReactNode } from "react";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { RichHtml } from "@/components/RichHtml";
import { Reveal } from "@/components/motion/Reveal";
import { Link } from "@/i18n/navigation";
import { contact } from "@/lib/contact";

/** Editorial reading column with a sticky "talk to the resort" aside. */
export async function ArticleBody({
  body,
  empty,
  backHref,
  backLabel,
  actions,
}: {
  body?: string | null;
  empty: string;
  backHref?: string;
  backLabel?: string;
  actions?: ReactNode;
}) {
  const t = await getTranslations("cmsPages");
  const tCommon = await getTranslations("common");

  return (
    <div className="grid gap-12 lg:grid-cols-[minmax(0,1fr)_300px] lg:gap-20">
      <Reveal as="article" className="min-w-0 max-w-[46rem]">
        {body ? (
          <RichHtml html={body} className="text-[1.075rem] leading-[1.85] text-navy/80" />
        ) : (
          <p className="lede text-navy/70">{empty}</p>
        )}
        {actions ? <div className="mt-10 flex flex-wrap gap-3">{actions}</div> : null}
        {backHref && backLabel ? (
          <Link
            href={backHref}
            className="group mt-12 flex min-h-11 items-center gap-3 border-t border-line pt-8 text-[0.7rem] font-medium uppercase tracking-[0.2em] text-navy"
          >
            <ArrowLeft aria-hidden className="h-4 w-4 text-gold transition-transform duration-500 ease-lux group-hover:-translate-x-1" strokeWidth={1.5} />
            <span className="link-underline">{backLabel}</span>
          </Link>
        ) : null}
      </Reveal>

      <Reveal as="aside" delay={0.15} className="lg:sticky lg:top-28 lg:self-start">
        <div className="rounded-[24px] border border-line bg-white p-7">
          <span aria-hidden className="block h-px w-10 bg-gold" />
          <p className="mt-5 font-display text-[1.45rem] leading-snug text-navy-deep">{t("asideTitle")}</p>
          <p className="mt-3 text-sm leading-relaxed text-muted">{t("asideBody")}</p>
          <div className="mt-6 grid gap-2">
            <a
              href={contact.whatsapp}
              target="_blank"
              rel="noreferrer"
              className="inline-flex min-h-11 items-center gap-3 rounded-full bg-[#1f6f5c] px-5 text-[0.7rem] font-medium uppercase tracking-[0.16em] text-white transition-colors hover:bg-[#155446]"
            >
              <WhatsAppIcon className="h-4 w-4" />
              {tCommon("whatsapp")}
            </a>
            <a
              href={contact.tel}
              className="inline-flex min-h-11 items-center gap-3 rounded-full border border-line px-5 text-[0.7rem] font-medium uppercase tracking-[0.16em] text-navy transition-colors hover:border-navy-deep hover:bg-navy-deep hover:text-white"
            >
              <Phone aria-hidden className="h-4 w-4" strokeWidth={1.5} />
              {contact.phoneDisplay}
            </a>
          </div>
        </div>
      </Reveal>
    </div>
  );
}
