"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { useTranslations } from "next-intl";
import { useState } from "react";
import { ease } from "@/lib/motion";
import type { RoomSummary } from "@/lib/types";
import { RoomCard } from "./RoomCard";

export function RoomsGrid({ rooms }: { rooms: RoomSummary[] }) {
  const t = useTranslations("roomsPage");
  const reduce = useReducedMotion();
  const types = new Map<string, string>();
  for (const room of rooms) {
    if (room.room_type && room.room_type_label) types.set(room.room_type, room.room_type_label);
  }
  const [filter, setFilter] = useState<string>("all");
  const visible = filter === "all" ? rooms : rooms.filter((room) => room.room_type === filter);
  const options = [{ id: "all", label: t("all") }, ...Array.from(types, ([id, label]) => ({ id, label }))];

  return (
    <div>
      <div className="flex flex-col items-center gap-6">
        {types.size > 1 ? (
          <div role="group" aria-label={t("filterLabel")} className="flex flex-wrap justify-center gap-1 rounded-full border border-line bg-white/70 p-1.5">
            {options.map((option) => {
              const active = filter === option.id;
              return (
                <button
                  key={option.id}
                  type="button"
                  aria-pressed={active}
                  onClick={() => setFilter(option.id)}
                  className={`relative min-h-10 rounded-full px-5 text-[0.7rem] font-medium uppercase tracking-[0.16em] transition-colors duration-500 ${
                    active ? "text-white" : "text-navy hover:text-navy-deep"
                  }`}
                >
                  {active ? (
                    <motion.span layoutId="rooms-filter" className="absolute inset-0 rounded-full bg-navy-deep" transition={{ duration: reduce ? 0 : 0.5, ease: ease.out }} />
                  ) : null}
                  <span className="relative">{option.label}</span>
                </button>
              );
            })}
          </div>
        ) : null}
        <p className="text-[0.66rem] uppercase tracking-[0.26em] text-muted">{t("count", { count: visible.length })}</p>
      </div>

      <motion.ul layout className="mt-14 grid gap-x-8 gap-y-16 md:grid-cols-2 lg:grid-cols-3">
        <AnimatePresence mode="popLayout" initial={false}>
          {visible.map((room, i) => (
            <motion.li
              layout
              key={room.id}
              initial={reduce ? false : { opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.97 }}
              viewport={{ once: true, margin: "0px 0px -10% 0px" }}
              transition={{ duration: reduce ? 0 : 0.9, ease: ease.out, delay: reduce ? 0 : (i % 3) * 0.1 }}
            >
              <RoomCard room={room} index={i} />
            </motion.li>
          ))}
        </AnimatePresence>
      </motion.ul>
    </div>
  );
}
