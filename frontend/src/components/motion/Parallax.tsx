"use client";

import { motion, useScroll, useTransform } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { useEffect, useRef, useState, type ReactNode } from "react";

function useFinePointer() {
  const [fine, setFine] = useState(false);
  useEffect(() => {
    const query = window.matchMedia("(hover: hover) and (pointer: fine)");
    const update = () => setFine(query.matches);
    update();
    query.addEventListener("change", update);
    return () => query.removeEventListener("change", update);
  }, []);
  return fine;
}

/** Scroll-linked vertical drift. Disabled on touch devices and for reduced motion. */
export function Parallax({
  children,
  speed = 60,
  className = "",
  axis = "y",
}: {
  children: ReactNode;
  /** Total travel in px across the element's pass through the viewport. */
  speed?: number;
  className?: string;
  axis?: "x" | "y";
}) {
  const ref = useRef<HTMLDivElement>(null);
  const reduce = useReducedMotion();
  const fine = useFinePointer();
  const { scrollYProgress } = useScroll({ target: ref, offset: ["start end", "end start"] });
  const travel = useTransform(scrollYProgress, [0, 1], [-speed / 2, speed / 2]);
  const active = fine && !reduce;
  return (
    <div ref={ref} className={className}>
      <motion.div className="h-full w-full will-change-transform" style={active ? { [axis]: travel } : undefined}>
        {children}
      </motion.div>
    </div>
  );
}

export { useFinePointer };
