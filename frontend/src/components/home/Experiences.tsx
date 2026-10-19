import Image from "next/image";
import { getTranslations } from "next-intl/server";
import type { Homepage } from "@/lib/homepage";
import { Reveal, Stagger, StaggerItem } from "../motion/Reveal";
import { TextLink } from "../ui/Button";
import { Ornament, SectionHeading } from "../ui/Section";

export async function Experiences({ items, showLink = true }: { items: Homepage["experiences"]; showLink?: boolean }) {
  const t = await getTranslations("home");
  const nav = await getTranslations("nav");

  return (
    <section className="rounded-top-lux grain relative z-10 -mt-10 overflow-hidden bg-navy-deep py-[var(--section-y)] text-white md:-mt-14">
      <div
        aria-hidden
        className="pointer-events-none absolute -right-40 -top-40 h-[520px] w-[520px] rounded-full bg-teal/20 blur-[120px]"
      />
      <div className="container-lux relative">
        <div className="flex flex-col items-start justify-between gap-10 md:flex-row md:items-end">
          <SectionHeading
            align="left"
            tone="light"
            eyebrow={t("experiencesEyebrow")}
            title={t("experiencesLead")}
            accent={t("experiencesAccent")}
            intro={t("experiencesIntro")}
          />
          {showLink ? (
            <Reveal delay={0.3} className="shrink-0 pb-2">
              <TextLink href="/experience" tone="light">
                {nav("experience")}
              </TextLink>
            </Reveal>
          ) : null}
        </div>

        <Stagger as="ul" className="snap-row mt-14 md:mt-20 md:grid-cols-2 lg:grid-cols-3 md:gap-6" step={0.1}>
          {items.map((item, i) => (
            <StaggerItem as="li" key={`${item.title}-${i}`} className={i % 3 === 1 ? "lg:translate-y-12" : ""}>
              <article className="group relative aspect-[3/4] overflow-hidden rounded-[28px] bg-navy md:aspect-[4/5]">
                <div className="img-zoom absolute inset-0">
                  <Image
                    src={item.image.src}
                    alt={item.image.alt}
                    fill
                    sizes="(min-width: 1024px) 30vw, (min-width: 768px) 45vw, 82vw"
                    className="object-cover"
                  />
                </div>
                <div
                  aria-hidden
                  className="absolute inset-0 bg-gradient-to-t from-navy-deep/95 via-navy-deep/30 to-transparent transition-opacity duration-700 group-hover:opacity-100 md:opacity-80"
                />
                <span className="absolute left-6 top-6 font-sans text-[0.65rem] tracking-[0.24em] text-white/80">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <div className="absolute inset-x-0 bottom-0 p-6 md:p-8">
                  <h3 className="font-display text-[1.9rem] font-light leading-tight transition-transform duration-700 ease-lux md:translate-y-8 md:group-hover:translate-y-0">
                    {item.title}
                  </h3>
                  <p className="mt-3 max-w-xs text-sm leading-relaxed text-white/75 transition-[opacity,transform] duration-700 ease-lux md:translate-y-6 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100">
                    {item.body}
                  </p>
                  <span
                    aria-hidden
                    className="mt-5 block h-px w-10 origin-left bg-gold transition-transform duration-700 ease-lux group-hover:scale-x-[2.4]"
                  />
                </div>
              </article>
            </StaggerItem>
          ))}
        </Stagger>
        <p className="mt-6 text-center text-[0.62rem] uppercase tracking-[0.3em] text-white/50 md:hidden">{t("experiencesSwipe")}</p>
        <Ornament tone="light" className="mt-16 hidden md:flex lg:mt-28" />
      </div>
    </section>
  );
}
