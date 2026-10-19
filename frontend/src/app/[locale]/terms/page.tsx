import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import CmsPage from "@/components/CmsPage";

export const metadata: Metadata = {
  title: "Terms",
  description: "Booking terms for S T Amar Bari Resort on Saint Martin Island.",
};

export default async function Page({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("legalPage");
  return (
    <CmsPage
      locale={locale}
      slug="terms"
      titleFallback={t("termsTitle")}
      subtitle={t("termsSubtitle")}
      empty={t("empty")}
      eyebrow={t("eyebrow")}
    />
  );
}
