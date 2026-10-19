import Image from "next/image";
import { getTranslations } from "next-intl/server";
import type { Homepage } from "@/lib/homepage";
import { ImageReveal } from "../motion/ImageReveal";
import { Parallax } from "../motion/Parallax";
import { Reveal } from "../motion/Reveal";
import { TextReveal } from "../motion/TextReveal";
import { TextLink } from "../ui/Button";
import { Eyebrow, GoldRule } from "../ui/Section";

export async function IslandSplit({ images }: { images: Homepage["saintMartin"] }) {
  const t = await getTranslations("home");

  return (
    <section className="relative overflow-hidden bg-white py-[var(--section-y)]">
      <div className="container-lux grid items-center gap-16 lg:grid-cols-[1.1fr_1fr] lg:gap-24">
        <div className="relative order-2 pb-20 lg:order-1 lg:pb-28">
          <ImageReveal from="left" radius="32px" className="aspect-[5/4] bg-sand lg:aspect-[6/5]">
            <Parallax speed={60} className="absolute -inset-y-10 inset-x-0">
              <div className="relative h-full w-full">
                <Image
                  src={images.primary.src}
                  alt={images.primary.alt}
                  fill
                  sizes="(min-width: 1024px) 50vw, 92vw"
                  className="object-cover"
                />
              </div>
            </Parallax>
          </ImageReveal>
          <Parallax speed={-90} className="absolute bottom-0 right-4 w-[46%] md:right-10 md:w-[40%]">
            <Reveal direction="none" delay={0.25} className="rounded-full bg-white p-2.5 shadow-[var(--shadow-lift)]">
              <ImageReveal from="down" delay={0.3} radius="9999px" className="frame-hairline aspect-square bg-sand text-white">
                <Image src={images.secondary.src} alt={images.secondary.alt} fill sizes="(min-width: 1024px) 20vw, 46vw" className="object-cover" />
              </ImageReveal>
            </Reveal>
          </Parallax>
          <p className="absolute bottom-6 left-0 hidden text-[0.62rem] uppercase tracking-[0.3em] text-muted md:block">
            {t("islandCaption")}
          </p>
        </div>

        <div className="order-1 max-w-lg lg:order-2">
          <Reveal>
            <Eyebrow line>{t("islandEyebrow")}</Eyebrow>
          </Reveal>
          <TextReveal
            text={t("islandLead")}
            accent={t("islandAccent")}
            className="display-lg mt-6 font-light text-navy-deep"
          />
          <Reveal delay={0.15}>
            <GoldRule className="mt-8" />
          </Reveal>
          <Reveal delay={0.2}>
            <p className="lede mt-8 text-ink/80">{t("islandBody")}</p>
          </Reveal>
          <Reveal delay={0.28}>
            <p className="mt-5 leading-relaxed text-muted">{t("islandBody2")}</p>
          </Reveal>
          <Reveal delay={0.36} className="mt-10">
            <TextLink href="/saint-martin">{t("islandCta")}</TextLink>
          </Reveal>
        </div>
      </div>
    </section>
  );
}
