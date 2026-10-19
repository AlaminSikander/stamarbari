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
import { contact } from "@/lib/contact";
import { getHomepage } from "@/lib/homepage";
import { stock } from "@/lib/stock";

export const metadata: Metadata = {
  title: "Packages",
  description: "Stay packages published by S T Amar Bari Resort on Saint Martin Island.",
};

type Package = {
  id: number;
  slug?: string;
  name_en: string;
  name_bn?: string | null;
  description_en?: string | null;
  description_bn?: string | null;
  image?: string | null;
};

const fallbacks = [stock.palmBeach, stock.sunsetBoat, stock.coconutGarden, stock.turquoiseShore];

export default async function PackagesPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("packagesPage");
  const tCommon = await getTranslations("common");
  const tNav = await getTranslations("nav");
  const home = await getHomepage(locale);
  let packages: Package[] = [];
  try {
    packages = await apiGet<Package[]>("/packages", locale);
  } catch {
    packages = [];
  }

  return (
    <>
      <PageHero route="packages" eyebrow={t("eyebrow")} title={t("title")} subtitle={t("subtitle")} />
      <Section tone="ivory" overlap>
        <Container>
          {packages.length === 0 ? (
            <EmptyState
              actions={
                <>
                  <ButtonLink href="/booking" arrow>
                    {tNav("bookNow")}
                  </ButtonLink>
                  <ButtonLink href={contact.whatsapp} variant="whatsapp">
                    {tCommon("whatsapp")}
                  </ButtonLink>
                </>
              }
            >
              {t("empty")}
            </EmptyState>
          ) : (
            <ul className="grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
              {packages.map((item, i) => (
                <Reveal as="li" key={item.id} delay={(i % 3) * 0.08}>
                  <ContentCard
                    title={locale === "bn" && item.name_bn ? item.name_bn : item.name_en}
                    description={locale === "bn" && item.description_bn ? item.description_bn : item.description_en}
                    image={item.image}
                    fallback={fallbacks[i % fallbacks.length]}
                    badge={t("badge")}
                    excerptLength={180}
                  />
                </Reveal>
              ))}
            </ul>
          )}
        </Container>
      </Section>
      <FinalCta image={home.finalCta.image} />
    </>
  );
}
