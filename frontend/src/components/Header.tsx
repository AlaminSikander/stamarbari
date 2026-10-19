"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useReducedMotion } from "@/lib/use-reduced-motion";
import { Mail, MapPin, Phone } from "lucide-react";
import Image from "next/image";
import { useLocale, useTranslations } from "next-intl";
import { useEffect, useRef, useState, useSyncExternalStore, type KeyboardEvent as ReactKeyboardEvent } from "react";
import { createPortal } from "react-dom";
import { Link, usePathname } from "@/i18n/navigation";
import { contact } from "@/lib/contact";
import { ease } from "@/lib/motion";
import { lockScroll } from "@/lib/scroll-lock";
import { stock } from "@/lib/stock";
import { MagneticButton } from "./motion/MagneticButton";
import { ButtonLink } from "./ui/Button";

const primaryLinks = [
  { href: "/rooms", key: "rooms" },
  { href: "/experience", key: "experience" },
  { href: "/gallery", key: "gallery" },
  { href: "/saint-martin", key: "saintMartin" },
  { href: "/offers", key: "offers" },
  { href: "/about", key: "about" },
  { href: "/contact", key: "contact" },
] as const;

const menuLinks = [
  { href: "/", key: "home" },
  { href: "/rooms", key: "rooms" },
  { href: "/experience", key: "experience" },
  { href: "/gallery", key: "gallery" },
  { href: "/saint-martin", key: "saintMartin" },
  { href: "/offers", key: "offers" },
  { href: "/about", key: "about" },
  { href: "/blog", key: "blog" },
  { href: "/faq", key: "faq" },
  { href: "/contact", key: "contact" },
] as const;

const FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

const subscribeNoop = () => () => {};

function LanguageSwitch({ pathname, tone }: { pathname: string; tone: "light" | "dark" }) {
  const locale = useLocale();
  const t = useTranslations("common");
  const active = tone === "light" ? "bg-white text-navy-deep" : "bg-navy-deep text-ivory";
  return (
    <div
      role="group"
      aria-label={t("language")}
      className="flex items-center rounded-full border border-current/25 p-0.5 text-[0.68rem] font-medium tracking-[0.12em]"
    >
      {(["en", "bn"] as const).map((code) => (
        <Link
          key={code}
          href={pathname}
          locale={code}
          aria-current={locale === code ? "true" : undefined}
          className={`inline-flex min-h-8 min-w-10 items-center justify-center rounded-full px-2.5 transition-colors duration-500 ease-lux ${
            locale === code ? active : "opacity-75 hover:opacity-100"
          }`}
        >
          {code === "en" ? "EN" : "বাং"}
        </Link>
      ))}
    </div>
  );
}

