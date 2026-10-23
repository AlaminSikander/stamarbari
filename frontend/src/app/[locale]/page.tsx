import { getTranslations, setRequestLocale } from "next-intl/server";
import { Hero } from "@/components/Hero";
import { Experiences } from "@/components/home/Experiences";
import { FinalCta } from "@/components/home/FinalCta";
import { GalleryMosaic } from "@/components/home/GalleryMosaic";
import { IslandSplit } from "@/components/home/IslandSplit";
import { OffersStrip } from "@/components/home/OffersStrip";
import { RoomsShowcase, type ShowcaseRoom } from "@/components/home/RoomsShowcase";
import { Testimonials } from "@/components/home/Testimonials";
import { Welcome } from "@/components/home/Welcome";
import { WhyDirect } from "@/components/home/WhyDirect";
import { Reveal } from "@/components/motion/Reveal";
import { ButtonLink } from "@/components/ui/Button";
import { SectionHeading } from "@/components/ui/Section";
import { apiGet, formatBdt, mediaUrl } from "@/lib/api";
import { contact } from "@/lib/contact";
import { getHomepage, type SiteImage } from "@/lib/homepage";
import { looksLikeHtml, stripHtml } from "@/lib/html";
import { roomImage } from "@/lib/rooms";
import { stock } from "@/lib/stock";
import type { Offer, RoomSummary, Testimonial } from "@/lib/types";

type CmsBody = { body: string; hero_image?: string | null };
type GalleryCategory = { items: { id: number; path: string; alt_en?: string | null; alt_bn?: string | null }[] };

const galleryFallbacks = [stock.turquoiseShore, stock.palmBeach, stock.coral, stock.sandRipples, stock.islandShore, stock.beachChairs];

async function safe<T>(promise: Promise<T>, fallback: T): Promise<T> {
  try {
    return await promise;
  } catch {
    return fallback;
  }
}

function paragraphs(body: string): string[] {
  const parts = looksLikeHtml(body) ? body.split(/<\/p>|<br\s*\/?>/i).map(stripHtml) : body.split(/\n+/);
  return parts.map((p) => p.trim()).filter(Boolean).slice(0, 2);
}

export default async function HomePage({
  params,
}: {
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("homePage");
  const tHome = await getTranslations("home");

  const [home, rooms, about, categories, offers, testimonials] = await Promise.all([
    getHomepage(locale),
    safe(apiGet<RoomSummary[]>("/rooms", locale), []),
    safe<CmsBody | null>(apiGet<CmsBody>("/pages/about", locale), null),
    safe(apiGet<GalleryCategory[]>("/gallery", locale), []),
    safe(apiGet<Offer[]>("/offers", locale), []),
    safe(apiGet<Testimonial[]>("/testimonials", locale), []),
  ]);

  const story = paragraphs(about?.body || t("storyFallback"));

  const stats = rooms.length
    ? [...home.welcome.stats.slice(0, 3), { value: String(rooms.length), label: tHome("roomCategories") }]
    : home.welcome.stats;

  const showcase: ShowcaseRoom[] = rooms.map((room) => ({
    slug: room.slug,
    name: room.name,
    image: roomImage(room),
    guests: room.max_adults,
    price: room.starting_price ? formatBdt(room.starting_price) : null,
    view: room.room_type_label ?? room.view,
  }));

  const uploaded: SiteImage[] = categories
    .flatMap((category) => category.items)
    .filter((item) => item.path)
    .map((item) => ({
      src: mediaUrl(item.path) ?? item.path,
      alt: (locale === "bn" ? item.alt_bn : null) ?? item.alt_en ?? "",
    }));
  const photos = [...uploaded, ...galleryFallbacks.map((img) => ({ src: img.src, alt: img.alt }))].slice(0, 6);

  const quotes = testimonials.map((item) => ({
    quote: locale === "bn" && item.quote_bn ? item.quote_bn : item.quote_en,
    name: item.guest_name,
  }));

  const schema = {
    "@context": "https://schema.org",
    "@type": "LodgingBusiness",
    name: "S T Amar Bari Resort",
    description: "Your Home on the Island — Saint Martin, Bangladesh",
    telephone: "+8801839425157",
    email: contact.email,
    image: stock.heroSunrise.src,
    url: process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000",
    address: {
      "@type": "PostalAddress",
      streetAddress: "Poschim Para, Ctb Beach Road",
      addressLocality: "Saint Martin Island",
      addressCountry: "BD",
    },
    sameAs: [contact.facebook],
  };

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} />
      <Hero hero={home.hero} bookTitle={t("bookTitle")} />
      <Welcome story={story} image={home.welcome.image} stats={stats} />
      <Experiences items={home.experiences} />

      <section className="rounded-top-lux relative z-10 -mt-10 bg-ivory py-[var(--section-y)] md:-mt-14">
        <div className="container-lux">
          <div className="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
            <SectionHeading
              align="left"
              eyebrow={tHome("roomsEyebrow")}
              title={tHome("roomsLead")}
              accent={tHome("roomsAccent")}
              intro={t("roomsIntro")}
            />
            <Reveal delay={0.3} className="shrink-0 pb-2">
              <ButtonLink href="/rooms" variant="ghost-dark" arrow>
                {t("roomsCta")}
              </ButtonLink>
            </Reveal>
          </div>
          <div className="mt-14 md:mt-20">
            {showcase.length ? (
              <RoomsShowcase rooms={showcase} />
            ) : (
              <Reveal>
                <p className="rounded-[28px] border border-line bg-white p-10 text-center text-muted">{tHome("roomsEmpty")}</p>
              </Reveal>
            )}
          </div>
        </div>
      </section>

      <IslandSplit images={home.saintMartin} />
      <GalleryMosaic photos={photos} />
      <WhyDirect />
      {offers.length ? <OffersStrip offers={offers} locale={locale} /> : null}
      {quotes.length ? (
        <section className="rounded-top-lux relative z-10 -mt-10 bg-sand py-[var(--section-y)] md:-mt-14">
          <div className="container-lux">
            <SectionHeading
              eyebrow={tHome("testimonialsEyebrow")}
              title={tHome("testimonialsLead")}
              accent={tHome("testimonialsAccent")}
              size="md"
            />
            <Reveal delay={0.2} className="mt-14">
              <Testimonials items={quotes} />
            </Reveal>
          </div>
        </section>
      ) : null}
      <FinalCta image={home.finalCta.image} overlap={!offers.length && !quotes.length} />
    </>
  );
}
