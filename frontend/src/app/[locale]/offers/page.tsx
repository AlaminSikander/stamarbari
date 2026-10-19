import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { ContentCard } from "@/components/ContentCard";
import { FinalCta } from "@/components/home/FinalCta";
import { Reveal } from "@/components/motion/Reveal";
import { PageHero } from "@/components/PageHero";
import { ButtonLink } from "@/components/ui/Button";
import { EmptyState } from "@/components/ui/EmptyState";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import { getHomepage } from "@/lib/homepage";
import { stock } from "@/lib/stock";
import type { Offer } from "@/lib/types";

export const metadata: Metadata = {
  title: "Offers",
  description: "Current offers from S T Amar Bari Resort on Saint Martin Island.",
};

const fallbacks = [stock.beachChairs, stock.turquoiseShore, stock.palmBeach, stock.sunsetBoat];

export default async function OffersPage({
  params,
}: {
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("offersPage");
  const tNav = await getTranslations("nav");
  const home = await getHomepage(locale);
  let offers: Offer[] = [];
  try {
    offers = await apiGet<Offer[]>("/offers", locale);
  } catch {
    offers = [];
  }

  return (
    <>
      <PageHero route="offers" eyebrow={t("eyebrow")} title={t("title")} subtitle={t("subtitle")} />
      <Section tone="ivory" overlap>
        <Container>
          {offers.length === 0 ? (
            <EmptyState
              actions={
                <ButtonLink href="/booking" arrow>
                  {tNav("bookNow")}
                </ButtonLink>
              }
            >
              {t("empty")}
            </EmptyState>
          ) : (
            <ul className="grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
              {offers.map((offer, i) => {
                const name = locale === "bn" && offer.name_bn ? offer.name_bn : offer.name_en;
                const description = locale === "bn" && offer.description_bn ? offer.description_bn : offer.description_en;
                const value = Number(offer.discount_value ?? 0);
                const badge =
                  value > 0
                    ? offer.discount_type === "percent"
                      ? t("percentOff", { value })
                      : t("amountOff", { value })
                    : t("badge");
                return (
                  <Reveal as="li" key={offer.id} delay={(i % 3) * 0.08}>
                    <ContentCard
                      href={`/offers/${offer.slug}`}
                      title={name}
                      description={description}
                      image={offer.image}
                      fallback={fallbacks[i % fallbacks.length]}
                      badge={badge}
                      cta={t("learnMore")}
                    />
                  </Reveal>
                );
              })}
            </ul>
          )}
        </Container>
      </Section>
      <FinalCta image={home.finalCta.image} />
    </>
  );
}
