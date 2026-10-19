import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { ContentCard } from "@/components/ContentCard";
import { Reveal } from "@/components/motion/Reveal";
import { PageHero } from "@/components/PageHero";
import { EmptyState } from "@/components/ui/EmptyState";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import { stock } from "@/lib/stock";

export const metadata: Metadata = {
  title: "Journal",
  description: "Notes from S T Amar Bari Resort on Saint Martin Island.",
};

type Post = {
  slug: string;
  title_en: string;
  title_bn?: string | null;
  content_en?: string | null;
  content_bn?: string | null;
  featured_image?: string | null;
  published_at?: string | null;
  tags?: string | null;
};

const fallbacks = [stock.leaningPalm, stock.islandLife, stock.coastline, stock.keya, stock.seafood, stock.islandShore];

export default async function BlogPage({
  params,
}: {
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("blogPage");
  let items: Post[] = [];
  try {
    const payload = await apiGet<{ data?: Post[] }>("/blog", locale);
    items = payload.data ?? [];
  } catch {
    items = [];
  }

  return (
    <>
      <PageHero route="blog" eyebrow={t("eyebrow")} title={t("title")} subtitle={t("subtitle")} />
      <Section tone="ivory" overlap>
        <Container>
          {items.length === 0 ? (
            <EmptyState>{t("empty")}</EmptyState>
          ) : (
            <ul className="grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
              {items.map((post, i) => {
                const title = locale === "bn" && post.title_bn ? post.title_bn : post.title_en;
                const description = locale === "bn" && post.content_bn ? post.content_bn : post.content_en;
                const date = post.published_at
                  ? new Intl.DateTimeFormat(locale === "bn" ? "bn-BD" : "en-GB", {
                      day: "numeric",
                      month: "short",
                      year: "numeric",
                    }).format(new Date(post.published_at))
                  : null;
                return (
                  <Reveal as="li" key={post.slug} delay={(i % 3) * 0.08} className={i === 0 ? "md:col-span-2 lg:col-span-2" : ""}>
                    <ContentCard
                      href={`/blog/${post.slug}`}
                      title={title}
                      description={description}
                      image={post.featured_image}
                      fallback={fallbacks[i % fallbacks.length]}
                      badge={t("badge")}
                      meta={date}
                      chips={post.tags ? post.tags.split(",").map((tag) => tag.trim()).filter(Boolean) : []}
                      cta={t("learnMore")}
                      aspect={i === 0 ? "aspect-[16/9]" : "aspect-[4/3]"}
                      excerptLength={i === 0 ? 200 : 110}
                    />
                  </Reveal>
                );
              })}
            </ul>
          )}
        </Container>
      </Section>
    </>
  );
}
