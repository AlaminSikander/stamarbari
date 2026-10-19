import type { ReactNode } from "react";
import { Reveal } from "@/components/motion/Reveal";
import { TextReveal } from "@/components/motion/TextReveal";

type Tone = "ivory" | "white" | "sand" | "ink";

const tones: Record<Tone, string> = {
  ivory: "bg-ivory text-navy",
  white: "bg-white text-navy",
  sand: "bg-sand/60 text-navy",
  ink: "bg-navy-deep text-white",
};

export function Container({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <div className={`container-lux ${className}`}>{children}</div>;
}

export function Section({
  children,
  tone = "ivory",
  overlap = false,
  className = "",
  id,
  label,
}: {
  children: ReactNode;
  tone?: Tone;
  /** Rounded top corners that slide over the previous section. */
  overlap?: boolean;
  className?: string;
  id?: string;
  label?: string;
}) {
  return (
    <section
      id={id}
      aria-label={label}
      className={`relative section-y ${tones[tone]} ${overlap ? "rounded-top-lux -mt-10 md:-mt-14 z-10" : ""} ${className}`}
    >
      {children}
    </section>
  );
}

export function Eyebrow({
  children,
  tone = "gold",
  line = false,
  className = "",
}: {
  children: ReactNode;
  tone?: "gold" | "light" | "muted";
  line?: boolean;
  className?: string;
}) {
  const color = tone === "gold" ? "text-gold" : tone === "light" ? "text-white/70" : "text-muted";
  return (
    <p className={`eyebrow inline-flex items-center gap-3 ${color} ${className}`}>
      {line ? <span aria-hidden className="h-px w-8 bg-current opacity-70" /> : null}
      {children}
    </p>
  );
}

export function GoldRule({ className = "" }: { className?: string }) {
  return <span aria-hidden className={`block h-px w-12 bg-gold ${className}`} />;
}

export function Ornament({ className = "", tone = "gold" }: { className?: string; tone?: "gold" | "light" }) {
  const color = tone === "gold" ? "text-gold" : "text-white/60";
  return (
    <span aria-hidden className={`flex items-center justify-center gap-3 ${color} ${className}`}>
      <span className="h-px w-14 bg-current opacity-60" />
      <svg viewBox="0 0 12 12" className="h-2.5 w-2.5" fill="none" stroke="currentColor" strokeWidth="1">
        <path d="M6 0.8 11.2 6 6 11.2 0.8 6Z" />
      </svg>
      <span className="h-px w-14 bg-current opacity-60" />
    </span>
  );
}

export function SectionHeading({
  eyebrow,
  title,
  accent,
  intro,
  align = "center",
  tone = "dark",
  as = "h2",
  size = "lg",
  className = "",
}: {
  eyebrow?: string;
  title: string;
  /** Rendered as a gold italic continuation of the title. */
  accent?: string;
  intro?: string;
  align?: "center" | "left";
  tone?: "dark" | "light";
  as?: "h1" | "h2";
  size?: "lg" | "md";
  className?: string;
}) {
  const centered = align === "center";
  const titleColor = tone === "light" ? "text-white" : "text-navy";
  const introColor = tone === "light" ? "text-white/70" : "text-navy/65";
  return (
    <div className={`${centered ? "mx-auto text-center" : "text-left"} max-w-3xl ${className}`}>
      {eyebrow ? (
        <Reveal>
          <Eyebrow>{eyebrow}</Eyebrow>
        </Reveal>
      ) : null}
      <TextReveal
        as={as}
        text={title}
        accent={accent}
        className={`${size === "lg" ? "display-lg" : "display-md"} mt-5 ${titleColor}`}
      />
      <Reveal delay={0.2}>
        <GoldRule className={`mt-7 ${centered ? "mx-auto" : ""}`} />
      </Reveal>
      {intro ? (
        <Reveal delay={0.25}>
          <p className={`lede mt-7 ${introColor} ${centered ? "mx-auto max-w-2xl" : "max-w-xl"}`}>{intro}</p>
        </Reveal>
      ) : null}
    </div>
  );
}
