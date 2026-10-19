/** Seconds to wait before the hero sequence starts, so it begins as the first-visit preloader lifts. */
export function introDelay(): number {
  if (typeof document === "undefined") return 0;
  return document.documentElement.classList.contains("intro-play") ? 1.35 : 0.15;
}
