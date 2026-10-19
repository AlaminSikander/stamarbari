import { ArrowUpRight, Mail, MapPin, Phone } from "lucide-react";
import Image from "next/image";
import { getTranslations } from "next-intl/server";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { Link } from "@/i18n/navigation";
import { contact } from "@/lib/contact";
import { BackToTop } from "./BackToTop";
import { ButtonLink } from "./ui/Button";

function FooterLink({ href, children }: { href: string; children: React.ReactNode }) {
  return (
    <li>
      <Link href={href} className="group inline-flex min-h-9 items-center gap-2 text-[0.95rem] text-white/70 transition-colors duration-300 hover:text-white">
        <span aria-hidden className="h-px w-0 bg-gold transition-all duration-500 ease-lux group-hover:w-4" />
        {children}
      </Link>
    </li>
  );
}

export async function Footer() {
  const t = await getTranslations("footer");
  const nav = await getTranslations("nav");
  const tContact = await getTranslations("contact");
  const tCommon = await getTranslations("common");
  const tBrand = await getTranslations();

  return (
    <footer className="grain relative overflow-hidden bg-navy-deep pb-28 text-white md:pb-0">
      <div aria-hidden className="pointer-events-none absolute -left-40 top-0 h-[460px] w-[460px] rounded-full bg-teal/15 blur-[130px]" />

      <div className="container-lux relative pt-20 md:pt-28">
        <div className="grid gap-14 border-b border-white/10 pb-16 lg:grid-cols-[1.3fr_0.7fr_0.8fr_1fr] lg:gap-12">
          <div className="max-w-sm">
            <Link href="/" className="group inline-flex items-center gap-4">
              <span className="relative block h-14 w-14 overflow-hidden rounded-full bg-white ring-1 ring-white/30 transition-transform duration-700 ease-lux group-hover:rotate-[-6deg]">
                <Image src="/brand/logo.jpg" alt="" width={56} height={61} className="h-full w-full object-contain p-0.5" />
              </span>
              <span className="leading-none">
                <span className="block font-display text-[1.6rem]">{tBrand("brand")}</span>
                <span className="mt-1.5 block text-[0.58rem] font-medium uppercase tracking-[0.34em] text-[#e4c891]">{tBrand("tagline")}</span>
              </span>
            </Link>
            <p className="mt-8 text-[0.95rem] leading-relaxed text-white/65">{t("tagline")}</p>
            <div className="mt-10">
              <p className="font-display text-[1.75rem] font-light italic text-white">{t("bookTitle")}</p>
              <p className="mt-2 text-sm text-white/60">{t("bookBody")}</p>
              <div className="mt-6 flex flex-wrap gap-3">
                <ButtonLink href="/booking" arrow>
                  {nav("bookNow")}
                </ButtonLink>
                <ButtonLink href={contact.whatsapp} variant="ghost-light">
                  {tCommon("whatsapp")}
                </ButtonLink>
              </div>
            </div>
          </div>

          <nav aria-label={t("explore")}>
            <p className="eyebrow text-[#e4c891]">{t("explore")}</p>
            <ul className="mt-6 space-y-1">
              <FooterLink href="/about">{nav("about")}</FooterLink>
              <FooterLink href="/our-story">{nav("ourStory")}</FooterLink>
              <FooterLink href="/experience">{nav("experience")}</FooterLink>
              <FooterLink href="/gallery">{nav("gallery")}</FooterLink>
              <FooterLink href="/saint-martin">{nav("saintMartin")}</FooterLink>
              <FooterLink href="/blog">{nav("blog")}</FooterLink>
            </ul>
          </nav>

          <nav aria-label={t("stay")}>
            <p className="eyebrow text-[#e4c891]">{t("stay")}</p>
            <ul className="mt-6 space-y-1">
              <FooterLink href="/rooms">{nav("rooms")}</FooterLink>
              <FooterLink href="/offers">{nav("offers")}</FooterLink>
              <FooterLink href="/packages">{nav("packages")}</FooterLink>
              <FooterLink href="/faq">{nav("faq")}</FooterLink>
              <FooterLink href="/cancellation-policy">{t("cancellation")}</FooterLink>
              <FooterLink href="/contact">{nav("contact")}</FooterLink>
            </ul>
          </nav>

          <div>
            <p className="eyebrow text-[#e4c891]">{t("contact")}</p>
            <ul className="mt-6 space-y-5 text-[0.95rem] text-white/75">
              <li>
                <a href={contact.maps} target="_blank" rel="noreferrer" className="group flex gap-3 transition-colors hover:text-white">
                  <MapPin aria-hidden className="mt-1 h-4 w-4 shrink-0 text-gold" strokeWidth={1.5} />
                  <span className="whitespace-pre-line leading-relaxed">{tContact("address")}</span>
                </a>
              </li>
              <li>
                <a href={contact.tel} className="flex items-center gap-3 transition-colors hover:text-white">
                  <Phone aria-hidden className="h-4 w-4 shrink-0 text-gold" strokeWidth={1.5} />
                  {contact.phoneDisplay}
                </a>
              </li>
              <li>
                <a href={contact.whatsapp} target="_blank" rel="noreferrer" className="flex items-center gap-3 transition-colors hover:text-white">
                  <WhatsAppIcon className="h-4 w-4 shrink-0 text-gold" />
                  {tCommon("whatsapp")}
                </a>
              </li>
              <li>
                <a href={contact.mailto} className="flex items-center gap-3 break-all transition-colors hover:text-white">
                  <Mail aria-hidden className="h-4 w-4 shrink-0 text-gold" strokeWidth={1.5} />
                  {contact.email}
                </a>
              </li>
            </ul>
            <p className="eyebrow mt-10 text-[#e4c891]">{t("follow")}</p>
            <a
              href={contact.facebook}
              target="_blank"
              rel="noreferrer"
              className="group mt-4 inline-flex min-h-11 items-center gap-3 rounded-full border border-white/20 px-5 text-[0.7rem] font-medium uppercase tracking-[0.18em] transition-colors duration-500 hover:border-white hover:bg-white hover:text-navy-deep"
            >
              Facebook
              <ArrowUpRight aria-hidden className="h-4 w-4 transition-transform duration-500 group-hover:-translate-y-0.5 group-hover:translate-x-0.5" strokeWidth={1.5} />
            </a>
          </div>
        </div>

        <p
          aria-hidden
          className="pointer-events-none select-none whitespace-nowrap pt-10 text-center font-display text-[clamp(4rem,1rem+12vw,12rem)] font-light leading-[0.9] text-white/[0.06]"
        >
          Amar Bari
        </p>

        <div className="flex flex-col items-start justify-between gap-6 border-t border-white/10 py-8 md:flex-row md:items-center">
          <p className="text-[0.72rem] tracking-[0.08em] text-white/50">
            {t("rights")} · {new Date().getFullYear()}
          </p>
          <ul className="flex flex-wrap items-center gap-x-6 gap-y-2 text-[0.66rem] uppercase tracking-[0.2em] text-white/55">
            <li>
              <Link href="/privacy-policy" className="link-underline transition-colors hover:text-white">
                {t("privacy")}
              </Link>
            </li>
            <li>
              <Link href="/terms" className="link-underline transition-colors hover:text-white">
                {t("terms")}
              </Link>
            </li>
            <li>
              <Link href="/credits" className="link-underline transition-colors hover:text-white">
                {t("credits")}
              </Link>
            </li>
          </ul>
          <BackToTop label={t("backToTop")} />
        </div>
      </div>
    </footer>
  );
}
