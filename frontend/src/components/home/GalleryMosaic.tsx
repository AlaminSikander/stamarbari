import Image from "next/image";
import { getTranslations } from "next-intl/server";
import type { SiteImage } from "@/lib/homepage";
import { ImageReveal } from "../motion/ImageReveal";
import { Parallax } from "../motion/Parallax";
import { Reveal } from "../motion/Reveal";
import { ButtonLink } from "../ui/Button";
import { SectionHeading } from "../ui/Section";

const tiles = [
  { span: "col-span-2 row-span-2 md:col-span-2 md:row-span-2", from: "up", drift: 0 },
  { span: "col-span-1 row-span-1", from: "right", drift: 24 },
  { span: "col-span-1 row-span-2", from: "down", drift: -24 },
  { span: "col-span-1 row-span-1", from: "left", drift: 24 },
  { span: "col-span-2 row-span-1 md:col-span-2", from: "up", drift: -32 },
  { span: "col-span-2 row-span-1 md:col-span-2", from: "right", drift: 32 },
] as const;

export async function GalleryMosaic({ photos }: { photos: SiteImage[] }) {
  const t = await getTranslations("home");
  const tHome = await getTranslations("homePage");

  return (
    <section className="relative overflow-hidden bg-ivory py-[var(--section-y)]">
      <div className="container-lux">
        <SectionHeading eyebrow={t("galleryEyebrow")} title={t("galleryLead")} accent={t("galleryAccent")} />
        <div className="mt-14 grid auto-rows-[clamp(140px,18vw,250px)] grid-cols-2 gap-3 md:mt-20 md:grid-cols-4 md:gap-5">
          {photos.slice(0, tiles.length).map((photo, i) => {
            const tile = tiles[i];
            return (
              <Parallax key={`${photo.src}-${i}`} axis="x" speed={tile.drift} className={tile.span}>
                <ImageReveal from={tile.from} delay={(i % 3) * 0.12} radius="24px" className="group h-full bg-sand">
                  <div className="img-zoom absolute inset-0">
                    <Image
                      src={photo.src}
                      alt={photo.alt}
                      fill
                      sizes={i === 0 ? "(min-width: 768px) 50vw, 100vw" : "(min-width: 768px) 25vw, 50vw"}
                      className="object-cover"
                    />
                  </div>
                  <div aria-hidden className="absolute inset-0 bg-navy-deep/0 transition-colors duration-700 group-hover:bg-navy-deep/15" />
                </ImageReveal>
              </Parallax>
            );
          })}
        </div>
        <Reveal className="mt-14 flex justify-center">
          <ButtonLink href="/gallery" variant="ghost-dark" arrow>
            {tHome("galleryCta")}
          </ButtonLink>
        </Reveal>
      </div>
    </section>
  );
}