export function Header() {
  const t = useTranslations("nav");
  const tContact = useTranslations("contact");
  const tFooter = useTranslations("footer");
  const pathname = usePathname();
  const reduce = useReducedMotion();
  const mounted = useSyncExternalStore(subscribeNoop, () => true, () => false);

  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [hidden, setHidden] = useState(false);
  const [lastPath, setLastPath] = useState(pathname);

  const headerRef = useRef<HTMLElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const toggleRef = useRef<HTMLButtonElement>(null);

  if (lastPath !== pathname) {
    setLastPath(pathname);
    setOpen(false);
    setHidden(false);
  }

  useEffect(() => {
    let last = window.scrollY;
    let ticking = false;
    const update = () => {
      const y = window.scrollY;
      setScrolled(y > 80);
      if (Math.abs(y - last) > 6) {
        setHidden(y > last && y > 240);
        last = y;
      }
      ticking = false;
    };
    const onScroll = () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(update);
    };
    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    lockScroll(open);
    if (!open) return;
    const timer = window.setTimeout(() => {
      panelRef.current?.querySelector<HTMLElement>(FOCUSABLE)?.focus({ preventScroll: true });
    }, 450);
    const toggle = toggleRef.current;
    return () => {
      window.clearTimeout(timer);
      lockScroll(false);
      toggle?.focus({ preventScroll: true });
    };
  }, [open]);

  function onKeyDown(event: ReactKeyboardEvent) {
    if (!open) return;
    if (event.key === "Escape") {
      setOpen(false);
      return;
    }
    if (event.key !== "Tab") return;
    const nodes = [headerRef.current, panelRef.current]
      .flatMap((root) => (root ? Array.from(root.querySelectorAll<HTMLElement>(FOCUSABLE)) : []))
      .filter((node) => node.getClientRects().length > 0 && !node.closest("[inert]"));
    if (!nodes.length) return;
    const first = nodes[0];
    const lastNode = nodes[nodes.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      lastNode.focus();
    } else if (!event.shiftKey && document.activeElement === lastNode) {
      event.preventDefault();
      first.focus();
    }
  }

  function isActive(href: string) {
    if (href === "/") return pathname === "/";
    return pathname === href || pathname.startsWith(`${href}/`);
  }

  const solid = scrolled && !open;
  const tone = solid ? "dark" : "light";

  const menu = (
    <AnimatePresence>
      {open ? (
        <motion.div
          key="menu"
          id="site-menu"
          ref={panelRef}
          role="dialog"
          aria-modal="true"
          aria-label={t("menu")}
          onKeyDown={onKeyDown}
          className="fixed inset-0 z-40 overflow-hidden bg-navy-deep text-white"
          initial={{ clipPath: "inset(0 0 100% 0)" }}
          animate={{ clipPath: "inset(0 0 0% 0)" }}
          exit={{ clipPath: "inset(0 0 100% 0)" }}
          transition={{ duration: reduce ? 0 : 0.9, ease: ease.inOut }}
        >
          <motion.div
            aria-hidden
            className="absolute inset-0"
            initial={{ scale: reduce ? 1 : 1.12 }}
            animate={{ scale: 1 }}
            transition={{ duration: 1.8, ease: ease.out }}
          >
            <Image src={stock.coconutPalms.src} alt="" fill sizes="100vw" className="object-cover opacity-25" />
            <div className="absolute inset-0 bg-gradient-to-br from-navy-deep via-navy-deep/90 to-navy-deep/60" />
          </motion.div>
          <div aria-hidden className="grain absolute inset-0" />

          <div data-lenis-prevent className="relative h-full overflow-y-auto overscroll-contain">
            <div className="container-lux grid min-h-full content-end gap-12 pb-10 pt-28 md:pb-14 md:pt-36 lg:grid-cols-[1.5fr_1fr] lg:items-end lg:gap-20">
              <nav aria-label={t("primary")}>
                <ol className="grid gap-x-14 sm:grid-cols-2">
                  {menuLinks.map((link, i) => (
                    <motion.li
                      key={link.href}
                      initial={{ opacity: 0, y: reduce ? 0 : 32 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: reduce ? 0 : 0.35 + i * 0.05, duration: 0.8, ease: ease.out }}
                    >
                      <Link
                        href={link.href}
                        onClick={() => setOpen(false)}
                        aria-current={isActive(link.href) ? "page" : undefined}
                        className="group flex items-baseline gap-5 border-b border-white/10 py-3.5 md:py-4"
                      >
                        <span className="w-6 font-sans text-[0.65rem] tracking-[0.2em] text-gold">
                          {String(i + 1).padStart(2, "0")}
                        </span>
                        <span className="flex-1">
                          <span className="block font-display text-[1.9rem] font-light leading-none transition-[color,transform] duration-500 ease-lux group-hover:translate-x-2 group-hover:text-gold group-aria-[current=page]:italic group-aria-[current=page]:text-gold md:text-[2.4rem]">
                            {t(link.key)}
                          </span>
                          <span className="mt-1.5 block text-[0.64rem] uppercase tracking-[0.22em] text-white/45">
                            {t(`subtitles.${link.key}`)}
                          </span>
                        </span>
                      </Link>
                    </motion.li>
                  ))}
                </ol>
              </nav>

              <motion.aside
                initial={{ opacity: 0, y: reduce ? 0 : 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: reduce ? 0 : 0.7, duration: 0.9, ease: ease.out }}
                className="space-y-8 lg:border-l lg:border-white/10 lg:pl-12"
              >
                <div>
                  <p className="eyebrow text-gold">{tFooter("contact")}</p>
                  <ul className="mt-5 space-y-4 text-sm leading-relaxed text-white/80">
                    <li className="flex gap-3">
                      <MapPin aria-hidden className="mt-0.5 h-4 w-4 shrink-0 text-gold" strokeWidth={1.5} />
                      <span className="whitespace-pre-line">{tContact("address")}</span>
                    </li>
                    <li>
                      <a href={contact.tel} className="link-underline inline-flex items-center gap-3">
                        <Phone aria-hidden className="h-4 w-4 text-gold" strokeWidth={1.5} />
                        {contact.phoneDisplay}
                      </a>
                    </li>
                    <li>
                      <a href={contact.mailto} className="link-underline inline-flex items-center gap-3">
                        <Mail aria-hidden className="h-4 w-4 text-gold" strokeWidth={1.5} />
                        {contact.email}
                      </a>
                    </li>
                  </ul>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                  <ButtonLink href="/booking" onClick={() => setOpen(false)} arrow>
                    {t("bookNow")}
                  </ButtonLink>
                  <ButtonLink href={contact.whatsapp} variant="ghost-light">
                    WhatsApp
                  </ButtonLink>
                </div>
                <div className="md:hidden">
                  <LanguageSwitch pathname={pathname} tone="light" />
                </div>
              </motion.aside>
            </div>
          </div>
        </motion.div>
      ) : null}
    </AnimatePresence>
  );

  return (
    <>
      <header
        ref={headerRef}
        onKeyDown={onKeyDown}
        style={{ viewTransitionName: "site-header" }}
        className={`fixed inset-x-0 top-0 z-50 transition-transform duration-700 ease-lux ${
          hidden && !open ? "-translate-y-full" : ""
        }`}
      >
        <a
          href="#main"
          className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-10 focus:rounded-full focus:bg-ivory focus:px-5 focus:py-3 focus:text-navy-deep"
        >
          {t("skip")}
        </a>
        <div className="header-enter">
          <div
            className={`relative transition-[background-color,padding,box-shadow,color] duration-500 ease-lux ${
              solid
                ? "bg-ivory/85 py-3 text-navy-deep shadow-[0_1px_0_var(--line)] backdrop-blur-xl"
                : "bg-transparent py-4 text-white md:py-6"
            }`}
          >
            <div
              aria-hidden
              className={`pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-navy-deep/55 to-transparent transition-opacity duration-500 ${
                solid || open ? "opacity-0" : "opacity-100"
              }`}
            />
            <div className="container-lux relative grid grid-cols-[1fr_auto] items-center gap-6 xl:grid-cols-[1fr_auto_1fr] xl:gap-4">
              <Link href="/" onClick={() => setOpen(false)} className="group flex items-center gap-3 justify-self-start">
                <span className="relative block h-11 w-11 overflow-hidden rounded-full bg-white ring-1 ring-white/40 transition-transform duration-700 ease-lux group-hover:rotate-[-6deg]">
                  <Image src="/brand/logo.jpg" alt="" width={44} height={48} className="h-full w-full object-contain p-0.5" preload />
                </span>
                <span lang="en" className="leading-none">
                  <span className="block font-display text-[1.35rem] font-normal tracking-wide">S T Amar Bari</span>
                  <span className="mt-1 block text-[0.56rem] font-medium uppercase tracking-[0.34em] opacity-70">
                    Resort · Saint Martin
                  </span>
                </span>
              </Link>

              <nav
                aria-label={t("primary")}
                inert={open}
                className={`hidden items-center gap-5 transition-opacity duration-500 xl:flex 2xl:gap-8 ${open ? "opacity-0" : ""}`}
              >
                {primaryLinks.map((link) => (
                  <Link
                    key={link.href}
                    href={link.href}
                    aria-current={isActive(link.href) ? "page" : undefined}
                    className="link-underline py-2 text-[0.72rem] font-medium uppercase tracking-[0.18em] opacity-90 transition-opacity hover:opacity-100 aria-[current=page]:opacity-100"
                  >
                    {t(link.key)}
                  </Link>
                ))}
              </nav>

              <div className="flex items-center gap-3 justify-self-end md:gap-5">
                <div className={`hidden md:block ${open ? "md:hidden" : ""}`}>
                  <LanguageSwitch pathname={pathname} tone={tone} />
                </div>
                <div inert={open} className={`hidden transition-opacity duration-500 sm:block ${open ? "opacity-0" : ""}`}>
                  <MagneticButton>
                    <ButtonLink href="/booking" className="!min-h-11 !px-6">
                      {t("bookNow")}
                    </ButtonLink>
                  </MagneticButton>
                </div>
                <button
                  ref={toggleRef}
                  type="button"
                  aria-expanded={open}
                  aria-controls="site-menu"
                  aria-label={open ? t("closeMenu") : t("openMenu")}
                  onClick={() => setOpen((v) => !v)}
                  className="group inline-flex min-h-11 min-w-11 items-center justify-center gap-3 rounded-full"
                >
                  <span className={`hidden text-[0.68rem] font-medium uppercase tracking-[0.22em] md:inline ${open ? "" : "xl:hidden"}`}>
                    {open ? t("close") : t("menu")}
                  </span>
                  <span aria-hidden className="relative block h-3 w-7">
                    <span className={`burger-line w-7 ${open ? "top-1/2 rotate-45" : "top-0"}`} />
                    <span
                      className={`burger-line ${open ? "top-1/2 w-7 -rotate-45" : "top-full w-5 group-hover:w-7"}`}
                    />
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>
      {mounted ? createPortal(menu, document.body) : null}
    </>
  );
}
