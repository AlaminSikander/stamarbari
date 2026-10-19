"use client";

import { Users } from "lucide-react";
import Image from "next/image";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { useEffect, useState, type ReactNode } from "react";
import { Link } from "@/i18n/navigation";
import { RoomBookingLayout } from "@/components/RoomBookingLayout";
import { apiGet, formatBdt, ApiRequestError } from "@/lib/api";
import { roomImage } from "@/lib/rooms";
import type { BookingPolicies, RoomSummary } from "@/lib/types";

export function BookingPageClient() {
  const t = useTranslations("bookingPage");
  const tRooms = useTranslations("rooms");
  const tPage = useTranslations("roomsPage");
  const params = useSearchParams();
  const roomId = params.get("room_category_id");
  const slug = params.get("room");
  const [rooms, setRooms] = useState<RoomSummary[]>([]);
  const [selected, setSelected] = useState<RoomSummary | null>(null);
  const [policies, setPolicies] = useState<BookingPolicies | null>(null);
  const [houseRules, setHouseRules] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    apiGet<RoomSummary[]>("/rooms")
      .then((list) => {
        setRooms(list);
        setLoaded(true);
        const match = list.find((item) => String(item.id) === roomId || item.slug === slug) ?? null;
        setSelected(match);
      })
      .catch((err: unknown) => {
        setError(err instanceof ApiRequestError ? err.message : t("loadError"));
      });
    apiGet<BookingPolicies>("/bookings/policies").then(setPolicies).catch(() => undefined);
    apiGet<{ body: string }>("/pages/house-rules")
      .then((page) => setHouseRules(page.body))
      .catch(() => undefined);
  }, [roomId, slug, t]);

  if (error) {
    return (
      <Sheet className="py-24">
        <p role="alert" className="mx-auto max-w-xl rounded-[28px] border border-muted-red/25 bg-white p-10 text-center text-muted-red">
          {error}
        </p>
      </Sheet>
    );
  }

  if (selected && policies) {
    return <RoomBookingLayout room={selected} policies={policies} houseRules={houseRules} showTitle />;
  }

  return (
    <Sheet className="pb-28 pt-16 md:pt-20">
      <p className="eyebrow text-gold">{t("choose")}</p>
      {loaded && rooms.length === 0 ? (
        <p className="mt-10 max-w-xl rounded-[28px] border border-line bg-white p-10 text-navy/70">{tPage("empty")}</p>
      ) : rooms.length === 0 ? (
        <div className="mt-10 grid gap-8 md:grid-cols-3">
          {[0, 1, 2].map((i) => (
            <div key={i} className="skeleton aspect-[4/5] rounded-[28px]" />
          ))}
        </div>
      ) : (
        <div className="mt-10 grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
          {rooms.map((room) => {
            const image = roomImage(room);
            return (
              <article key={room.id} className="group relative">
                <div className="relative aspect-[4/5] overflow-hidden rounded-[28px] bg-sand">
                  <div className="absolute inset-0 transition-transform duration-[1.4s] ease-lux group-hover:scale-[1.06]">
                    <Image src={image.src} alt={image.alt} fill sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 92vw" className="object-cover" />
                  </div>
                  <div aria-hidden className="absolute inset-0 bg-gradient-to-t from-navy-deep/75 via-transparent to-transparent" />
                  <div className="absolute inset-x-0 bottom-0 p-6 text-white">
                    {room.view ? <p className="eyebrow text-white/70">{room.view}</p> : null}
                    <h2 className="mt-2 font-display text-[2rem] font-light leading-tight">{room.name}</h2>
                    <p className="mt-3 inline-flex items-center gap-2 text-[0.72rem] uppercase tracking-[0.16em] text-white/80">
                      <Users aria-hidden className="h-4 w-4 text-[#e4c891]" strokeWidth={1.5} />
                      {t("upTo", { count: room.max_adults })}
                    </p>
                  </div>
                </div>
                <div className="flex items-center justify-between gap-4 px-1 pt-5">
                  <p className="font-display text-xl text-navy-deep">
                    {room.starting_price ? tRooms("fromShort", { price: formatBdt(room.starting_price) }) : tRooms("noRate")}
                  </p>
                  <Link
                    href={`/booking?room_category_id=${room.id}`}
                    className="after:absolute after:inset-0 after:rounded-[28px] inline-flex min-h-11 items-center rounded-full bg-navy-deep px-5 text-[0.7rem] font-medium uppercase tracking-[0.16em] text-white transition-colors duration-500 group-hover:bg-gold group-hover:text-navy-deep"
                  >
                    {t("bookRoom")}
                  </Link>
                </div>
              </article>
            );
          })}
        </div>
      )}
    </Sheet>
  );
}

/** Ivory sheet with rounded top corners that slides over the page hero. */
export function Sheet({ children, className = "" }: { children: ReactNode; className?: string }) {
  return (
    <div className="rounded-top-lux relative z-10 -mt-10 bg-ivory md:-mt-14">
      <div className={`container-lux ${className}`}>{children}</div>
    </div>
  );
}
