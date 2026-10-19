"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { Plus } from "lucide-react";
import { useId, useState } from "react";
import { RichHtml } from "@/components/RichHtml";
import { ease } from "@/lib/motion";

export type AccordionItem = { id: string | number; title: string; body: string };

export function Accordion({ items, defaultOpen = 0 }: { items: AccordionItem[]; defaultOpen?: number | null }) {
  const reduce = useReducedMotion();
  const baseId = useId();
  const [open, setOpen] = useState<number | null>(defaultOpen);

  return (
    <ul className="border-t border-line">
      {items.map((item, i) => {
        const expanded = open === i;
        const panelId = `${baseId}-panel-${i}`;
        const buttonId = `${baseId}-button-${i}`;
        return (
          <li key={item.id} className="border-b border-line">
            <h3>
              <button
                id={buttonId}
                type="button"
                aria-expanded={expanded}
                aria-controls={panelId}
                onClick={() => setOpen(expanded ? null : i)}
                className="group flex w-full items-start gap-5 py-7 text-left md:gap-8 md:py-8"
              >
                <span className="pt-2 font-sans text-[0.64rem] tracking-[0.22em] text-gold">{String(i + 1).padStart(2, "0")}</span>
                <span
                  className={`flex-1 font-display text-[1.45rem] font-light leading-snug transition-colors duration-500 md:text-[1.75rem] ${
                    expanded ? "text-navy-deep" : "text-navy/75 group-hover:text-navy-deep"
                  }`}
                >
                  {item.title}
                </span>
                <span
                  aria-hidden
                  className={`mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border transition-[background-color,border-color,color,transform] duration-500 ease-lux ${
                    expanded ? "rotate-45 border-navy-deep bg-navy-deep text-white" : "border-line text-navy group-hover:border-navy-deep"
                  }`}
                >
                  <Plus className="h-4 w-4" strokeWidth={1.5} />
                </span>
              </button>
            </h3>
            <AnimatePresence initial={false}>
              {expanded ? (
                <motion.div
                  id={panelId}
                  role="region"
                  aria-labelledby={buttonId}
                  key="panel"
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: "auto", opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  transition={{ duration: reduce ? 0 : 0.55, ease: ease.out }}
                  className="overflow-hidden"
                >
                  <div className="pb-8 pl-10 pr-14 md:pl-[3.4rem]">
                    <RichHtml html={item.body} className="max-w-2xl text-[1rem] leading-[1.8] text-navy/70" />
                  </div>
                </motion.div>
              ) : null}
            </AnimatePresence>
          </li>
        );
      })}
    </ul>
  );
}
