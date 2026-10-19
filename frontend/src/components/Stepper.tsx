"use client";

import { Minus, Plus } from "lucide-react";
import { useTranslations } from "next-intl";

type StepperProps = {
  value: number;
  min: number;
  max: number;
  onChange: (value: number) => void;
  label: string;
};

export function Stepper({ value, min, max, onChange, label }: StepperProps) {
  const t = useTranslations("bookingPanel");
  const button =
    "inline-flex h-11 w-11 items-center justify-center rounded-full text-navy transition-colors duration-300 hover:bg-navy-deep hover:text-white disabled:pointer-events-none disabled:opacity-25";
  return (
    <div className="inline-flex h-12 w-full items-center justify-between rounded-full border border-line bg-white px-0.5">
      <button
        type="button"
        aria-label={t("decrease", { label })}
        className={button}
        disabled={value <= min}
        onClick={() => onChange(Math.max(min, value - 1))}
      >
        <Minus aria-hidden className="h-4 w-4" strokeWidth={1.5} />
      </button>
      <span className="min-w-8 text-center font-display text-xl tabular-nums text-navy-deep" aria-live="polite">
        {value}
      </span>
      <button
        type="button"
        aria-label={t("increase", { label })}
        className={button}
        disabled={value >= max}
        onClick={() => onChange(Math.min(max, value + 1))}
      >
        <Plus aria-hidden className="h-4 w-4" strokeWidth={1.5} />
      </button>
    </div>
  );
}
