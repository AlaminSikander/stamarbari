import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import CmsPage from "@/components/CmsPage";

export const metadata: Metadata = {
  title: "Our Story",
  description: "How S T Amar Bari became a home on Saint Martin Island.",
};

export default async function Page({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("cmsPages");
  const tNav = await getTranslations("nav");
  return (
    <CmsPage
      locale={locale}
      slug="our-story"
      route="our-story"
      compact={false}
      eyebrow={t("ourStoryEyebrow")}
      titleFallback={t("ourStoryTitle")}
      subtitle={t("ourStorySubtitle")}
      crumbs={[{ label: tNav("about"), href: "/about" }]}
    />
  );
}
