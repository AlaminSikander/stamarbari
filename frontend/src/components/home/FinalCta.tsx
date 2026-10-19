import Image from "next/image";
import { getTranslations } from "next-intl/server";
import { contact } from "@/lib/contact";
import type { SiteImage } from "@/lib/homepage";
import { Parallax } from "../motion/Parallax";
import { Reveal } from "../motion/Reveal";
import { TextReveal } from "../motion/TextReveal";
import { ButtonLink } from "../ui/Button";
import { Ornament } from "../ui/Section";

export async function FinalCta({ image, overlap = false }: { image: SiteImage; overlap?: boolean }) {
  const t = await getTranslations("home");
  const tHome = await getTranslations("homePage");

  return (
    <section
      className={`bg-ivory px-3 pb-3 pt-[var(--section-y)] md:px-5 md:pb-5 ${
        overlap ? "rounded-top-lux relative z-10 -mt-10 md:-mt-14" : ""
      }`}
    >
      <div className="relative isolate overflow-hidden rounded-[32px] bg-navy-deep text-white md:rounded-[44px]">
        <Parallax speed={120} className="absolute -inset-y-16 inset-x-0 -z-10">
          <div className="relative h-full w-full">
            <Image src={image.src} alt={image.alt} fill sizes="100vw" className="object-cover" />
          </div>
        </Parallax>
        <div aria-hidden className="absolute inset-0 -z-10 bg-gradient-to-b from-navy-deep/40 via-navy-deep/45 to-navy-deep/80" />
        <div className="container-lux flex min-h-[78svh] flex-col items-center justify-center py-28 text-center">
          <Reveal>
            <p className="eyebrow text-white/80">{t("ctaEyebrow")}</p>
          </Reveal>
          <TextReveal
            text={t("ctaLead")}
            accent={t("ctaAccent")}
            accentClassName="text-[#e4c891]"
            className="display-xl mt-6 font-light"
          />
          <Reveal delay={0.2}>
            <Ornament tone="light" className="mt-9" />
          </Reveal>
          <Reveal delay={0.3}>
            <p className="lede mx-auto mt-8 max-w-lg text-white/85">{tHome("ctaBody")}</p>
          </Reveal>
          <Reveal delay={0.4} className="mt-10 flex flex-wrap items-center justify-center gap-3">
            <ButtonLink href="/booking" size="lg" arrow>
              {tHome("ctaBook")}
            </ButtonLink>
            <ButtonLink href={contact.whatsapp} size="lg" variant="ghost-light">
              {t("ctaWhatsapp")}
            </ButtonLink>
          </Reveal>
        </div>
      </div>
    </section>
  );
}
