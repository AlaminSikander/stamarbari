"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { useTranslations } from "next-intl";
import { useEffect, useState } from "react";
import { ease } from "@/lib/motion";

const INTERVAL = 8000;

export function Testimonials({ items }: { items: { quote: string; name: string }[] }) {
  const t = useTranslations("home");
  const reduce = useReducedMotion();
  const [index, setIndex] = useState(0);
  const [paused, setPaused] = useState(false);
  const count = items.length;

  useEffect(() => {
    if (paused || reduce || count < 2) return;
    const timer = window.setTimeout(() => setIndex((i) => (i + 1) % count), INTERVAL);
    return () => window.clearTimeout(timer);
  }, [index, paused, reduce, count]);

  if (!count) return null;
  const current = items[index];
  const go = (step: number) => setIndex((i) => (i + step + count) % count);

  return (
    <div
      className="mx-auto max-w-4xl text-center"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onFocusCapture={() => setPaused(true)}
      onBlurCapture={() => setPaused(false)}
    >
      <span aria-hidden className="block font-display text-[7rem] leading-[0.5] text-gold/60">“</span>
      <div className="relative mt-6 min-h-[13rem] md:min-h-[11rem]" aria-live="polite">
        <AnimatePresence mode="wait" initial={false}>
          <motion.figure
            key={index}
            initial={reduce ? { opacity: 0 } : { opacity: 0, y: 24, filter: "blur(6px)" }}
            animate={{ opacity: 1, y: 0, filter: "blur(0px)" }}
            exit={reduce ? { opacity: 0 } : { opacity: 0, y: -16, filter: "blur(6px)" }}
            transition={{ duration: 0.8, ease: ease.out }}
          >
            <blockquote className="font-display text-[1.6rem] font-light italic leading-snug text-navy-deep md:text-[2.3rem]">
              {current.quote}
            </blockquote>
            <figcaption className="mt-8 text-[0.7rem] font-medium uppercase tracking-[0.26em] text-muted">— {current.name}</figcaption>
          </motion.figure>
        </AnimatePresence>
      </div>
      {count > 1 ? (
        <div className="mt-12 flex items-center justify-center gap-6">
          <button
            type="button"
            onClick={() => go(-1)}
            aria-label={t("previous")}
            className="flex h-12 w-12 items-center justify-center rounded-full border border-line text-navy transition-colors duration-500 hover:border-navy-deep hover:bg-navy-deep hover:text-white"
          >
            <ArrowLeft aria-hidden className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <div className="flex items-center gap-3">
            <span className="font-sans text-[0.68rem] tracking-[0.2em] text-navy">{String(index + 1).padStart(2, "0")}</span>
            <span className="relative block h-px w-28 overflow-hidden bg-line">
              <span
                key={`${index}-${paused}`}
                className="absolute inset-0 origin-left bg-gold"
                style={
                  reduce || paused
                    ? { transform: "scaleX(1)", opacity: 0.4 }
                    : { animation: `progress-fill ${INTERVAL}ms linear forwards` }
                }
              />
            </span>
            <span className="font-sans text-[0.68rem] tracking-[0.2em] text-muted">{String(count).padStart(2, "0")}</span>
          </div>
          <button
            type="button"
            onClick={() => go(1)}
            aria-label={t("next")}
            className="flex h-12 w-12 items-center justify-center rounded-full border border-line text-navy transition-colors duration-500 hover:border-navy-deep hover:bg-navy-deep hover:text-white"
          >
            <ArrowRight aria-hidden className="h-4 w-4" strokeWidth={1.5} />
          </button>
        </div>
      ) : null}
    </div>
  );
}
