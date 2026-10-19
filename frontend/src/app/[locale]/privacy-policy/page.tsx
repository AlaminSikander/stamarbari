import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import CmsPage from "@/components/CmsPage";

export const metadata: Metadata = {
  title: "Privacy policy",
  description: "How S T Amar Bari Resort handles guest details for bookings.",
};

export default async function Page({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("legalPage");
  return (
    <CmsPage
      locale={locale}
      slug="privacy-policy"
      titleFallback={t("privacyTitle")}
      subtitle={t("privacySubtitle")}
      empty={t("empty")}
      eyebrow={t("eyebrow")}
    />
  );
}
