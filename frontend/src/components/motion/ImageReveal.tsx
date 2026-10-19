"use client";

import { motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import type { ReactNode } from "react";
import { ease, viewportOnce } from "@/lib/motion";

const clips = {
  up: "100% 0% 0% 0%",
  down: "0% 0% 100% 0%",
  left: "0% 0% 0% 100%",
  right: "0% 100% 0% 0%",
};

/**
 * Clip-path curtain reveal with a slow settle of the inner image.
 * `radius` must match the element's border radius: Chrome does not clip
 * transformed children to rounded corners while clip-path is animating.
 */
export function ImageReveal({
  children,
  className = "",
  from = "up",
  delay = 0,
  radius = "0px",
}: {
  children: ReactNode;
  className?: string;
  from?: keyof typeof clips;
  delay?: number;
  radius?: string;
}) {
  const reduce = useReducedMotion();
  return (
    <motion.div
      className={`relative overflow-hidden ${className}`}
      style={{ borderRadius: radius }}
      initial={reduce ? false : { clipPath: `inset(${clips[from]} round ${radius})` }}
      whileInView={{ clipPath: `inset(0% 0% 0% 0% round ${radius})` }}
      viewport={viewportOnce}
      transition={reduce ? { duration: 0 } : { duration: 1.3, ease: ease.inOut, delay }}
    >
      <motion.div
        className="absolute inset-0"
        initial={reduce ? false : { scale: 1.18 }}
        whileInView={{ scale: 1 }}
        viewport={viewportOnce}
        transition={reduce ? { duration: 0 } : { duration: 1.8, ease: ease.out, delay }}
      >
        {children}
      </motion.div>
    </motion.div>
  );
}
