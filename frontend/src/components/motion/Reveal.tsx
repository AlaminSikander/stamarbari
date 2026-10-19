"use client";

import { motion, type Variants } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import type { ReactNode } from "react";
import { duration, ease, stagger as staggerStep, viewportOnce } from "@/lib/motion";

type Tag = "div" | "li" | "ul" | "ol" | "span" | "p" | "article" | "section" | "figure" | "aside";

const offsets = {
  up: { y: 28, x: 0 },
  down: { y: -28, x: 0 },
  left: { x: 40, y: 0 },
  right: { x: -40, y: 0 },
  none: { x: 0, y: 0 },
};

export function Reveal({
  children,
  as = "div",
  delay = 0,
  direction = "up",
  scale = false,
  className,
  once = true,
}: {
  children: ReactNode;
  as?: Tag;
  delay?: number;
  direction?: keyof typeof offsets;
  scale?: boolean;
  className?: string;
  once?: boolean;
}) {
  const reduce = useReducedMotion();
  const Comp = motion[as];
  const offset = offsets[direction];
  return (
    <Comp
      className={className}
      initial={reduce ? false : { opacity: 0, ...offset, ...(scale ? { scale: 0.96 } : {}) }}
      whileInView={{ opacity: 1, x: 0, y: 0, scale: 1 }}
      viewport={{ ...viewportOnce, once }}
      transition={{ duration: duration.slow, ease: ease.out, delay }}
    >
      {children}
    </Comp>
  );
}

const container: Variants = {
  hidden: {},
  show: (step: number) => ({ transition: { staggerChildren: step, delayChildren: 0.05 } }),
};

const item: Variants = {
  hidden: { opacity: 0, y: 32 },
  show: { opacity: 1, y: 0, transition: { duration: duration.slow, ease: ease.out } },
};

export function Stagger({
  children,
  as = "div",
  className,
  step = staggerStep,
}: {
  children: ReactNode;
  as?: Tag;
  className?: string;
  step?: number;
}) {
  const reduce = useReducedMotion();
  const Comp = motion[as];
  return (
    <Comp
      className={className}
      variants={container}
      custom={step}
      initial={reduce ? false : "hidden"}
      whileInView="show"
      viewport={viewportOnce}
    >
      {children}
    </Comp>
  );
}

export function StaggerItem({ children, as = "div", className }: { children: ReactNode; as?: Tag; className?: string }) {
  const Comp = motion[as];
  return (
    <Comp className={className} variants={item}>
      {children}
    </Comp>
  );
}
