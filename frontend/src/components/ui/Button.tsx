import { ArrowRight } from "lucide-react";
import type { ComponentProps, ReactNode } from "react";
import { Link } from "@/i18n/navigation";

type Variant = "gold" | "ink" | "ghost-light" | "ghost-dark" | "whatsapp";
type Size = "md" | "lg";

const base =
  "group btn-shine relative inline-flex items-center justify-center gap-3 overflow-hidden rounded-full font-sans font-medium uppercase tracking-[0.16em] transition-[color,background-color,border-color,transform,box-shadow] duration-500 ease-lux hover:-translate-y-0.5 focus-visible:-translate-y-0.5 disabled:pointer-events-none disabled:opacity-50";

const sizes: Record<Size, string> = {
  md: "min-h-12 px-7 text-[0.72rem]",
  lg: "min-h-14 px-9 text-[0.76rem]",
};

const variants: Record<Variant, { root: string; fill: string }> = {
  gold: {
    root: "bg-gold text-navy-deep shadow-[0_18px_40px_-20px_rgba(195,154,85,0.9)] hover:text-white",
    fill: "bg-gold-dark",
  },
  ink: {
    root: "bg-navy-deep text-white shadow-[0_18px_40px_-22px_rgba(10,34,54,0.9)] hover:text-navy-deep",
    fill: "bg-gold",
  },
  "ghost-light": {
    root: "border border-white/45 text-white backdrop-blur-sm hover:border-white hover:text-navy-deep",
    fill: "bg-white",
  },
  "ghost-dark": {
    root: "border border-navy/25 text-navy hover:border-navy-deep hover:text-white",
    fill: "bg-navy-deep",
  },
  whatsapp: {
    root: "bg-[#1f6f5c] text-white hover:text-white",
    fill: "bg-[#155446]",
  },
};

function Inner({ children, arrow, fill }: { children: ReactNode; arrow?: boolean; fill: string }) {
  return (
    <>
      <span
        aria-hidden
        className={`absolute inset-0 -z-10 origin-left scale-x-0 rounded-full transition-transform duration-500 ease-lux group-hover:scale-x-100 ${fill}`}
      />
      <span className="relative">{children}</span>
      {arrow ? (
        <ArrowRight
          aria-hidden
          className="relative h-4 w-4 transition-transform duration-500 ease-lux group-hover:translate-x-1"
          strokeWidth={1.5}
        />
      ) : null}
    </>
  );
}

type Common = {
  variant?: Variant;
  size?: Size;
  arrow?: boolean;
  className?: string;
  children: ReactNode;
};

type LinkButtonProps = Common & { href: string; external?: boolean } & Omit<ComponentProps<"a">, "href" | "className" | "children">;

export function ButtonLink({ variant = "gold", size = "md", arrow, className = "", children, href, external, ...rest }: LinkButtonProps) {
  const v = variants[variant];
  const classes = `${base} ${sizes[size]} ${v.root} ${className}`;
  if (external || href.startsWith("#") || href.startsWith("http") || href.startsWith("tel:") || href.startsWith("mailto:")) {
    return (
      <a href={href} className={classes} {...(href.startsWith("http") ? { target: "_blank", rel: "noreferrer" } : {})} {...rest}>
        <Inner arrow={arrow} fill={v.fill}>
          {children}
        </Inner>
      </a>
    );
  }
  return (
    <Link href={href} className={classes} {...rest}>
      <Inner arrow={arrow} fill={v.fill}>
        {children}
      </Inner>
    </Link>
  );
}

type ButtonProps = Common & Omit<ComponentProps<"button">, "className" | "children">;

export function Button({ variant = "gold", size = "md", arrow, className = "", children, type = "button", ...rest }: ButtonProps) {
  const v = variants[variant];
  return (
    <button type={type} className={`${base} ${sizes[size]} ${v.root} ${className}`} {...rest}>
      <Inner arrow={arrow} fill={v.fill}>
        {children}
      </Inner>
    </button>
  );
}

/** Understated text link with an animated underline and arrow. */
export function TextLink({
  href,
  children,
  tone = "dark",
  className = "",
}: {
  href: string;
  children: ReactNode;
  tone?: "dark" | "light";
  className?: string;
}) {
  const color = tone === "light" ? "text-white" : "text-navy";
  return (
    <Link
      href={href}
      className={`group inline-flex items-center gap-3 text-[0.72rem] font-medium uppercase tracking-[0.2em] ${color} ${className}`}
    >
      <span className="link-underline">{children}</span>
      <ArrowRight
        aria-hidden
        className="h-4 w-4 text-gold transition-transform duration-500 ease-lux group-hover:translate-x-1.5"
        strokeWidth={1.5}
      />
    </Link>
  );
}
