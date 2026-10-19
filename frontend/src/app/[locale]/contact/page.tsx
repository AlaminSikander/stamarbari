import { Clock, Mail, MapPin, Navigation, Phone } from "lucide-react";
import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import type { ReactNode } from "react";
import { ContactForm } from "@/components/ContactForm";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { ImageReveal } from "@/components/motion/ImageReveal";
import { Reveal, Stagger, StaggerItem } from "@/components/motion/Reveal";
import { PageHero } from "@/components/PageHero";
import { ButtonLink } from "@/components/ui/Button";
import { Container, Section, SectionHeading } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import { contact } from "@/lib/contact";
import type { BookingPolicies } from "@/lib/types";

export const metadata: Metadata = {
  title: "Contact Us",
  description: "Contact S T Amar Bari Resort on Saint Martin Island.",
};

export default async function ContactPage({
  params,
}: {
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("contact");
  let policies: BookingPolicies | null = null;
  try {
    policies = await apiGet<BookingPolicies>("/bookings/policies", locale);
  } catch {
    policies = null;
  }

  const icon = "h-5 w-5";
  const cards: { icon: ReactNode; title: string; body: string; href?: string }[] = [
    { icon: <MapPin aria-hidden className={icon} strokeWidth={1.25} />, title: t("locationTitle"), body: t("address"), href: contact.maps },
    { icon: <Phone aria-hidden className={icon} strokeWidth={1.25} />, title: t("phoneTitle"), body: contact.phoneDisplay, href: contact.tel },
    { icon: <Mail aria-hidden className={icon} strokeWidth={1.25} />, title: t("emailTitle"), body: contact.email, href: contact.mailto },
    {
      icon: <Clock aria-hidden className={icon} strokeWidth={1.25} />,
      title: t("hoursTitle"),
      body: policies ? t("hoursValue", { checkIn: policies.check_in_time, checkOut: policies.check_out_time }) : t("hoursFallback"),
    },
  ];

  return (
    <>
      <PageHero
        route="contact"
        eyebrow={t("eyebrow")}
        title={t("title")}
        subtitle={t("intro")}
        actions={[
          { label: t("whatsapp"), href: contact.whatsapp, variant: "whatsapp" },
          { label: t("call"), href: contact.tel, variant: "ghost-light" },
        ]}
      />
      <Section tone="ivory" overlap>
        <Container>
          <div className="grid gap-14 lg:grid-cols-[1fr_1.1fr] lg:gap-20">
            <div>
              <SectionHeading align="left" eyebrow={t("detailsEyebrow")} title={t("getInTouch")} />
              <Stagger as="ul" className="mt-10 border-t border-line">
                {cards.map((card) => {
                  const content = (
                    <>
                      <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-gold/40 text-gold transition-colors duration-500 group-hover:bg-gold group-hover:text-navy-deep">
                        {card.icon}
                      </span>
                      <span>
                        <span className="field-label">{card.title}</span>
                        <span className="mt-1.5 block whitespace-pre-line font-display text-[1.3rem] leading-snug text-navy-deep">{card.body}</span>
                      </span>
                    </>
                  );
                  return (
                    <StaggerItem as="li" key={card.title} className="border-b border-line">
                      {card.href ? (
                        <a
                          href={card.href}
                          {...(card.href.startsWith("http") ? { target: "_blank", rel: "noreferrer" } : {})}
                          className="group flex items-start gap-5 py-6"
                        >
                          {content}
                        </a>
                      ) : (
                        <div className="group flex items-start gap-5 py-6">{content}</div>
                      )}
                    </StaggerItem>
                  );
                })}
              </Stagger>
              <Reveal delay={0.2} className="mt-10 flex flex-wrap gap-3">
                <ButtonLink href={contact.whatsapp} variant="whatsapp">
                  <span className="inline-flex items-center gap-2.5">
                    <WhatsAppIcon className="h-4 w-4" />
                    {t("whatsapp")}
                  </span>
                </ButtonLink>
                <ButtonLink href={contact.tel} variant="ghost-dark">
                  <span className="inline-flex items-center gap-2.5">
                    <Phone aria-hidden className="h-4 w-4" strokeWidth={1.5} />
                    {t("call")}
                  </span>
                </ButtonLink>
              </Reveal>
            </div>
            <Reveal delay={0.1}>
              <ContactForm />
            </Reveal>
          </div>
        </Container>
      </Section>

      <section className="bg-ivory pb-3 md:pb-5">
        <div className="container-lux">
          <Reveal className="mb-8 flex flex-wrap items-end justify-between gap-6">
            <div>
              <p className="eyebrow text-gold">{t("mapEyebrow")}</p>
              <p className="mt-3 font-display text-[2rem] font-light text-navy-deep">{t("locationTitle")}</p>
            </div>
            <a
              href={contact.maps}
              target="_blank"
              rel="noreferrer"
              className="group inline-flex min-h-11 items-center gap-3 text-[0.7rem] font-medium uppercase tracking-[0.2em] text-navy"
            >
              <Navigation aria-hidden className="h-4 w-4 text-gold" strokeWidth={1.5} />
              <span className="link-underline">{t("maps")}</span>
            </a>
          </Reveal>
        </div>
        <div className="px-3 md:px-5">
          <ImageReveal radius="32px" className="h-[60svh] min-h-[380px] bg-sand">
            <iframe
              title={t("mapTitle")}
              className="absolute inset-0 h-full w-full grayscale-[35%] contrast-[1.05]"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              src={contact.mapsEmbed}
            />
          </ImageReveal>
        </div>
      </section>
    </>
  );
}
