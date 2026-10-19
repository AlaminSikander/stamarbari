import { ExternalLink } from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { Reveal } from "@/components/motion/Reveal";
import { PageHero } from "@/components/PageHero";
import { Container, Section } from "@/components/ui/Section";
import { stock } from "@/lib/stock";

export const metadata: Metadata = {
  title: "Photo credits",
  description: "Credits for the Saint Martin Island photographs used on the S T Amar Bari Resort website.",
};

export default async function CreditsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("credits");
  const images = Object.values(stock);

  return (
    <>
      <PageHero compact route="credits" eyebrow={t("eyebrow")} title={t("title")} subtitle={t("subtitle")} />
      <Section tone="ivory" overlap>
        <Container>
          <Reveal>
            <p className="lede mx-auto max-w-2xl text-center text-navy/70">{t("intro")}</p>
          </Reveal>
          <ul className="mt-16 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
            {images.map((image, i) => (
              <Reveal as="li" key={image.src} delay={(i % 4) * 0.06}>
                <figure>
                  <div className="relative aspect-[4/3] overflow-hidden rounded-[20px] bg-sand">
                    <Image src={image.src} alt={image.alt} fill sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 92vw" className="object-cover" />
                  </div>
                  <figcaption className="mt-4 px-1">
                    <p className="text-[0.9rem] leading-snug text-navy/80">{image.alt}</p>
                    <p className="mt-2 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
                      {t("by", { author: image.author })} · {t("licence", { license: image.license })}
                    </p>
                    <a
                      href={image.source}
                      target="_blank"
                      rel="noreferrer"
                      className="mt-2 inline-flex min-h-9 items-center gap-2 text-[0.66rem] font-medium uppercase tracking-[0.18em] text-teal"
                    >
                      <span className="link-underline">{t("source")}</span>
                      <ExternalLink aria-hidden className="h-3.5 w-3.5" strokeWidth={1.5} />
                    </a>
                  </figcaption>
                </figure>
              </Reveal>
            ))}
          </ul>
        </Container>
      </Section>
    </>
  );
}
