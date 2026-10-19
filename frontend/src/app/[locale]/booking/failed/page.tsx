import { getTranslations, setRequestLocale } from "next-intl/server";
import { Suspense } from "react";
import { BookingFailed } from "@/components/booking/BookingFailed";
import { PageHero } from "@/components/PageHero";
import { Container, Section } from "@/components/ui/Section";

export default async function FailedPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("bookingStatus");
  const tNav = await getTranslations("nav");

  return (
    <>
      <PageHero
        compact
        route="booking"
        title={t("failedTitle")}
        accent={t("failedAccent")}
        crumbs={[{ label: tNav("bookNow"), href: "/booking" }]}
      />
      <Section tone="ivory" overlap>
        <Container>
          <Suspense>
            <BookingFailed />
          </Suspense>
        </Container>
      </Section>
    </>
  );
}
