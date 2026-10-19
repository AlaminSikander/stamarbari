"use client";

import { ArrowUp } from "lucide-react";
import { getLenis } from "@/lib/scroll-lock";

export function BackToTop({ label }: { label: string }) {
  return (
    <button
      type="button"
      onClick={() => {
        const lenis = getLenis();
        if (lenis) lenis.scrollTo(0, { duration: 1.6 });
        else window.scrollTo({ top: 0, behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth" });
      }}
      className="group inline-flex min-h-11 items-center gap-3 text-[0.66rem] font-medium uppercase tracking-[0.22em] text-white/70 transition-colors hover:text-white"
    >
      {label}
      <span className="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 transition-colors duration-500 group-hover:border-gold group-hover:bg-gold group-hover:text-navy-deep">
        <ArrowUp aria-hidden className="h-4 w-4 transition-transform duration-500 group-hover:-translate-y-0.5" strokeWidth={1.5} />
      </span>
    </button>
  );
}
