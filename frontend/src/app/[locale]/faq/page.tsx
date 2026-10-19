import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { WhatsAppIcon } from "@/components/icons/WhatsAppIcon";
import { Reveal } from "@/components/motion/Reveal";
import { PageHero } from "@/components/PageHero";
import { Accordion } from "@/components/ui/Accordion";
import { ButtonLink } from "@/components/ui/Button";
import { EmptyState } from "@/components/ui/EmptyState";
import { Container, Section } from "@/components/ui/Section";
import { apiGet } from "@/lib/api";
import { contact } from "@/lib/contact";
import { stripHtml } from "@/lib/html";

export const metadata: Metadata = {
  title: "FAQ",
  description: "Questions guests often ask about S T Amar Bari Resort.",
};

type Faq = {
  id: number;
  question_en: string;
  question_bn?: string | null;
  answer_en: string;
  answer_bn?: string | null;
};

export default async function FaqPage({
  params,
}: {
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("faqPage");
  const tNav = await getTranslations("nav");
  let faqs: Faq[] = [];
  try {
    faqs = await apiGet<Faq[]>("/faqs", locale);
  } catch {
    faqs = [];
  }

  const items = faqs.map((faq) => ({
    id: faq.id,
    title: locale === "bn" && faq.question_bn ? faq.question_bn : faq.question_en,
    body: locale === "bn" && faq.answer_bn ? faq.answer_bn : faq.answer_en,
  }));

  const schema = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((item) => ({
      "@type": "Question",
      name: item.title,
      acceptedAnswer: { "@type": "Answer", text: stripHtml(item.body) || item.body },
    })),
  };

  return (
    <>
      {items.length ? <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} /> : null}
      <PageHero compact route="faq" eyebrow={t("eyebrow")} title={t("title")} subtitle={t("subtitle")} />
      <Section tone="ivory" overlap>
        <Container>
          <div className="grid gap-14 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-20">
            <Reveal className="min-w-0">
              {items.length === 0 ? <EmptyState>{t("empty")}</EmptyState> : <Accordion items={items} />}
            </Reveal>
            <Reveal as="aside" delay={0.15} className="lg:sticky lg:top-28 lg:self-start">
              <div className="relative overflow-hidden rounded-[28px] bg-navy-deep p-8 text-white md:p-10">
                <div aria-hidden className="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-teal/30 blur-[80px]" />
                <span aria-hidden className="relative block h-px w-10 bg-gold" />
                <p className="relative mt-6 font-display text-[1.9rem] font-light leading-tight">{t("moreTitle")}</p>
                <p className="relative mt-4 text-sm leading-relaxed text-white/70">{t("moreBody")}</p>
                <div className="relative mt-8 grid gap-3">
                  <ButtonLink href={contact.whatsapp} variant="whatsapp">
                    <span className="inline-flex items-center gap-2.5">
                      <WhatsAppIcon className="h-4 w-4" />
                      {t("moreCta")}
                    </span>
                  </ButtonLink>
                  <ButtonLink href="/contact" variant="ghost-light">
                    {tNav("contact")}
                  </ButtonLink>
                </div>
              </div>
            </Reveal>
          </div>
        </Container>
      </Section>
    </>
  );
}
