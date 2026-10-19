import Image from "next/image";
import { getTranslations } from "next-intl/server";
import { ButtonLink } from "@/components/ui/Button";
import { Ornament } from "@/components/ui/Section";
import { stock } from "@/lib/stock";

export default async function NotFound() {
  const t = await getTranslations("notFound");
  const nav = await getTranslations("nav");

  return (
    <section className="relative isolate flex min-h-[100svh] items-center justify-center overflow-hidden bg-navy-deep text-white">
      <div className="ken-burns absolute inset-0 -z-10">
        <Image src={stock.sandRipples.src} alt="" fill preload sizes="100vw" className="object-cover" />
      </div>
      <div aria-hidden className="absolute inset-0 -z-10 bg-gradient-to-b from-navy-deep/70 via-navy-deep/45 to-navy-deep/85" />
      <div aria-hidden className="grain absolute inset-0 -z-10 opacity-60" />
      <div className="container-lux py-32 text-center">
        <p aria-hidden className="font-display text-[clamp(6rem,4rem+10vw,13rem)] font-light leading-none text-white/15">404</p>
        <Ornament tone="light" className="-mt-4" />
        <h1 className="display-lg mt-8 font-light">
          {t("title")} <em className="text-[#e4c891]">{t("accent")}</em>
        </h1>
        <p className="lede mx-auto mt-6 max-w-xl text-white/80">{t("body")}</p>
        <div className="mt-10 flex flex-wrap justify-center gap-3">
          <ButtonLink href="/" arrow>
            {t("home")}
          </ButtonLink>
          <ButtonLink href="/rooms" variant="ghost-light">
            {nav("rooms")}
          </ButtonLink>
        </div>
      </div>
    </section>
  );
}
