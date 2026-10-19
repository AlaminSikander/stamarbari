import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { GalleryBrowser, type GalleryCategory } from "@/components/GalleryBrowser";
import { PageHero } from "@/components/PageHero";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";

export const metadata: Metadata = {
  title: "Gallery",
  description: "Photographs of S T Amar Bari Resort on Saint Martin Island.",
};

export default async function GalleryPage({
  params,
}: {
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("galleryPage");
  let categories: GalleryCategory[] = [];
  try {
    categories = await apiGet<GalleryCategory[]>("/gallery", locale);
  } catch {
    categories = [];
  }

  return (
    <>
      <PageHero
        route="gallery"
        eyebrow={t("eyebrow")}
        title={t("title")}
        subtitle={t("subtitle")}
      />
      <Section tone="ivory" overlap>
        <Container>
          <GalleryBrowser categories={categories} locale={locale} allLabel={t("all")} empty={t("empty")} />
        </Container>
      </Section>
    </>
  );
}
