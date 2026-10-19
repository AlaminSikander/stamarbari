"use client";

import { useEffect } from "react";
import { registerLenis } from "@/lib/scroll-lock";

/** Inertial scrolling on desktop pointer devices only. */
export function SmoothScroll() {
  useEffect(() => {
    const fine = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (!fine || reduce) return;

    let destroyed = false;
    let cleanup: (() => void) | undefined;
    import("lenis").then(({ default: Lenis }) => {
      if (destroyed) return;
      const lenis = new Lenis({ duration: 1.15, easing: (t) => 1 - Math.pow(1 - t, 4), autoRaf: true, anchors: true });
      registerLenis(lenis);
      cleanup = () => {
        registerLenis(null);
        lenis.destroy();
      };
    });
    return () => {
      destroyed = true;
      cleanup?.();
    };
  }, []);

  return null;
}
