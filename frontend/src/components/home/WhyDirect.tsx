import { CalendarRange, FileText, Headphones, MessageCircle, Sparkles } from "lucide-react";
import { getTranslations } from "next-intl/server";
import { Stagger, StaggerItem } from "../motion/Reveal";
import { ButtonLink } from "../ui/Button";
import { SectionHeading } from "../ui/Section";

const icons = [MessageCircle, FileText, CalendarRange, Headphones, Sparkles];

/** Vertical hairlines between cells for the 2-column and 3-column layouts. */
function dividers(index: number) {
  const sm = index % 2 === 0 ? "sm:border-r" : "sm:border-r-0";
  const lg = index % 3 === 2 ? "lg:border-r-0" : "lg:border-r";
  return `border-white/10 ${sm} ${lg}`;
}

export async function WhyDirect() {
  const t = await getTranslations("home");
  const why = await getTranslations("why");
  const nav = await getTranslations("nav");
  const items = why.raw("items") as string[];

  return (
    <section className="rounded-top-lux grain relative z-10 -mt-10 overflow-hidden bg-navy-deep py-[var(--section-y)] text-white md:-mt-14">
      <div aria-hidden className="pointer-events-none absolute -bottom-48 -left-32 h-[480px] w-[480px] rounded-full bg-gold/10 blur-[120px]" />
      <div className="container-lux relative">
        <SectionHeading tone="light" eyebrow={t("whyEyebrow")} title={t("whyLead")} accent={t("whyAccent")} intro={t("whyIntro")} />
        <Stagger as="ul" className="mt-16 grid border-t border-white/10 sm:grid-cols-2 lg:grid-cols-3 md:mt-20">
          {items.map((item, i) => {
            const Icon = icons[i % icons.length];
            return (
              <StaggerItem
                as="li"
                key={item}
                className={`group relative border-b border-white/10 px-2 py-10 sm:px-8 ${dividers(i)}`}
              >
                <span className="font-sans text-[0.62rem] tracking-[0.24em] text-white/40">{String(i + 1).padStart(2, "0")}</span>
                <span className="mt-6 flex h-14 w-14 items-center justify-center rounded-full border border-gold/40 text-gold transition-colors duration-500 group-hover:bg-gold group-hover:text-navy-deep">
                  <Icon aria-hidden className="h-5 w-5" strokeWidth={1.3} />
                </span>
                <p className="mt-6 max-w-[18rem] font-display text-2xl font-light leading-snug">{item}</p>
              </StaggerItem>
            );
          })}
          <StaggerItem as="li" className={`flex flex-col justify-center gap-6 border-b border-white/10 px-2 py-10 sm:px-8 ${dividers(items.length)}`}>
            <p className="font-display text-2xl font-light italic text-gold">{t("ctaEyebrow")}</p>
            <div>
              <ButtonLink href="/booking" arrow>
                {nav("bookNow")}
              </ButtonLink>
            </div>
          </StaggerItem>
        </Stagger>
      </div>
    </section>
  );
}
