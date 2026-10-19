import Image from "next/image";
import { getLocale, getTranslations } from "next-intl/server";
import { ViewTransition } from "react";
import { getPageHeroImage } from "@/lib/homepage";
import { PageHeroContent, type Crumb, type HeroAction } from "./PageHeroContent";

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

/**
 * Secondary page hero. Image priority: `image` (CMS), then the admin page-hero
 * image for `route`, then the curated stock default.
 */
export async function PageHero({
  title,
  accent,
  subtitle,
  eyebrow,
  route = "legal",
  image,
  crumbs = [],
  actions,
  compact = false,
  transitionName,
}: {
  title: string;
  accent?: string;
  subtitle?: string;
  eyebrow?: string;
  route?: string;
  image?: string | null;
  /** Intermediate crumbs between Home and the current page. */
  crumbs?: Crumb[];
  actions?: HeroAction[];
  compact?: boolean;
  /** Shared-element name so a card image can morph into this hero. */
  transitionName?: string;
}) {
  const locale = await getLocale();
  const nav = await getTranslations("nav");
  const media = await getPageHeroImage(route, locale, image);
  const trail: Crumb[] = [{ label: nav("home"), href: "/" }, ...crumbs, { label: accent ? `${title} ${accent}` : title }];

  const prefix = locale === "en" ? "" : `/${locale}`;
  const breadcrumbSchema = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: trail.map((crumb, i) => ({
      "@type": "ListItem",
      position: i + 1,
      name: crumb.label,
      ...(crumb.href ? { item: `${SITE_URL}${prefix}${crumb.href === "/" ? "" : crumb.href}` } : {}),
    })),
  };

  const picture = (
    <div className="absolute inset-0 overflow-hidden">
      <div className="ken-burns absolute inset-0">
        <Image src={media.src} alt={media.alt} fill preload sizes="100vw" className="object-cover" />
      </div>
    </div>
  );

  return (
    <section
      className={`relative overflow-hidden bg-navy-deep text-white ${
        compact ? "h-[50svh] min-h-[440px] md:h-[54svh]" : "h-[62svh] min-h-[520px] md:h-[70svh] md:min-h-[600px]"
      }`}
    >
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbSchema) }} />
      {transitionName ? (
        <ViewTransition name={transitionName} share="morph" default="none">
          {picture}
        </ViewTransition>
      ) : (
        picture
      )}
      <div
        aria-hidden
        className="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,34,54,0.62)_0%,rgba(10,34,54,0.42)_40%,rgba(10,34,54,0.5)_70%,rgba(10,34,54,0.85)_100%)]"
      />
      <div
        aria-hidden
        className="absolute inset-0 bg-[radial-gradient(ellipse_60%_55%_at_50%_52%,rgba(10,34,54,0.35),transparent_75%)]"
      />
      <div aria-hidden className="grain absolute inset-0 opacity-60" />
      <PageHeroContent
        trail={trail}
        eyebrow={eyebrow}
        title={title}
        accent={accent}
        subtitle={subtitle}
        actions={actions}
        compact={compact}
      />
    </section>
  );
}
