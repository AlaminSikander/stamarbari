import { BadgeCheck, HeartHandshake, House, Leaf, MapPin, Phone, Sprout, Sun } from "lucide-react";
import Image from "next/image";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { FinalCta } from "@/components/home/FinalCta";
import { ImageReveal } from "@/components/motion/ImageReveal";
import { Parallax } from "@/components/motion/Parallax";
import { Reveal, Stagger, StaggerItem } from "@/components/motion/Reveal";
import { PageHero } from "@/components/PageHero";
import { RichHtml } from "@/components/RichHtml";
import { ButtonLink, TextLink } from "@/components/ui/Button";
import { Container, Ornament, Section, SectionHeading } from "@/components/ui/Section";
import { apiGet, mediaUrl } from "@/lib/api";
import { getHomepage } from "@/lib/homepage";
import { looksLikeHtml } from "@/lib/html";
import { stock } from "@/lib/stock";

type CmsBody = { title: string; body: string; hero_image?: string | null };

const valueIcons = [House, HeartHandshake, BadgeCheck, Leaf];
const welcomeIcons = [MapPin, Sprout, Phone, Sun];

/** Vertical hairlines between cells for the 2-column and 4-column layouts. */
function dividers(index: number) {
  const md = index % 2 === 0 ? "md:border-r" : "md:border-r-0";
  const lg = index % 4 === 3 ? "lg:border-r-0" : "lg:border-r";
  return `border-white/10 ${md} ${lg}`;
}

