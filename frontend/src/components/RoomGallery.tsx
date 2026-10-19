"use client";

import { Images } from "lucide-react";
import dynamic from "next/dynamic";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { useState } from "react";
import { mediaUrl } from "@/lib/api";
import { roomImage } from "@/lib/rooms";
import type { RoomSummary } from "@/lib/types";

const Lightbox = dynamic(() => import("./ui/Lightbox"), { ssr: false });

export function RoomGallery({ room }: { room: RoomSummary }) {
  const t = useTranslations("roomDetail");
  const photos = uniquePhotos(room);
  const [open, setOpen] = useState<number | null>(null);
  const [armed, setArmed] = useState(false);
  const show = (index: number) => {
    setArmed(true);
    setOpen(index);
  };

  if (photos.length === 0) {
    const cover = roomImage(room);
    return (
      <div className="relative aspect-[16/10] overflow-hidden rounded-[28px] bg-sand">
        <Image src={cover.src} alt={cover.alt} fill sizes="(min-width: 1024px) 60vw, 92vw" className="object-cover" />
        <div aria-hidden className="absolute inset-0 bg-gradient-to-t from-navy-deep/70 to-transparent" />
        <p className="absolute bottom-6 left-6 right-6 font-display text-xl italic text-white/90">{t("photosSoon")}</p>
      </div>
    );
  }

  const [main, ...rest] = photos;
  const side = rest.slice(0, 2);

  return (
    <>
      <div className={`grid gap-3 md:gap-4 ${side.length ? "md:grid-cols-[2fr_1fr]" : ""}`}>
        <button
          type="button"
          onClick={() => show(0)}
          className="group img-zoom relative aspect-[4/3] overflow-hidden rounded-[28px] bg-sand md:aspect-auto md:min-h-[26rem]"
        >
          <Image src={main.src} alt={main.alt} fill sizes="(min-width: 1024px) 45vw, 92vw" className="object-cover" />
          <span aria-hidden className="absolute inset-0 bg-navy-deep/0 transition-colors duration-700 group-hover:bg-navy-deep/20" />
        </button>
        {side.length ? (
          <div className="grid grid-cols-2 gap-3 md:grid-cols-1 md:gap-4">
            {side.map((photo, i) => (
              <button
                key={photo.src}
                type="button"
                onClick={() => show(i + 1)}
                className="group img-zoom relative aspect-[4/3] overflow-hidden rounded-[22px] bg-sand md:aspect-auto"
              >
                <Image src={photo.src} alt={photo.alt} fill sizes="(min-width: 1024px) 22vw, 46vw" className="object-cover" />
                <span aria-hidden className="absolute inset-0 bg-navy-deep/0 transition-colors duration-700 group-hover:bg-navy-deep/20" />
              </button>
            ))}
          </div>
        ) : null}
      </div>
      <div className="mt-4 flex justify-end">
        <button
          type="button"
          onClick={() => show(0)}
          className="group inline-flex min-h-11 items-center gap-3 text-[0.7rem] font-medium uppercase tracking-[0.2em] text-navy"
        >
          <Images aria-hidden className="h-4 w-4 text-gold" strokeWidth={1.5} />
          <span className="link-underline">{t("viewAll", { count: photos.length })}</span>
        </button>
      </div>
      {armed ? <Lightbox photos={photos} index={open} onClose={() => setOpen(null)} onIndex={setOpen} /> : null}
    </>
  );
}

export function uniquePhotos(room: RoomSummary): { src: string; alt: string }[] {
  const seen = new Set<string>();
  const out: { src: string; alt: string }[] = [];
  const items = [room.featured_image ? { path: room.featured_image, alt: room.name } : null, ...room.images];
  for (const item of items) {
    if (!item?.path) continue;
    const src = mediaUrl(item.path);
    if (!src || seen.has(src)) continue;
    seen.add(src);
    out.push({ src, alt: item.alt ?? room.name });
  }
  return out;
}
