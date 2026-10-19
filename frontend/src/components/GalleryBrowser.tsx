"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { Expand } from "lucide-react";
import dynamic from "next/dynamic";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { useMemo, useState } from "react";
import { mediaUrl } from "@/lib/api";
import { ease } from "@/lib/motion";

const Lightbox = dynamic(() => import("./ui/Lightbox"), { ssr: false });

export type GalleryCategory = {
  id: number;
  name_en: string;
  name_bn?: string | null;
  items: {
    id: number;
    path: string;
    type?: string | null;
    alt_en?: string | null;
    alt_bn?: string | null;
    caption_en?: string | null;
    caption_bn?: string | null;
  }[];
};

/** Repeating aspect ratios give the grid an editorial, masonry-like rhythm without known image sizes. */
const aspects = ["aspect-[4/5]", "aspect-[4/3]", "aspect-square", "aspect-[3/4]", "aspect-[5/4]", "aspect-[4/5]"];

export function GalleryBrowser({
  categories,
  locale,
  allLabel,
  empty,
}: {
  categories: GalleryCategory[];
  locale: string;
  allLabel: string;
  empty: string;
}) {
  const t = useTranslations("galleryPage");
  const tBox = useTranslations("lightbox");
  const reduce = useReducedMotion();
  const [filter, setFilter] = useState<number | "all">("all");
  const [open, setOpen] = useState<number | null>(null);
  const [armed, setArmed] = useState(false);

  const photos = useMemo(() => {
    const source = filter === "all" ? categories.flatMap((category) => category.items) : categories.find((c) => c.id === filter)?.items ?? [];
    return source
      .filter((item) => item.path && (!item.type || item.type === "image"))
      .map((item) => ({
        id: item.id,
        src: mediaUrl(item.path) ?? item.path,
        alt:
          (locale === "bn" ? item.alt_bn || item.caption_bn : null) || item.alt_en || item.caption_en || "",
      }));
  }, [categories, filter, locale]);

  const labels = [
    { id: "all" as const, name: allLabel },
    ...categories.filter((category) => category.items.some((item) => item.path)).map((category) => ({
      id: category.id,
      name: locale === "bn" && category.name_bn ? category.name_bn : category.name_en,
    })),
  ];

  return (
    <div>
      <div className="flex flex-col items-center gap-6">
        {labels.length > 2 ? (
          <div role="group" aria-label={t("filterLabel")} className="flex flex-wrap justify-center gap-1 rounded-full border border-line bg-white/70 p-1.5">
            {labels.map((label) => {
              const active = filter === label.id;
              return (
                <button
                  key={String(label.id)}
                  type="button"
                  aria-pressed={active}
                  onClick={() => setFilter(label.id)}
                  className={`relative min-h-10 rounded-full px-5 text-[0.7rem] font-medium uppercase tracking-[0.16em] transition-colors duration-500 ${
                    active ? "text-white" : "text-navy hover:text-navy-deep"
                  }`}
                >
                  {active ? (
                    <motion.span
                      layoutId="gallery-filter"
                      className="absolute inset-0 rounded-full bg-navy-deep"
                      transition={{ duration: reduce ? 0 : 0.5, ease: ease.out }}
                    />
                  ) : null}
                  <span className="relative">{label.name}</span>
                </button>
              );
            })}
          </div>
        ) : null}
        <p className="text-[0.66rem] uppercase tracking-[0.26em] text-muted">{t("count", { count: photos.length })}</p>
      </div>

      {photos.length === 0 ? (
        <p className="mt-12 rounded-[28px] border border-line bg-white p-12 text-center text-muted">{empty}</p>
      ) : (
        <motion.ul layout className="mt-12 columns-2 gap-3 md:columns-3 md:gap-5">
          <AnimatePresence mode="popLayout" initial={false}>
            {photos.map((photo, index) => (
              <motion.li
                layout
                key={photo.id}
                className="mb-3 break-inside-avoid md:mb-5"
                initial={reduce ? false : { opacity: 0, y: 30 }}
                whileInView={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.96 }}
                viewport={{ once: true, margin: "0px 0px -8% 0px" }}
                transition={{ duration: reduce ? 0 : 0.8, ease: ease.out, delay: reduce ? 0 : (index % 3) * 0.08 }}
              >
                <button
                  type="button"
                  onClick={() => {
                    setArmed(true);
                    setOpen(index);
                  }}
                  aria-label={photo.alt || tBox("open", { index: index + 1 })}
                  className={`group img-zoom relative block w-full overflow-hidden rounded-[22px] bg-sand ${aspects[index % aspects.length]}`}
                >
                  <Image src={photo.src} alt={photo.alt} fill sizes="(min-width: 768px) 33vw, 50vw" className="object-cover" />
                  <span aria-hidden className="absolute inset-0 bg-navy-deep/0 transition-colors duration-700 group-hover:bg-navy-deep/30" />
                  <span
                    aria-hidden
                    className="absolute bottom-4 right-4 flex h-11 w-11 scale-75 items-center justify-center rounded-full bg-ivory/90 text-navy-deep opacity-0 transition-all duration-500 ease-lux group-hover:scale-100 group-hover:opacity-100"
                  >
                    <Expand className="h-4 w-4" strokeWidth={1.5} />
                  </span>
                </button>
              </motion.li>
            ))}
          </AnimatePresence>
        </motion.ul>
      )}

      {armed ? <Lightbox photos={photos} index={open} onClose={() => setOpen(null)} onIndex={setOpen} /> : null}
    </div>
  );
}
