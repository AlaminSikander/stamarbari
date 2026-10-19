import { ArrowUpRight } from "lucide-react";
import Image from "next/image";
import { Link } from "@/i18n/navigation";
import { mediaUrl } from "@/lib/api";
import type { SiteImage } from "@/lib/homepage";
import { stripHtml } from "@/lib/html";
import { stock } from "@/lib/stock";

function excerpt(text: string | null | undefined, length = 110): string | null {
  if (!text) return null;
  const clean = stripHtml(text) || text.replace(/\s+/g, " ").trim();
  if (!clean) return null;
  if (clean.length <= length) return clean;
  return `${clean.slice(0, length - 1).trimEnd()}…`;
}

export function ContentCard({
  href,
  title,
  description,
  image,
  fallback = stock.sandRipples,
  badge,
  meta,
  chips,
  cta,
  excerptLength = 110,
  index,
  aspect = "aspect-[4/3]",
}: {
  href?: string;
  title: string;
  description?: string | null;
  /** CMS media path; wins over `fallback`. */
  image?: string | null;
  fallback?: SiteImage;
  badge?: string | null;
  meta?: string | null;
  chips?: string[];
  cta?: string;
  excerptLength?: number;
  index?: number;
  aspect?: string;
}) {
  const uploaded = mediaUrl(image);
  const picture = uploaded ? { src: uploaded, alt: title } : fallback;
  const plain = stripHtml(description ?? "") || description?.replace(/\s+/g, " ").trim() || null;
  const body = excerptLength <= 0 ? plain : excerpt(description, excerptLength);

  return (
    <article className="group relative flex h-full flex-col">
      {href ? <Link href={href} className="absolute inset-0 z-10 rounded-[24px]" aria-label={cta ? `${cta}: ${title}` : title} /> : null}
      <div className={`relative overflow-hidden rounded-[24px] bg-sand ${aspect}`}>
        <div className="absolute inset-0 transition-transform duration-[1.4s] ease-lux group-hover:scale-[1.06]">
          <Image src={picture.src} alt={picture.alt} fill sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 92vw" className="object-cover" />
        </div>
        <div aria-hidden className="absolute inset-0 bg-gradient-to-t from-navy-deep/40 via-transparent to-transparent opacity-80" />
        {badge ? (
          <span className="absolute left-4 top-4 rounded-full bg-ivory/90 px-3.5 py-1.5 text-[0.62rem] font-medium uppercase tracking-[0.18em] text-navy-deep backdrop-blur">
            {badge}
          </span>
        ) : null}
        {typeof index === "number" ? (
          <span className="absolute bottom-4 left-4 font-sans text-[0.66rem] tracking-[0.24em] text-white/85">{String(index + 1).padStart(2, "0")}</span>
        ) : null}
      </div>
      <div className="flex flex-1 flex-col px-1 pt-6">
        {meta ? <p className="text-[0.66rem] uppercase tracking-[0.2em] text-muted">{meta}</p> : null}
        <h2 className={`font-display text-[1.65rem] font-light leading-tight text-navy-deep transition-colors duration-500 group-hover:text-teal ${meta ? "mt-2" : ""}`}>
          {title}
        </h2>
        {body ? <p className="mt-3 text-[0.95rem] leading-relaxed text-navy/70">{body}</p> : null}
        {chips?.length ? (
          <ul className="mt-4 flex flex-wrap gap-x-4 gap-y-2">
            {chips.map((chip) => (
              <li key={chip} className="inline-flex items-center gap-2 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
                <span aria-hidden className="h-1 w-1 rotate-45 bg-gold" />
                {chip}
              </li>
            ))}
          </ul>
        ) : null}
        {href && cta ? (
          <span className="mt-auto inline-flex items-center gap-3 pt-6 text-[0.7rem] font-medium uppercase tracking-[0.2em] text-navy">
            <span className="link-underline">{cta}</span>
            <ArrowUpRight
              aria-hidden
              className="h-4 w-4 text-gold transition-transform duration-500 ease-lux group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
              strokeWidth={1.5}
            />
          </span>
        ) : null}
      </div>
    </article>
  );
}
