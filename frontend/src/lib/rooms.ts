import { mediaUrl } from "./api";
import type { SiteImage } from "./homepage";
import { stock } from "./stock";
import type { RoomSummary } from "./types";

const fallbacks = [stock.coconutPalms, stock.pandanus, stock.keya, stock.coconutGarden];

/** Cover image for a room. The stock fallback is keyed on the id so cards and detail heroes always match. */
export function roomImage(room: Pick<RoomSummary, "id" | "name" | "featured_image" | "images">): SiteImage {
  const src = mediaUrl(room.featured_image) ?? mediaUrl(room.images[0]?.path);
  if (src) return { src, alt: room.name };
  const fallback = fallbacks[Math.abs(room.id) % fallbacks.length];
  return { src: fallback.src, alt: fallback.alt };
}

export function roomTransitionName(slug: string) {
  return `room-${slug.replace(/[^a-z0-9-]/gi, "")}`;
}
