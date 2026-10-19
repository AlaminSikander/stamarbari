import Image from "next/image";
import { getTranslations } from "next-intl/server";
import type { SiteImage } from "@/lib/homepage";
import { stock } from "@/lib/stock";
import { Counter } from "../motion/Counter";
import { ImageReveal } from "../motion/ImageReveal";
import { Parallax } from "../motion/Parallax";
import { Reveal } from "../motion/Reveal";
import { TextReveal } from "../motion/TextReveal";
import { TextLink } from "../ui/Button";
import { Eyebrow, GoldRule } from "../ui/Section";

const statCols: Record<number, string> = {
  1: "md:grid-cols-1",
  2: "md:grid-cols-2",
  3: "md:grid-cols-3",
  4: "md:grid-cols-4",
};

export async function Welcome({
  story,
  image,
  stats,
}: {
  story: string[];
  image: SiteImage;
  stats: { value: string; label: string }[];
}) {
  const t = await getTranslations("home");
  const tHome = await getTranslations("homePage");

  return (
    <section id="welcome" className="relative scroll-mt-24 overflow-hidden bg-ivory pb-[var(--section-y)] pt-[calc(var(--section-y)*0.9)]">
      <p
        aria-hidden
        className="pointer-events-none absolute left-1/2 top-[18%] -translate-x-1/2 select-none whitespace-nowrap font-display text-[22vw] font-light italic leading-none text-navy/[0.035] md:top-[12%]"
      >
        {t("welcomeBig")}
      </p>

      <div className="container-lux relative grid items-center gap-16 lg:grid-cols-[1fr_1.05fr] lg:gap-24">
        <div className="max-w-xl">
          <Reveal>
            <Image src="/brand/logo.jpg" alt="" width={64} height={70} className="mb-8 h-16 w-auto mix-blend-multiply" />
          </Reveal>
          <Reveal>
            <Eyebrow line>{t("welcomeEyebrow")}</Eyebrow>
          </Reveal>
          <TextReveal
            text={t("welcomeLead")}
            accent={t("welcomeAccent")}
            className="display-lg mt-6 font-light text-navy-deep"
          />
          <Reveal delay={0.15}>
            <GoldRule className="mt-8" />
          </Reveal>
          <div className="mt-8 space-y-5">
            {story.map((paragraph, i) => (
              <Reveal key={paragraph.slice(0, 32)} delay={0.2 + i * 0.08}>
                <p className={i === 0 ? "lede text-ink/80" : "leading-relaxed text-muted"}>{paragraph}</p>
              </Reveal>
            ))}
          </div>
          <Reveal delay={0.35} className="mt-10 flex flex-wrap items-center gap-x-10 gap-y-6">
            <TextLink href="/about">{tHome("storyCta")}</TextLink>
            <span className="font-display text-lg italic text-muted">— {t("welcomeSignature")}</span>
          </Reveal>
        </div>

        <div className="relative pb-16 pr-6 md:pb-24 md:pr-16">
          <ImageReveal from="right" radius="32px" className="aspect-[4/5] bg-sand">
            <Image src={image.src} alt={image.alt} fill sizes="(min-width: 1024px) 45vw, 92vw" className="object-cover" />
          </ImageReveal>
          <Parallax speed={-70} className="absolute bottom-0 right-0 w-[48%] md:w-[44%]">
            <Reveal direction="none" delay={0.3} className="rounded-[30px] bg-ivory p-2.5 shadow-[var(--shadow-lift)]">
              <ImageReveal from="up" delay={0.35} radius="22px" className="aspect-[3/4] bg-sand">
                <Image src={stock.pandanus.src} alt={stock.pandanus.alt} fill sizes="(min-width: 1024px) 20vw, 44vw" className="object-cover" />
              </ImageReveal>
            </Reveal>
          </Parallax>
          <Reveal delay={0.6} className="absolute -left-4 top-10 hidden md:block">
            <div className="frame-hairline rounded-full bg-navy-deep px-7 py-6 text-center text-white shadow-[var(--shadow-lift)]">
              <span className="block font-display text-3xl italic leading-none text-gold">{t("badgeName")}</span>
              <span className="mt-2 block text-[0.58rem] uppercase tracking-[0.3em] text-white/70">{t("badgeMeaning")}</span>
            </div>
          </Reveal>
        </div>
      </div>

      <div className="container-lux relative mt-20 md:mt-28">
        <Reveal>
          <dl className={`grid grid-cols-2 border-y border-line ${statCols[Math.min(stats.length, 4)]}`}>
            {stats.map((stat, i) => (
              <div
                key={`${stat.value}-${stat.label}`}
                className={`px-4 py-8 text-center md:py-10 ${i % 2 === 1 ? "border-l border-line" : ""} ${
                  i > 0 ? "md:border-l md:border-line" : ""
                } ${i >= 2 ? "border-t border-line md:border-t-0" : ""}`}
              >
                <dt className="sr-only">{stat.label}</dt>
                <dd>
                  <Counter value={stat.value} className="block font-display text-5xl font-light text-navy-deep md:text-6xl" />
                  <span className="mt-3 block text-[0.66rem] uppercase tracking-[0.22em] text-muted">{stat.label}</span>
                </dd>
              </div>
            ))}
          </dl>
        </Reveal>
      </div>
    </section>
  );
}
