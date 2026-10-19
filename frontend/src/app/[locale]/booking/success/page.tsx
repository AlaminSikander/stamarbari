import { getTranslations, setRequestLocale } from "next-intl/server";
import { Suspense } from "react";
import { BookingSuccess } from "@/components/booking/BookingSuccess";
import { PageHero } from "@/components/PageHero";
import { Container, Section } from "@/components/ui/Section";

export default async function SuccessPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("bookingStatus");
  const tNav = await getTranslations("nav");

  return (
    <>
      <PageHero
        compact
        route="booking"
        title={t("successTitle")}
        accent={t("successAccent")}
        crumbs={[{ label: tNav("bookNow"), href: "/booking" }]}
      />
      <Section tone="ivory" overlap>
        <Container>
          <Suspense>
            <BookingSuccess />
          </Suspense>
        </Container>
      </Section>
    </>
  );
}
