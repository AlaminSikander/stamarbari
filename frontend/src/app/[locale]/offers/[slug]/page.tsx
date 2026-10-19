import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { ArticleBody } from "@/components/ArticleBody";
import { PageHero } from "@/components/PageHero";
import { ButtonLink } from "@/components/ui/Button";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import type { Offer } from "@/lib/types";

export default async function OfferDetailPage({
  params,
}: {
  params: Promise<{ locale: string; slug: string }>;
}) {
  const { locale, slug } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("offersPage");
  const tNav = await getTranslations("nav");
  let offers: Offer[] = [];
  try {
    offers = await apiGet<Offer[]>("/offers", locale);
  } catch {
    offers = [];
  }
  const offer = offers.find((item) => item.slug === slug);
  if (!offer) notFound();

  const name = locale === "bn" && offer.name_bn ? offer.name_bn : offer.name_en;
  const description = locale === "bn" && offer.description_bn ? offer.description_bn : offer.description_en;

  return (
    <>
      <PageHero
        compact
        route="offers"
        image={offer.image}
        eyebrow={t("eyebrow")}
        title={name}
        subtitle={t("subtitle")}
        crumbs={[{ label: tNav("offers"), href: "/offers" }]}
      />
      <Section tone="ivory" overlap>
        <Container>
          <ArticleBody
            body={description}
            empty={t("subtitle")}
            backHref="/offers"
            backLabel={t("back")}
            actions={
              <ButtonLink href="/booking" arrow>
                {t("book")}
              </ButtonLink>
            }
          />
        </Container>
      </Section>
    </>
  );
}
