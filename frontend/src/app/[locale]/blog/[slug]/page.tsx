import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { ArticleBody } from "@/components/ArticleBody";
import { PageHero } from "@/components/PageHero";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import { stripHtml } from "@/lib/html";

type Post = {
  slug: string;
  title_en: string;
  title_bn?: string | null;
  content_en?: string | null;
  content_bn?: string | null;
  featured_image?: string | null;
  published_at?: string | null;
};

export default async function BlogPostPage({
  params,
}: {
  params: Promise<{ locale: string; slug: string }>;
}) {
  const { locale, slug } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("blogPage");
  const tNav = await getTranslations("nav");
  let post: Post | null = null;
  try {
    post = await apiGet<Post>(`/blog/${slug}`, locale);
  } catch {
    post = null;
  }
  if (!post) notFound();

  const title = locale === "bn" && post.title_bn ? post.title_bn : post.title_en;
  const body = locale === "bn" && post.content_bn ? post.content_bn : post.content_en;
  const words = stripHtml(body ?? "").split(" ").filter(Boolean).length;
  const minutes = Math.max(1, Math.round(words / 200));
  const date = post.published_at
    ? new Intl.DateTimeFormat(locale === "bn" ? "bn-BD" : "en-GB", { day: "numeric", month: "long", year: "numeric" }).format(
        new Date(post.published_at),
      )
    : null;

  return (
    <>
      <PageHero
        compact
        route="blog"
        image={post.featured_image}
        eyebrow={[date, t("readTime", { minutes })].filter(Boolean).join(" · ")}
        title={title}
        crumbs={[{ label: tNav("blog"), href: "/blog" }]}
      />
      <Section tone="ivory" overlap>
        <Container>
          <ArticleBody body={body} empty={t("empty")} backHref="/blog" backLabel={t("back")} />
        </Container>
      </Section>
    </>
  );
}
