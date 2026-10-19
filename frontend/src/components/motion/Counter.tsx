"use client";

import { animate, useInView } from "framer-motion";
import { useEffect, useRef, useState } from "react";
import { useReducedMotion } from "@/lib/use-reduced-motion";

/** Counts up the numeric part of values like "12", "50+" or "4.9". Non-numeric values render as-is. */
export function Counter({ value, className = "" }: { value: string; className?: string }) {
  const ref = useRef<HTMLSpanElement>(null);
  const inView = useInView(ref, { once: true, margin: "0px 0px -10% 0px" });
  const reduce = useReducedMotion();
  const match = value.match(/^(\D*)(\d+(?:\.\d+)?)(.*)$/);
  const target = match ? Number(match[2]) : null;
  const decimals = match?.[2].includes(".") ? match[2].split(".")[1].length : 0;
  const [display, setDisplay] = useState(target === null ? value : `${match![1]}0${match![3]}`);

  useEffect(() => {
    if (!inView || target === null || !match) return;
    const controls = animate(reduce ? target : 0, target, {
      duration: reduce ? 0 : 1.8,
      ease: [0.22, 1, 0.36, 1],
      onUpdate: (latest) => setDisplay(`${match[1]}${latest.toFixed(decimals)}${match[3]}`),
    });
    return () => controls.stop();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [inView, target, reduce]);

  return (
    <span ref={ref} className={className} aria-label={value}>
      <span aria-hidden>{display}</span>
    </span>
  );
}
