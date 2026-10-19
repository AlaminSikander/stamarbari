import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { FinalCta } from "@/components/home/FinalCta";
import { PageHero } from "@/components/PageHero";
import { RoomsGrid } from "@/components/RoomsGrid";
import { ButtonLink } from "@/components/ui/Button";
import { EmptyState } from "@/components/ui/EmptyState";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import { contact } from "@/lib/contact";
import { getHomepage } from "@/lib/homepage";
import type { RoomSummary } from "@/lib/types";

export const metadata: Metadata = {
  title: "Rooms",
  description: "Garden-view rooms at S T Amar Bari Resort on Saint Martin Island.",
};

export default async function RoomsPage({
  params,
}: {
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("roomsPage");
  const tCommon = await getTranslations("common");
  const home = await getHomepage(locale);
  let rooms: RoomSummary[] = [];
  try {
    rooms = await apiGet<RoomSummary[]>("/rooms", locale);
  } catch {
    rooms = [];
  }

  return (
    <>
      <PageHero route="rooms" eyebrow={t("eyebrow")} title={t("title")} subtitle={t("subtitle")} />
      <Section tone="ivory" overlap>
        <Container>
          {rooms.length ? (
            <RoomsGrid rooms={rooms} />
          ) : (
            <EmptyState
              actions={
                <>
                  <ButtonLink href={contact.tel} variant="ink">
                    {tCommon("call")}
                  </ButtonLink>
                  <ButtonLink href={contact.whatsapp} variant="whatsapp">
                    {tCommon("whatsapp")}
                  </ButtonLink>
                </>
              }
            >
              {t("empty")}
            </EmptyState>
          )}
        </Container>
      </Section>
      <FinalCta image={home.finalCta.image} />
    </>
  );
}
