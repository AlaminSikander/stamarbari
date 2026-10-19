import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import CmsPage from "@/components/CmsPage";
import { ContentCard } from "@/components/ContentCard";
import { FinalCta } from "@/components/home/FinalCta";
import { Reveal } from "@/components/motion/Reveal";
import { PageHero } from "@/components/PageHero";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import { getHomepage } from "@/lib/homepage";
import { stock } from "@/lib/stock";

const guides = [
  { path: "travel-guide", slug: "saint-martin-travel-guide", key: "travelGuide", image: stock.sunsetBoat },
  { path: "tourist-spots", slug: "saint-martin-tourist-spots", key: "touristSpots", image: stock.turquoiseShore },
  { path: "chera-dwip", slug: "saint-martin-chera-dwip", key: "cheraDwip", image: stock.chhera },
  { path: "weather", slug: "saint-martin-weather", key: "weather", image: stock.islandShore },
  { path: "tides", slug: "saint-martin-tides", key: "tides", image: stock.sandRipples },
  { path: "eco-tourism", slug: "saint-martin-eco-tourism", key: "ecoTourism", image: stock.coral },
  { path: "what-to-pack", slug: "saint-martin-what-to-pack", key: "whatToPack", image: stock.beachChairs },
  { path: "first-time-guide", slug: "saint-martin-first-time-guide", key: "firstTime", image: stock.palmBeach },
  { path: "emergency-information", slug: "saint-martin-emergency-information", key: "emergency", image: stock.lighthouse },
  { path: "facilities", slug: "saint-martin-facilities", key: "facilities", image: stock.coconutGarden },
] as const;

type CmsBody = { title: string; body: string; hero_image?: string | null };

export const metadata: Metadata = {
  title: "Saint Martin",
  description: "Honest notes about Saint Martin Island and staying at S T Amar Bari.",
};

export default async function Page({
  params,
}: {
  params: Promise<{ locale: string; section?: string[] }>;
}) {
  const { locale, section } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("saintMartinPage");
  const tNav = await getTranslations("nav");
  const key = section?.[0];

  if (key) {
    const guide = guides.find((item) => item.path === key);
    if (!guide) notFound();
    return (
      <CmsPage
        locale={locale}
        slug={guide.slug}
        route="saint-martin"
        eyebrow={t("guideEyebrow")}
        titleFallback={t(`guides.${guide.key}`)}
        subtitle={t("subtitle")}
        empty={t("empty")}
        backHref="/saint-martin"
        backLabel={t("back")}
        crumbs={[{ label: tNav("saintMartin"), href: "/saint-martin" }]}
      />
    );
  }

  const [pages, home] = await Promise.all([
    Promise.all(
      guides.map(async (guide) => {
        try {
          return await apiGet<CmsBody>(`/pages/${guide.slug}`, locale);
        } catch {
          return null;
        }
      }),
    ),
    getHomepage(locale),
  ]);

  return (
    <>
      <PageHero route="saint-martin" eyebrow={t("eyebrow")} title={t("title")} subtitle={t("subtitle")} />
      <Section tone="ivory" overlap>
        <Container>
          <p className="text-center text-[0.66rem] uppercase tracking-[0.26em] text-muted">{t("count", { count: guides.length })}</p>
          <ul className="mt-12 grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
            {guides.map((guide, index) => {
              const page = pages[index];
              return (
                <Reveal as="li" key={guide.path} delay={(index % 3) * 0.08}>
                  <ContentCard
                    href={`/saint-martin/${guide.path}`}
                    title={page?.title || t(`guides.${guide.key}`)}
                    description={page?.body ?? t("empty")}
                    image={page?.hero_image}
                    fallback={guide.image}
                    badge={t("badge")}
                    cta={t("learnMore")}
                    index={index}
                  />
                </Reveal>
              );
            })}
          </ul>
        </Container>
      </Section>
      <FinalCta image={home.finalCta.image} />
    </>
  );
}
