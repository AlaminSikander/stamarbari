import { ArrowRight } from "lucide-react";
import Image from "next/image";
import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { mediaUrl } from "@/lib/api";
import { stripHtml } from "@/lib/html";
import { stock } from "@/lib/stock";
import type { Offer } from "@/lib/types";
import { Reveal, Stagger, StaggerItem } from "../motion/Reveal";
import { TextLink } from "../ui/Button";
import { SectionHeading } from "../ui/Section";

const fallbacks = [stock.beachChairs, stock.palmBeach, stock.leaningPalm];

export async function OffersStrip({ offers, locale }: { offers: Offer[]; locale: string }) {
  const t = await getTranslations("home");
  const tOffers = await getTranslations("offersPage");

  return (
    <section className="rounded-top-lux relative z-10 -mt-10 bg-ivory py-[var(--section-y)] md:-mt-14">
      <div className="container-lux">
        <div className="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
          <SectionHeading align="left" eyebrow={t("offersEyebrow")} title={t("offersLead")} accent={t("offersAccent")} size="md" />
          <Reveal delay={0.2} className="pb-2">
            <TextLink href="/offers">{t("offersCta")}</TextLink>
          </Reveal>
        </div>
        <Stagger as="ul" className="snap-row mt-14 md:grid-cols-2 md:gap-8 lg:grid-cols-3">
          {offers.slice(0, 3).map((offer, i) => {
            const name = locale === "bn" && offer.name_bn ? offer.name_bn : offer.name_en;
            const description = stripHtml((locale === "bn" && offer.description_bn ? offer.description_bn : offer.description_en) ?? "");
            const value = Number(offer.discount_value ?? 0);
            const badge =
              value > 0
                ? offer.discount_type === "percent"
                  ? tOffers("percentOff", { value })
                  : tOffers("amountOff", { value })
                : tOffers("badge");
            const fallback = fallbacks[i % fallbacks.length];
            const src = mediaUrl(offer.image) ?? fallback.src;
            return (
              <StaggerItem as="li" key={offer.id}>
                <Link href={`/offers/${offer.slug}`} className="group block h-full">
                  <article className="flex h-full flex-col">
                    <div className="img-zoom relative aspect-[4/3] overflow-hidden rounded-[24px] bg-sand">
                      <Image src={src} alt={name} fill sizes="(min-width: 1024px) 30vw, (min-width: 768px) 45vw, 82vw" className="object-cover" />
                      <span className="absolute left-4 top-4 rounded-full bg-ivory/90 px-4 py-1.5 text-[0.64rem] font-medium uppercase tracking-[0.18em] text-navy-deep backdrop-blur">
                        {badge}
                      </span>
                    </div>
                    <h3 className="mt-6 font-display text-2xl font-normal text-navy-deep transition-colors duration-500 group-hover:text-gold-dark md:text-[1.75rem]">
                      {name}
                    </h3>
                    {description ? <p className="mt-3 line-clamp-2 text-sm leading-relaxed text-muted">{description}</p> : null}
                    <span className="mt-5 inline-flex items-center gap-3 text-[0.68rem] font-medium uppercase tracking-[0.2em] text-navy">
                      <span className="link-underline">{tOffers("learnMore")}</span>
                      <ArrowRight aria-hidden className="h-4 w-4 text-gold transition-transform duration-500 group-hover:translate-x-1.5" strokeWidth={1.5} />
                    </span>
                  </article>
                </Link>
              </StaggerItem>
            );
          })}
        </Stagger>
      </div>
    </section>
  );
}
