"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { ArrowUpRight, Users } from "lucide-react";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { useState } from "react";
import { Link } from "@/i18n/navigation";
import { ease } from "@/lib/motion";
import { Reveal } from "../motion/Reveal";

export type ShowcaseRoom = {
  slug: string;
  name: string;
  image: { src: string; alt: string };
  guests: number;
  price: string | null;
  view: string | null;
};

export function RoomsShowcase({ rooms }: { rooms: ShowcaseRoom[] }) {
  const t = useTranslations("rooms");
  const tHome = useTranslations("home");
  const reduce = useReducedMotion();
  const [active, setActive] = useState(0);
  const current = rooms[active] ?? rooms[0];
  if (!current) return null;

  return (
    <div className="grid gap-10 lg:grid-cols-[1.15fr_1fr] lg:gap-20">
      <Reveal direction="none" className="relative aspect-[4/5] overflow-hidden rounded-[32px] bg-sand md:aspect-[5/4] lg:sticky lg:top-28 lg:aspect-[4/5] lg:self-start">
        <AnimatePresence initial={false} mode="popLayout">
          <motion.div
            key={current.slug}
            className="absolute inset-0"
            initial={reduce ? { opacity: 0 } : { opacity: 0, scale: 1.08, clipPath: "inset(0 0 0 100%)" }}
            animate={{ opacity: 1, scale: 1, clipPath: "inset(0 0 0 0%)" }}
            exit={{ opacity: 0 }}
            transition={{ duration: reduce ? 0.2 : 1.1, ease: ease.inOut }}
          >
            <Image
              src={current.image.src}
              alt={current.image.alt}
              fill
              sizes="(min-width: 1024px) 50vw, 92vw"
              className="object-cover"
            />
          </motion.div>
        </AnimatePresence>
        <div aria-hidden className="absolute inset-0 bg-gradient-to-t from-navy-deep/60 via-transparent to-transparent" />
        <div className="absolute inset-x-0 bottom-0 flex items-end justify-between gap-6 p-6 text-white md:p-8">
          <div>
            <p className="font-sans text-[0.65rem] tracking-[0.24em] text-white/70">
              {String(active + 1).padStart(2, "0")} / {String(rooms.length).padStart(2, "0")}
            </p>
            <p className="mt-2 font-display text-3xl font-light md:text-4xl">{current.name}</p>
          </div>
          {current.price ? (
            <p className="rounded-full bg-white/15 px-4 py-2 text-xs tracking-[0.08em] backdrop-blur-md">{current.price}</p>
          ) : null}
        </div>
      </Reveal>

      <ol className="border-t border-line">
        {rooms.map((room, i) => (
          <Reveal as="li" key={room.slug} delay={i * 0.06} className="border-b border-line">
            <Link
              href={`/rooms/${room.slug}`}
              onMouseEnter={() => setActive(i)}
              onFocus={() => setActive(i)}
              aria-current={i === active ? "true" : undefined}
              className="group grid grid-cols-[auto_1fr_auto] items-center gap-5 py-6 md:gap-8 md:py-8"
            >
              <span className="font-sans text-[0.68rem] tracking-[0.2em] text-gold">{String(i + 1).padStart(2, "0")}</span>
              <span>
                <span
                  className={`block font-display text-[1.75rem] font-light leading-tight transition-[color,transform] duration-500 ease-lux md:text-[2.3rem] ${
                    i === active ? "translate-x-2 text-navy-deep" : "text-navy/55 group-hover:text-navy-deep"
                  }`}
                >
                  {room.name}
                </span>
                <span className="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-[0.7rem] uppercase tracking-[0.16em] text-muted">
                  <span className="inline-flex items-center gap-1.5">
                    <Users aria-hidden className="h-3.5 w-3.5 text-gold" strokeWidth={1.5} />
                    {t("guests", { count: room.guests })}
                  </span>
                  {room.view ? <span>{room.view}</span> : null}
                  <span>{room.price ?? t("noRate")}</span>
                </span>
              </span>
              <span
                aria-hidden
                className={`flex h-12 w-12 items-center justify-center rounded-full border transition-all duration-500 ease-lux ${
                  i === active ? "border-navy-deep bg-navy-deep text-white" : "border-line text-navy group-hover:border-navy-deep"
                }`}
              >
                <ArrowUpRight className="h-4 w-4 transition-transform duration-500 group-hover:rotate-45" strokeWidth={1.5} />
              </span>
              <span className="sr-only">{tHome("roomsView")}</span>
            </Link>
          </Reveal>
        ))}
      </ol>
    </div>
  );
}
