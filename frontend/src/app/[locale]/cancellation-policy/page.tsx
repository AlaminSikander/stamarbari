import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import CmsPage from "@/components/CmsPage";

export const metadata: Metadata = {
  title: "Cancellation policy",
  description: "Cancellation and missed-ship policy for S T Amar Bari Resort.",
};

export default async function Page({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("legalPage");
  return (
    <CmsPage
      locale={locale}
      slug="cancellation-policy"
      titleFallback={t("cancellationTitle")}
      subtitle={t("cancellationSubtitle")}
      empty={t("empty")}
      eyebrow={t("eyebrow")}
    />
  );
}
