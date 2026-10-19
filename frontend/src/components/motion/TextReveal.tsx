"use client";

import { motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { duration, ease, viewportOnce } from "@/lib/motion";

type Tag = "h1" | "h2" | "h3" | "p";

function words(text: string) {
  return text.split(/\s+/).filter(Boolean);
}

/**
 * Masked word-by-word reveal. `accent` continues the heading in gold italic,
 * on its own line when `breakAccent` is set.
 */
export function TextReveal({
  text,
  accent,
  as = "h2",
  className = "",
  delay = 0,
  trigger = "view",
  play = true,
  breakAccent = true,
  accentClassName = "text-gold",
}: {
  text: string;
  accent?: string;
  as?: Tag;
  className?: string;
  delay?: number;
  trigger?: "view" | "mount";
  /** For `trigger="mount"`: hold the animation until true. */
  play?: boolean;
  breakAccent?: boolean;
  accentClassName?: string;
}) {
  const reduce = useReducedMotion();
  const Comp = motion[as];
  const lead = words(text);
  const tail = accent ? words(accent) : [];
  const label = accent ? `${text} ${accent}` : text;

  const animateProps =
    trigger === "mount"
      ? { animate: play ? "show" : "hidden" }
      : { whileInView: "show", viewport: viewportOnce };

  const last = lead.length + tail.length - 1;
  const lineEnd = (index: number) => index === last || (breakAccent && tail.length > 0 && index === lead.length - 1);

  const word = (value: string, index: number, italic: boolean) => (
    <span key={`${value}-${index}`} className="inline-block overflow-hidden pb-[0.12em] align-bottom -mb-[0.12em]">
      <motion.span
        className={`inline-block ${italic ? `italic ${accentClassName}` : ""}`}
        variants={{
          hidden: { y: "110%", opacity: 0 },
          show: {
            y: "0%",
            opacity: 1,
            transition: { duration: duration.cinematic, ease: ease.out, delay: delay + index * 0.07 },
          },
        }}
      >
        {value}
      </motion.span>
      {lineEnd(index) ? null : "\u00a0"}
    </span>
  );

  return (
    <Comp className={className} aria-label={label} initial={reduce ? false : "hidden"} {...animateProps}>
      <span aria-hidden>
        {lead.map((value, index) => word(value, index, false))}
        {tail.length ? (
          <>
            {breakAccent ? <br /> : null}
            {tail.map((value, index) => word(value, lead.length + index, true))}
          </>
        ) : null}
      </span>
    </Comp>
  );
}
