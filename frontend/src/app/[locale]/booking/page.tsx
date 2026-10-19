import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { Suspense } from "react";
import { BookingPageClient, Sheet } from "@/components/BookingPageClient";
import { PageHero } from "@/components/PageHero";

export const metadata: Metadata = {
  title: "Book your stay",
  description: "Book a garden-view room at S T Amar Bari Resort on Saint Martin Island directly with the resort.",
};

export default async function BookingPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("bookingPage");

  return (
    <>
      <PageHero compact route="booking" title={t("title")} accent={t("accent")} subtitle={t("subtitle")} />
      <Suspense
        fallback={
          <Sheet className="grid gap-8 py-20 md:grid-cols-3">
            {[0, 1, 2].map((i) => (
              <div key={i} className="skeleton aspect-[4/5] rounded-[28px]" />
            ))}
          </Sheet>
        }
      >
        <BookingPageClient />
      </Suspense>
    </>
  );
}
