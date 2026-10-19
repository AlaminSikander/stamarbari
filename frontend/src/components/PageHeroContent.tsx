"use client";

import { motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { ChevronRight } from "lucide-react";
import { useTranslations } from "next-intl";
import { useSyncExternalStore } from "react";
import { Link } from "@/i18n/navigation";
import { introDelay } from "@/lib/intro";
import { ease } from "@/lib/motion";
import { TextReveal } from "./motion/TextReveal";
import { ButtonLink } from "./ui/Button";

export type Crumb = { label: string; href?: string };
export type HeroAction = { label: string; href: string; variant?: "gold" | "ghost-light" | "whatsapp" };

const subscribeNoop = () => () => {};

export function PageHeroContent({
  trail,
  eyebrow,
  title,
  accent,
  subtitle,
  actions,
  compact,
}: {
  trail: Crumb[];
  eyebrow?: string;
  title: string;
  accent?: string;
  subtitle?: string;
  actions?: HeroAction[];
  compact?: boolean;
}) {
  const t = useTranslations("nav");
  const tHero = useTranslations("hero");
  const reduce = useReducedMotion();
  const start = useSyncExternalStore(subscribeNoop, introDelay, () => null);
  const play = start !== null;
  const at = (offset: number) => (reduce ? 0 : (start ?? 0) + offset);
  const fade = (offset: number) => ({
    initial: reduce ? false : { opacity: 0, y: 18 },
    animate: play ? { opacity: 1, y: 0 } : undefined,
    transition: { duration: 0.9, ease: ease.out, delay: at(offset) },
  });

  return (
    <>
      <div
        className={`container-lux relative flex h-full flex-col items-center justify-center text-center ${
          compact ? "pb-14 pt-24 md:pb-16 md:pt-28" : "pb-28 pt-24 md:pb-32 md:pt-28"
        }`}
      >
        <motion.nav aria-label={t("breadcrumb")} {...fade(0.1)}>
          <ol className="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-[0.64rem] font-medium uppercase tracking-[0.22em] text-white/70">
            {trail.map((crumb, i) => (
              <li key={`${crumb.label}-${i}`} className="flex items-center gap-2">
                {i > 0 ? <ChevronRight aria-hidden className="h-3 w-3 text-gold" strokeWidth={1.5} /> : null}
                {crumb.href ? (
                  <Link href={crumb.href} className="link-underline transition-colors hover:text-white">
                    {crumb.label}
                  </Link>
                ) : (
                  <span aria-current="page" className="text-white">
                    {crumb.label}
                  </span>
                )}
              </li>
            ))}
          </ol>
        </motion.nav>

        <motion.span
          aria-hidden
          className="mt-5 block h-px w-12 bg-gold"
          initial={reduce ? false : { scaleX: 0 }}
          animate={play ? { scaleX: 1 } : undefined}
          transition={{ duration: 1, ease: ease.inOut, delay: at(0.2) }}
        />
        {eyebrow ? (
          <motion.p className="eyebrow mt-5 text-[#e4c891]" {...fade(0.3)}>
            {eyebrow}
          </motion.p>
        ) : null}

        <TextReveal
          as="h1"
          trigger="mount"
          play={play}
          delay={at(0.3)}
          text={title}
          accent={accent}
          breakAccent={false}
          accentClassName="text-[#e4c891]"
          className={`${compact ? "display-md" : "display-lg"} ${eyebrow ? "mt-3" : "mt-6"} max-w-4xl font-light text-white`}
        />
        {subtitle ? (
          <motion.p className="lede mx-auto mt-5 max-w-2xl text-white/80" {...fade(0.7)}>
            {subtitle}
          </motion.p>
        ) : null}
        {actions?.length ? (
          <motion.div className="mt-8 flex flex-wrap justify-center gap-3" {...fade(0.85)}>
            {actions.map((action) => (
              <ButtonLink key={action.href} href={action.href} variant={action.variant ?? "gold"} arrow={action.variant !== "ghost-light"}>
                {action.label}
              </ButtonLink>
            ))}
          </motion.div>
        ) : null}
      </div>

      {!compact ? (
        <motion.div
          aria-hidden
          className="absolute bottom-16 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-3 md:bottom-20 md:flex"
          initial={reduce ? false : { opacity: 0 }}
          animate={play ? { opacity: 1 } : undefined}
          transition={{ duration: 1, delay: at(1.1) }}
        >
          <span className="text-[0.58rem] uppercase tracking-[0.34em] text-white/70">{tHero("scroll")}</span>
          <span className="relative block h-10 w-px overflow-hidden bg-white/20">
            <span className="scroll-line absolute inset-0 bg-gold" />
          </span>
        </motion.div>
      ) : null}
    </>
  );
}
