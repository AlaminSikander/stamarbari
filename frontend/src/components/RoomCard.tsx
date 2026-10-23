import { ArrowUpRight, Bath, Users } from "lucide-react";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { ViewTransition } from "react";
import { Link } from "@/i18n/navigation";
import { formatBdt } from "@/lib/api";
import { roomImage, roomTransitionName } from "@/lib/rooms";
import type { RoomSummary } from "@/lib/types";

function shorten(text: string | null, length = 120) {
  if (!text) return null;
  const clean = text.replace(/\s+/g, " ").trim();
  return clean.length > length ? `${clean.slice(0, length - 1).trimEnd()}…` : clean;
}

export function RoomCard({ room, index = 0, sizes = "(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 92vw" }: { room: RoomSummary; index?: number; sizes?: string }) {
  const t = useTranslations("rooms");
  const image = roomImage(room);
  const hasBath = room.amenities.some((item) => item.slug.includes("bath"));
  const description = shorten(room.description);

  return (
    <article className="group relative flex h-full flex-col">
      <Link href={`/rooms/${room.slug}`} className="absolute inset-0 z-10 rounded-[28px]" aria-label={`${t("view")}: ${room.name}`} />
      <div className="relative aspect-[4/5] overflow-hidden rounded-[28px] bg-sand">
        <ViewTransition name={roomTransitionName(room.slug)} share="morph" default="none">
          <div className="absolute inset-0 transition-transform duration-[1.4s] ease-lux group-hover:scale-[1.06]">
            <Image src={image.src} alt={image.alt} fill sizes={sizes} className="object-cover" />
          </div>
        </ViewTransition>
        <div aria-hidden className="absolute inset-0 bg-gradient-to-t from-navy-deep/75 via-navy-deep/5 to-transparent" />
        <span className="absolute left-5 top-5 font-sans text-[0.66rem] tracking-[0.24em] text-white/80">
          {String(index + 1).padStart(2, "0")}
        </span>
        <span className="absolute right-5 top-5 rounded-full bg-ivory/90 px-4 py-2 text-[0.68rem] font-medium tracking-[0.06em] text-navy-deep backdrop-blur">
          {room.starting_price ? t("fromShort", { price: formatBdt(room.starting_price) }) : t("noRate")}
        </span>
        <div className="absolute inset-x-0 bottom-0 p-6 text-white md:p-7">
          {(room.room_type_label ?? room.view) ? <p className="eyebrow text-white/70">{room.room_type_label ?? room.view}</p> : null}
          <h2 className="mt-2 font-display text-[2rem] font-light leading-[1.05] md:text-[2.3rem]">{room.name}</h2>
          <p className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[0.72rem] uppercase tracking-[0.16em] text-white/80">
            <span className="inline-flex items-center gap-2">
              <Users aria-hidden className="h-4 w-4 text-[#e4c891]" strokeWidth={1.5} />
              {t("capacity", { adults: room.max_adults, children: room.max_children })}
            </span>
            {hasBath ? (
              <span className="inline-flex items-center gap-2">
                <Bath aria-hidden className="h-4 w-4 text-[#e4c891]" strokeWidth={1.5} />
                {t("bathCount")}
              </span>
            ) : null}
          </p>
        </div>
      </div>
      <div className="flex flex-1 flex-col px-1 pt-6">
        {description ? <p className="text-[0.95rem] leading-relaxed text-navy/70">{description}</p> : null}
        {room.amenities.length ? (
          <ul className="mt-4 flex flex-wrap gap-x-4 gap-y-2">
            {room.amenities.map((item) => (
              <li key={item.slug} className="inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.16em] text-muted">
                <span aria-hidden className="h-1 w-1 rotate-45 bg-gold" />
                {item.name}
              </li>
            ))}
          </ul>
        ) : null}
        <span className="mt-auto inline-flex items-center gap-3 pt-6 text-[0.72rem] font-medium uppercase tracking-[0.2em] text-navy">
          <span className="link-underline">{t("view")}</span>
          <ArrowUpRight
            aria-hidden
            className="h-4 w-4 text-gold transition-transform duration-500 ease-lux group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
            strokeWidth={1.5}
          />
        </span>
      </div>
    </article>
  );
}
