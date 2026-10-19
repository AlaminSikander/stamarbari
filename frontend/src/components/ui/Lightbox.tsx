"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { ArrowLeft, ArrowRight, X } from "lucide-react";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { useEffect, useRef, useSyncExternalStore } from "react";
import { createPortal } from "react-dom";
import { ease } from "@/lib/motion";
import { lockScroll } from "@/lib/scroll-lock";

export type LightboxPhoto = { src: string; alt: string };

const subscribeNoop = () => () => {};

/** Full-screen photo viewer with keyboard, swipe and focus handling. */
export default function Lightbox({
  photos,
  index,
  onClose,
  onIndex,
}: {
  photos: LightboxPhoto[];
  index: number | null;
  onClose: () => void;
  onIndex: (index: number) => void;
}) {
  const t = useTranslations("lightbox");
  const reduce = useReducedMotion();
  const mounted = useSyncExternalStore(subscribeNoop, () => true, () => false);
  const closeRef = useRef<HTMLButtonElement>(null);
  const dialogRef = useRef<HTMLDivElement>(null);
  const touchX = useRef<number | null>(null);
  const open = index !== null && Boolean(photos[index]);
  const count = photos.length;

  useEffect(() => {
    if (!open) return;
    const previous = document.activeElement as HTMLElement | null;
    lockScroll(true);
    closeRef.current?.focus({ preventScroll: true });
    return () => {
      lockScroll(false);
      previous?.focus({ preventScroll: true });
    };
  }, [open]);

  useEffect(() => {
    if (!open || index === null) return;
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
      else if (event.key === "ArrowRight") onIndex((index + 1) % count);
      else if (event.key === "ArrowLeft") onIndex((index - 1 + count) % count);
      else if (event.key === "Tab" && dialogRef.current) {
        const nodes = Array.from(dialogRef.current.querySelectorAll<HTMLElement>("button"));
        const first = nodes[0];
        const last = nodes[nodes.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, index, count, onClose, onIndex]);

  if (!mounted) return null;

  const photo = index !== null ? photos[index] : null;
  const step = (delta: number) => index !== null && onIndex((index + delta + count) % count);

  return createPortal(
    <AnimatePresence>
      {open && photo ? (
        <motion.div
          ref={dialogRef}
          role="dialog"
          aria-modal="true"
          aria-label={t("dialog")}
          className="fixed inset-0 z-[80] flex flex-col bg-navy-deep/95 text-white backdrop-blur-xl"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: reduce ? 0 : 0.45, ease: ease.out }}
          onTouchStart={(event) => {
            touchX.current = event.touches[0]?.clientX ?? null;
          }}
          onTouchEnd={(event) => {
            if (touchX.current === null) return;
            const delta = (event.changedTouches[0]?.clientX ?? touchX.current) - touchX.current;
            if (Math.abs(delta) > 50) step(delta < 0 ? 1 : -1);
            touchX.current = null;
          }}
        >
          <div className="container-lux flex items-center justify-between py-5">
            <p className="font-sans text-[0.7rem] tracking-[0.24em] text-white/70" aria-live="polite">
              {t("counter", { current: String((index ?? 0) + 1).padStart(2, "0"), total: String(count).padStart(2, "0") })}
            </p>
            <button
              ref={closeRef}
              type="button"
              onClick={onClose}
              className="group inline-flex min-h-11 items-center gap-3 rounded-full border border-white/25 px-5 text-[0.68rem] uppercase tracking-[0.22em] transition-colors hover:bg-white hover:text-navy-deep"
            >
              {t("close")}
              <X aria-hidden className="h-4 w-4 transition-transform duration-500 group-hover:rotate-90" strokeWidth={1.5} />
            </button>
          </div>

          <div className="relative flex-1 overflow-hidden" onClick={(event) => event.target === event.currentTarget && onClose()}>
            <AnimatePresence initial={false} mode="popLayout">
              <motion.div
                key={photo.src}
                className="pointer-events-none absolute inset-x-4 inset-y-2 md:inset-x-24 md:inset-y-4"
                initial={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.96 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: reduce ? 0.15 : 0.6, ease: ease.out }}
              >
                <Image src={photo.src} alt={photo.alt} fill sizes="100vw" quality={85} className="object-contain" />
              </motion.div>
            </AnimatePresence>
            {count > 1 ? (
              <>
                <button
                  type="button"
                  onClick={() => step(-1)}
                  aria-label={t("previous")}
                  className="absolute left-3 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-navy-deep/40 backdrop-blur transition-colors hover:bg-white hover:text-navy-deep md:left-8 md:h-14 md:w-14"
                >
                  <ArrowLeft aria-hidden className="h-5 w-5" strokeWidth={1.5} />
                </button>
                <button
                  type="button"
                  onClick={() => step(1)}
                  aria-label={t("next")}
                  className="absolute right-3 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-navy-deep/40 backdrop-blur transition-colors hover:bg-white hover:text-navy-deep md:right-8 md:h-14 md:w-14"
                >
                  <ArrowRight aria-hidden className="h-5 w-5" strokeWidth={1.5} />
                </button>
              </>
            ) : null}
          </div>

          <div className="container-lux min-h-16 py-5 text-center">
            {photo.alt ? <p className="font-display text-lg italic text-white/80">{photo.alt}</p> : null}
          </div>
        </motion.div>
      ) : null}
    </AnimatePresence>,
    document.body,
  );
}
