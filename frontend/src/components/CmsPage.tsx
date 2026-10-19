import { getTranslations, setRequestLocale } from "next-intl/server";
import type { ReactNode } from "react";
import { ArticleBody } from "@/components/ArticleBody";
import { PageHero } from "@/components/PageHero";
import type { Crumb } from "@/components/PageHeroContent";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";

export default async function CmsPage({
  slug,
  locale,
  titleFallback,
  subtitle,
  empty,
  backHref,
  backLabel,
  route = "legal",
  eyebrow,
  crumbs,
  compact = true,
  children,
}: {
  slug: string;
  locale: string;
  titleFallback: string;
  subtitle?: string;
  empty?: string;
  backHref?: string;
  backLabel?: string;
  /** Page-hero image key (see `pageHeroDefaults`). */
  route?: string;
  eyebrow?: string;
  crumbs?: Crumb[];
  compact?: boolean;
  /** Rendered after the article, e.g. related links. */
  children?: ReactNode;
}) {
  setRequestLocale(locale);
  const tLegal = await getTranslations("legalPage");
  let page: { title: string; body: string; hero_image?: string | null } | null = null;
  try {
    page = await apiGet<{ title: string; body: string; hero_image?: string | null }>(`/pages/${slug}`, locale);
  } catch {
    page = null;
  }

  return (
    <>
      <PageHero
        compact={compact}
        route={route}
        image={page?.hero_image}
        eyebrow={eyebrow}
        title={page?.title || titleFallback}
        subtitle={subtitle}
        crumbs={crumbs}
      />
      <Section tone="ivory" overlap>
        <Container>
          <ArticleBody body={page?.body} empty={empty ?? tLegal("empty")} backHref={backHref} backLabel={backLabel} />
          {children}
        </Container>
      </Section>
    </>
  );
}
