"use client";

import { motion, useScroll, useTransform } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { useRef, useState, useSyncExternalStore } from "react";
import { introDelay } from "@/lib/intro";
import type { Homepage } from "@/lib/homepage";
import { ease } from "@/lib/motion";
import { BookingSearch } from "./BookingSearch";
import { TextReveal } from "./motion/TextReveal";
import { ButtonLink } from "./ui/Button";

const subscribeNoop = () => () => {};

function videoAllowed() {
  const connection = (navigator as Navigator & { connection?: { saveData?: boolean } }).connection;
  return (
    !connection?.saveData &&
    window.matchMedia("(min-width: 768px)").matches &&
    !window.matchMedia("(prefers-reduced-motion: reduce)").matches
  );
}

export function Hero({ hero, bookTitle }: { hero: Homepage["hero"]; bookTitle: string }) {
  const t = useTranslations("hero");
  const reduce = useReducedMotion();
  const start = useSyncExternalStore(subscribeNoop, introDelay, () => null);
  const canVideo = useSyncExternalStore(subscribeNoop, videoAllowed, () => false);
  const [videoReady, setVideoReady] = useState(false);
  const play = start !== null;
  const at = (offset: number) => (reduce ? 0 : (start ?? 0) + offset);

  const ref = useRef<HTMLElement>(null);
  const { scrollYProgress } = useScroll({ target: ref, offset: ["start start", "end start"] });
  const mediaY = useTransform(scrollYProgress, [0, 1], ["0%", reduce ? "0%" : "18%"]);
  const contentY = useTransform(scrollYProgress, [0, 1], ["0%", reduce ? "0%" : "-12%"]);
  const contentOpacity = useTransform(scrollYProgress, [0, 0.7], [1, reduce ? 1 : 0]);

  const fadeUp = (offset: number) => ({
    initial: reduce ? false : { opacity: 0, y: 24 },
    animate: play ? { opacity: 1, y: 0 } : undefined,
    transition: { duration: 1, ease: ease.out, delay: at(offset) },
  });

  return (
    <>
      <section ref={ref} className="relative h-[100svh] min-h-[640px] overflow-hidden bg-navy-deep text-white">
        <motion.div
          className="absolute inset-0"
          initial={reduce ? false : { clipPath: "inset(7% 6% 7% 6% round 28px)" }}
          animate={play ? { clipPath: "inset(0% 0% 0% 0% round 0px)" } : undefined}
          transition={{ duration: reduce ? 0 : 1.5, ease: ease.inOut, delay: at(0) }}
        >
          <motion.div className="absolute inset-0 will-change-transform" style={{ y: mediaY }}>
            <div className="ken-burns absolute inset-0">
              <Image
                src={hero.image.src}
                alt={hero.image.alt}
                fill
                preload
                quality={85}
                sizes="100vw"
                className="object-cover"
              />
              {hero.video && canVideo ? (
                <video
                  className={`absolute inset-0 h-full w-full object-cover transition-opacity duration-[1.6s] ${
                    videoReady ? "opacity-100" : "opacity-0"
                  }`}
                  src={hero.video}
                  poster={hero.poster ?? undefined}
                  autoPlay
                  muted
                  loop
                  playsInline
                  preload="metadata"
                  aria-hidden
                  onCanPlay={() => setVideoReady(true)}
                />
              ) : null}
            </div>
          </motion.div>
          <motion.div
            aria-hidden
            className="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,34,54,0.55)_0%,rgba(10,34,54,0.3)_35%,rgba(10,34,54,0.38)_60%,rgba(10,34,54,0.85)_100%),radial-gradient(ellipse_55%_50%_at_50%_50%,rgba(10,34,54,0.35),transparent_75%)]"
            initial={reduce ? false : { opacity: 0 }}
            animate={play ? { opacity: 1 } : undefined}
            transition={{ duration: 1.4, ease: ease.out, delay: at(0.3) }}
          />
          <div aria-hidden className="grain absolute inset-0 opacity-60" />
        </motion.div>

        <motion.div
          style={{ y: contentY, opacity: contentOpacity }}
          className="container-lux relative flex h-full flex-col items-center justify-center pb-36 pt-28 text-center md:pb-40"
        >
          <div className="flex max-w-5xl flex-col items-center">
            <div className="flex items-center gap-4">
              <motion.span
                aria-hidden
                className="block h-px w-10 origin-right bg-gold md:w-12"
                initial={reduce ? false : { scaleX: 0 }}
                animate={play ? { scaleX: 1 } : undefined}
                transition={{ duration: 1, ease: ease.inOut, delay: at(0.55) }}
              />
              <motion.p className="eyebrow text-white/85" {...fadeUp(0.7)}>
                {hero.eyebrow}
              </motion.p>
              <motion.span
                aria-hidden
                className="block h-px w-10 origin-left bg-gold md:w-12"
                initial={reduce ? false : { scaleX: 0 }}
                animate={play ? { scaleX: 1 } : undefined}
                transition={{ duration: 1, ease: ease.inOut, delay: at(0.55) }}
              />
            </div>
            <TextReveal
              as="h1"
              trigger="mount"
              play={play}
              delay={at(0.8)}
              text={hero.titleLead}
              accent={hero.titleAccent}
              accentClassName="text-[#e4c891]"
              className="display-xl mt-6 font-light text-white"
            />
            <motion.p className="lede mx-auto mt-7 max-w-xl text-white/85" {...fadeUp(1.35)}>
              {hero.body}
            </motion.p>
            <motion.div className="mt-9 flex flex-wrap items-center justify-center gap-3" {...fadeUp(1.5)}>
              <ButtonLink href="/booking" size="lg" arrow>
                {t("book")}
              </ButtonLink>
              <ButtonLink href="#welcome" external size="lg" variant="ghost-light">
                {t("explore")}
              </ButtonLink>
            </motion.div>
          </div>
        </motion.div>

        <motion.div
          aria-hidden
          className="absolute bottom-28 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-3 md:bottom-[7.5rem] lg:[@media(min-height:800px)]:flex"
          initial={reduce ? false : { opacity: 0 }}
          animate={play ? { opacity: 1 } : undefined}
          transition={{ duration: 1, delay: at(1.9) }}
        >
          <span className="text-[0.6rem] uppercase tracking-[0.34em] text-white/70">{t("scroll")}</span>
          <span className="relative block h-10 w-px overflow-hidden bg-white/20">
            <span className="scroll-line absolute inset-0 bg-gold" />
          </span>
        </motion.div>
        <motion.p
          aria-hidden
          className="absolute left-[var(--pad-x)] top-1/2 hidden -translate-y-1/2 rotate-180 text-[0.6rem] uppercase tracking-[0.34em] text-white/55 [writing-mode:vertical-rl] xl:block"
          initial={reduce ? false : { opacity: 0 }}
          animate={play ? { opacity: 1 } : undefined}
          transition={{ duration: 1, delay: at(2) }}
        >
          20.62° N · 92.32° E
        </motion.p>
      </section>

      <motion.div
        className="container-lux relative z-20 -mt-20 md:-mt-[4.75rem]"
        initial={reduce ? false : { opacity: 0, y: 40 }}
        animate={play ? { opacity: 1, y: 0 } : undefined}
        transition={{ duration: 1.1, ease: ease.out, delay: at(1.65) }}
      >
        <BookingSearch title={bookTitle} />
      </motion.div>
    </>
  );
}