export async function AboutPage({ locale }: { locale: string }) {
  setRequestLocale(locale);
  const t = await getTranslations("aboutPage");
  const tNav = await getTranslations("nav");

  const [about, story, home] = await Promise.all([
    apiGet<CmsBody>("/pages/about", locale).catch(() => null),
    apiGet<CmsBody>("/pages/our-story", locale).catch(() => null),
    getHomepage(locale),
  ]);

  const storyText = (about?.body || story?.body || "").trim();
  const paragraphs = storyText ? storyText.split(/\n+/).filter(Boolean) : [t("storyFallback")];
  const uploaded = mediaUrl(about?.hero_image);
  const primary = uploaded ? { src: uploaded, alt: t("storyTitle") } : stock.islandLife;
  const secondary = stock.pandanus;

  const values = t.raw("values") as { title: string; body: string }[];
  const welcome = t.raw("welcome") as { title: string; role: string }[];

  return (
    <>
      <PageHero route="about" image={about?.hero_image} eyebrow={t("storyEyebrow")} title={t("title")} subtitle={t("subtitle")} />

      <Section tone="ivory" overlap>
        <Container>
          <div className="grid items-center gap-16 lg:grid-cols-[1fr_1.05fr] lg:gap-24">
            <div>
              <SectionHeading align="left" eyebrow={t("storyEyebrow")} title={t("storyTitle")} />
              <Reveal delay={0.2} className="mt-8 max-w-xl">
                {looksLikeHtml(storyText) ? (
                  <RichHtml html={storyText} className="text-[1.075rem] leading-[1.85] text-navy/75" />
                ) : (
                  <div className="space-y-5 text-[1.075rem] leading-[1.85] text-navy/75">
                    {paragraphs.map((paragraph, i) => (
                      <p key={`${paragraph.slice(0, 32)}-${i}`}>{paragraph}</p>
                    ))}
                  </div>
                )}
              </Reveal>
              <Reveal delay={0.3} className="mt-10">
                <TextLink href="/our-story">{tNav("ourStory")}</TextLink>
              </Reveal>
            </div>

            <div className="relative pb-16 lg:pb-24">
              <ImageReveal radius="32px" className="aspect-[4/5] w-[86%] bg-sand">
                <Image src={primary.src} alt={primary.alt} fill sizes="(min-width: 1024px) 42vw, 80vw" className="object-cover" />
              </ImageReveal>
              <Parallax speed={70} className="absolute bottom-0 right-0 w-[48%]">
                <div className="rounded-[26px] bg-ivory p-2 shadow-[0_30px_60px_-30px_rgba(10,34,54,0.5)]">
                  <ImageReveal radius="20px" from="left" delay={0.3} className="aspect-[3/4] bg-sand">
                    <Image src={secondary.src} alt={secondary.alt} fill sizes="(min-width: 1024px) 22vw, 44vw" className="object-cover" />
                  </ImageReveal>
                </div>
              </Parallax>
              <span aria-hidden className="absolute -left-4 top-10 hidden font-display text-[0.7rem] uppercase tracking-[0.4em] text-gold [writing-mode:vertical-rl] lg:block">
                {t("welcomeEyebrow")}
              </span>
            </div>
          </div>
        </Container>
      </Section>

      <section className="rounded-top-lux grain relative z-10 -mt-10 overflow-hidden bg-navy-deep py-[var(--section-y)] text-white md:-mt-14">
        <div aria-hidden className="pointer-events-none absolute -right-40 -top-32 h-[480px] w-[480px] rounded-full bg-teal/20 blur-[120px]" />
        <Container className="relative">
          <SectionHeading tone="light" eyebrow={t("valuesEyebrow")} title={t("valuesTitle")} intro={t("valuesIntro")} />
          <Stagger as="ul" className="mt-16 grid border-t border-white/10 md:mt-20 md:grid-cols-2 lg:grid-cols-4">
            {values.map((value, i) => {
              const Icon = valueIcons[i % valueIcons.length];
              return (
                <StaggerItem as="li" key={value.title} className={`group border-b border-white/10 px-2 py-10 md:px-8 ${dividers(i)}`}>
                  <span className="font-sans text-[0.62rem] tracking-[0.24em] text-white/40">{String(i + 1).padStart(2, "0")}</span>
                  <span className="mt-6 flex h-14 w-14 items-center justify-center rounded-full border border-gold/40 text-gold transition-colors duration-500 group-hover:bg-gold group-hover:text-navy-deep">
                    <Icon aria-hidden className="h-5 w-5" strokeWidth={1.3} />
                  </span>
                  <h3 className="mt-6 font-display text-[1.75rem] font-light">{value.title}</h3>
                  <p className="mt-3 text-[0.95rem] leading-relaxed text-white/65">{value.body}</p>
                </StaggerItem>
              );
            })}
          </Stagger>
          <Reveal className="mx-auto mt-20 max-w-3xl text-center md:mt-28">
            <Ornament />
            <p className="eyebrow mt-8 text-[#e4c891]">{t("missionTitle")}</p>
            <blockquote className="mt-6 font-display text-[clamp(1.7rem,1.2rem+1.8vw,2.8rem)] font-light italic leading-[1.25] text-white/90">
              “{t("mission")}”
            </blockquote>
          </Reveal>
        </Container>
      </section>

      <Section tone="ivory" overlap>
        <Container>
          <SectionHeading eyebrow={t("welcomeEyebrow")} title={t("welcomeTitle")} intro={t("welcomeIntro")} />
          <Stagger as="ol" className="mt-16 grid gap-px overflow-hidden rounded-[28px] border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
            {welcome.map((item, i) => {
              const Icon = welcomeIcons[i % welcomeIcons.length];
              return (
                <StaggerItem as="li" key={item.title} className="group bg-ivory p-8 transition-colors duration-500 hover:bg-white">
                  <div className="flex items-center justify-between">
                    <Icon aria-hidden className="h-6 w-6 text-gold" strokeWidth={1.25} />
                    <span className="font-sans text-[0.62rem] tracking-[0.24em] text-muted">{String(i + 1).padStart(2, "0")}</span>
                  </div>
                  <h3 className="mt-10 font-display text-[1.6rem] font-light leading-tight text-navy-deep">{item.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-muted">{item.role}</p>
                </StaggerItem>
              );
            })}
          </Stagger>
          <Reveal className="mt-14 flex flex-wrap justify-center gap-3">
            <ButtonLink href="/booking" arrow>
              {t("book")}
            </ButtonLink>
            <ButtonLink href="/contact" variant="ghost-dark">
              {t("contact")}
            </ButtonLink>
          </Reveal>
        </Container>
      </Section>

      <FinalCta image={home.finalCta.image} />
    </>
  );
}
