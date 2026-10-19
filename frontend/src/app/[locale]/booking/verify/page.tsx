import { getTranslations, setRequestLocale } from "next-intl/server";
import { Suspense } from "react";
import { BookingVerify, VerifyMessage } from "@/components/booking/BookingVerify";
import { PageHero } from "@/components/PageHero";
import { Container, Section } from "@/components/ui/Section";

export default async function VerifyPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("bookingStatus");
  const tNav = await getTranslations("nav");

  return (
    <>
      <PageHero
        compact
        route="booking"
        title={t("verifyTitle")}
        accent={t("verifyAccent")}
        crumbs={[{ label: tNav("bookNow"), href: "/booking" }]}
      />
      <Section tone="ivory" overlap>
        <Container>
          <Suspense fallback={<VerifyMessage text={t("verifying")} />}>
            <BookingVerify />
          </Suspense>
        </Container>
      </Section>
    </>
  );
}
