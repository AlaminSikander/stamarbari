import type { Metadata, Viewport } from "next";
import { Cormorant_Garamond, Hind_Siliguri, Jost } from "next/font/google";
import { NextIntlClientProvider, hasLocale } from "next-intl";
import { getMessages, setRequestLocale } from "next-intl/server";
import { notFound } from "next/navigation";
import { routing } from "@/i18n/routing";
import { CookieBanner } from "@/components/CookieBanner";
import { FloatingActions } from "@/components/FloatingActions";
import { Footer } from "@/components/Footer";
import { Header } from "@/components/Header";
import { MotionProvider } from "@/components/motion/MotionProvider";
import { Preloader } from "@/components/motion/Preloader";
import { SmoothScroll } from "@/components/motion/SmoothScroll";
import "../globals.css";

const display = Cormorant_Garamond({
  subsets: ["latin"],
  weight: ["300", "400", "500", "600"],
  style: ["normal", "italic"],
  variable: "--font-display",
  display: "swap",
});

const body = Jost({
  subsets: ["latin"],
  variable: "--font-body",
  display: "swap",
});

const bangla = Hind_Siliguri({
  subsets: ["bengali", "latin"],
  weight: ["300", "400", "500", "600"],
  variable: "--font-bangla",
  display: "swap",
});

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export const viewport: Viewport = {
  themeColor: "#0a2236",
};

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"),
  title: {
    default: "S T Amar Bari Resort | Your Home on the Island",
    template: "%s | S T Amar Bari Resort",
  },
  description:
    "A peaceful Saint Martin beach resort with warm hospitality, comfortable rooms and the sea close by.",
  openGraph: {
    type: "website",
    siteName: "S T Amar Bari Resort",
    locale: "en_BD",
    images: [{ url: "/images/stock/hero-sunrise.webp", width: 2400, height: 1094 }],
  },
  twitter: { card: "summary_large_image" },
  alternates: {
    languages: { en: "/", bn: "/bn" },
  },
};

export default async function LocaleLayout({
  children,
  params,
}: {
  children: React.ReactNode;
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  if (!hasLocale(routing.locales, locale)) notFound();
  setRequestLocale(locale);
  const messages = await getMessages();

  return (
    <html
      lang={locale}
      className={`${display.variable} ${body.variable} ${bangla.variable} h-full`}
      suppressHydrationWarning
    >
      <body className={`flex min-h-full flex-col bg-ivory text-navy ${locale === "bn" ? "bn-body" : ""}`}>
        <NextIntlClientProvider messages={messages}>
          <MotionProvider>
            <Preloader />
            <SmoothScroll />
            <Header />
            <main id="main" className="flex-1">
              {children}
            </main>
            <Footer />
            <FloatingActions />
            <CookieBanner />
          </MotionProvider>
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
