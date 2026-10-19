import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import CmsPage from "@/components/CmsPage";
import { Experiences } from "@/components/home/Experiences";
import { getHomepage } from "@/lib/homepage";

export const metadata: Metadata = {
  title: "Experience",
  description: "Island days around S T Amar Bari Resort on Saint Martin Island.",
};

export default async function Page({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("cmsPages");
  const home = await getHomepage(locale);
  return (
    <>
      <CmsPage
        locale={locale}
        slug="experience"
        route="experience"
        compact={false}
        eyebrow={t("experienceEyebrow")}
        titleFallback={t("experienceTitle")}
        subtitle={t("experienceSubtitle")}
      />
      <Experiences items={home.experiences} showLink={false} />
    </>
  );
}
