import type { MetadataRoute } from "next";

const site = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

const paths = [
  "",
  "/rooms",
  "/experience",
  "/gallery",
  "/offers",
  "/packages",
  "/about",
  "/our-story",
  "/saint-martin",
  "/saint-martin/travel-guide",
  "/blog",
  "/faq",
  "/contact",
  "/privacy-policy",
  "/terms",
  "/cancellation-policy",
  "/credits",
];

export default function sitemap(): MetadataRoute.Sitemap {
  return paths.flatMap((path) => [
    {
      url: `${site}${path}`,
      alternates: { languages: { en: `${site}${path}`, bn: `${site}/bn${path}` } },
    },
  ]);
}
