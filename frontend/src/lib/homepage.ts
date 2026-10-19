import { cache } from "react";
import { getTranslations } from "next-intl/server";
import { apiGet, mediaUrl } from "./api";
import { pageHeroDefaults, stock, type StockImage } from "./stock";

/** Shape returned by GET /api/v1/homepage. Every field is optional on purpose. */
type HomepageApi = {
  hero?: {
    media_type?: "image" | "video";
    image?: string | null;
    video?: string | null;
    poster?: string | null;
    eyebrow?: string | null;
    title_lead?: string | null;
    title_accent?: string | null;
    body?: string | null;
  };
  welcome?: { image?: string | null; stats?: { value: string; label: string | null }[] };
  experiences?: { image: string | null; title: string | null; body: string | null }[];
  saint_martin?: { image_primary?: string | null; image_secondary?: string | null };
  final_cta?: { image?: string | null };
  page_heroes?: Record<string, string | null>;
};

export type SiteImage = { src: string; alt: string };

export type Homepage = {
  hero: {
    image: SiteImage;
    video: string | null;
    poster: string | null;
    eyebrow: string;
    titleLead: string;
    titleAccent: string;
    body: string;
  };
  welcome: { image: SiteImage; stats: { value: string; label: string }[] };
  experiences: { image: SiteImage; title: string; body: string }[];
  saintMartin: { primary: SiteImage; secondary: SiteImage };
  finalCta: { image: SiteImage };
  pageHeroes: Record<string, string | null>;
};

const fetchHomepage = cache(async (locale: string): Promise<HomepageApi> => {
  try {
    return await apiGet<HomepageApi>("/homepage", locale);
  } catch {
    return {};
  }
});

function pick(uploaded: string | null | undefined, fallback: StockImage, alt?: string): SiteImage {
  const src = mediaUrl(uploaded);
  return src ? { src, alt: alt ?? fallback.alt } : { src: fallback.src, alt: alt ?? fallback.alt };
}

const experienceImages = [stock.turquoiseShore, stock.chhera, stock.coral, stock.seafood, stock.coconutPalms, stock.lighthouse];

/** Homepage content: admin uploads and copy win, curated stock imagery and messages fill the gaps. */
export const getHomepage = cache(async (locale: string): Promise<Homepage> => {
  const [api, tHero, tHome] = await Promise.all([
    fetchHomepage(locale),
    getTranslations({ locale, namespace: "hero" }),
    getTranslations({ locale, namespace: "home" }),
  ]);
  const hero = api.hero ?? {};
  const defaultStats = tHome.raw("stats") as { value: string; label: string }[];
  const defaultExperiences = tHome.raw("experiences") as { title: string; body: string }[];

  const adminStats = (api.welcome?.stats ?? []).filter((s) => s.value);
  const adminExperiences = (api.experiences ?? []).filter((e) => e.title);

  return {
    hero: {
      image: pick(hero.image ?? hero.poster, stock.heroSunrise),
      video: hero.media_type === "video" ? mediaUrl(hero.video) : null,
      poster: mediaUrl(hero.poster) ?? mediaUrl(hero.image),
      eyebrow: hero.eyebrow || tHero("kicker"),
      titleLead: hero.title_lead || tHero("titleLead"),
      titleAccent: hero.title_accent || tHero("titleAccent"),
      body: hero.body || tHero("body"),
    },
    welcome: {
      image: pick(api.welcome?.image, stock.coconutGarden),
      stats: adminStats.length
        ? adminStats.map((s) => ({ value: s.value, label: s.label ?? "" }))
        : defaultStats,
    },
    experiences: adminExperiences.length
      ? adminExperiences.map((e, i) => ({
          image: pick(e.image, experienceImages[i % experienceImages.length], e.title ?? undefined),
          title: e.title ?? "",
          body: e.body ?? "",
        }))
      : defaultExperiences.map((e, i) => ({
          image: { src: experienceImages[i].src, alt: experienceImages[i].alt },
          title: e.title,
          body: e.body,
        })),
    saintMartin: {
      primary: pick(api.saint_martin?.image_primary, stock.aerialIsland),
      secondary: pick(api.saint_martin?.image_secondary, stock.keya),
    },
    finalCta: { image: pick(api.final_cta?.image, stock.sunsetBoat) },
    pageHeroes: api.page_heroes ?? {},
  };
});

/** Hero image for a secondary page: CMS page image, then admin page-hero image, then stock default. */
export async function getPageHeroImage(route: string, locale: string, cmsImage?: string | null): Promise<SiteImage> {
  const fallback = pageHeroDefaults[route] ?? stock.sandRipples;
  const fromCms = mediaUrl(cmsImage);
  if (fromCms) return { src: fromCms, alt: fallback.alt };
  const api = await fetchHomepage(locale);
  return pick(api.page_heroes?.[route], fallback);
}
