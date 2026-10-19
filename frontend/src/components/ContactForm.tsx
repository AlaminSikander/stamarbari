"use client";

import { CheckCircle2, Loader2, Send } from "lucide-react";
import { FormEvent, useState } from "react";
import { useTranslations } from "next-intl";
import { apiPost, ApiRequestError } from "@/lib/api";

const floatInput =
  "peer block min-h-14 w-full rounded-[14px] border border-line bg-white px-4 pb-2 pt-6 text-[0.95rem] text-navy transition-[border-color,box-shadow] duration-300 placeholder:text-transparent hover:border-navy/30 focus:border-gold focus:outline-none focus:ring-4 focus:ring-gold/15";
const floatLabel =
  "pointer-events-none absolute left-4 top-[1.1rem] origin-left text-[0.92rem] text-muted transition-all duration-300 ease-lux peer-focus:top-2 peer-focus:text-[0.62rem] peer-focus:uppercase peer-focus:tracking-[0.18em] peer-focus:text-gold peer-[:not(:placeholder-shown)]:top-2 peer-[:not(:placeholder-shown)]:text-[0.62rem] peer-[:not(:placeholder-shown)]:uppercase peer-[:not(:placeholder-shown)]:tracking-[0.18em]";

function Field({ name, label, type = "text", required, autoComplete }: { name: string; label: string; type?: string; required?: boolean; autoComplete?: string }) {
  return (
    <label className="relative block">
      <input name={name} type={type} required={required} autoComplete={autoComplete} placeholder={label} className={floatInput} />
      <span className={floatLabel}>{label}</span>
    </label>
  );
}

export function ContactForm() {
  const t = useTranslations("contact");
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [sending, setSending] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const element = event.currentTarget;
    const form = new FormData(element);
    setError(null);
    setStatus(null);
    setSending(true);
    try {
      await apiPost("/contact", {
        name: form.get("name"),
        email: form.get("email"),
        phone: form.get("phone"),
        subject: form.get("subject"),
        message: form.get("message"),
        website: form.get("website"),
      });
      setStatus(t("success"));
      element.reset();
    } catch (err: unknown) {
      setError(err instanceof ApiRequestError ? err.message : t("error"));
    } finally {
      setSending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="rounded-[28px] border border-line bg-white/80 p-6 shadow-[0_40px_80px_-50px_rgba(10,34,54,0.4)] backdrop-blur md:p-10">
      <span aria-hidden className="block h-px w-10 bg-gold" />
      <h2 className="mt-5 font-display text-[2.2rem] font-light leading-tight text-navy-deep">{t("formTitle")}</h2>
      <div className="mt-8 grid gap-4">
        <input name="website" className="hidden" tabIndex={-1} autoComplete="off" aria-hidden />
        <Field name="name" label={t("name")} required autoComplete="name" />
        <div className="grid gap-4 md:grid-cols-2">
          <Field name="email" type="email" label={t("email")} autoComplete="email" />
          <Field name="phone" type="tel" label={t("phone")} autoComplete="tel" />
        </div>
        <label className="relative block">
          <span className="sr-only">{t("subject")}</span>
          <select name="subject" defaultValue="" className={`${floatInput} appearance-none pt-2 pb-2 text-navy`}>
            <option value="">{t("subjectHint")}</option>
            <option value="General">{t("subjects.general")}</option>
            <option value="Reservation">{t("subjects.reservation")}</option>
            <option value="Feedback">{t("subjects.feedback")}</option>
            <option value="Other">{t("subjects.other")}</option>
          </select>
          <span aria-hidden className="pointer-events-none absolute right-5 top-1/2 h-2 w-2 -translate-y-1/2 rotate-45 border-b border-r border-navy/50" />
        </label>
        <label className="relative block">
          <textarea required name="message" placeholder={t("message")} className={`${floatInput} min-h-36 resize-y pt-7`} />
          <span className={floatLabel}>{t("message")}</span>
        </label>
        <button
          className="group btn-shine relative mt-2 inline-flex min-h-14 items-center justify-center gap-3 overflow-hidden rounded-full bg-navy-deep px-8 text-[0.76rem] font-medium uppercase tracking-[0.18em] text-white transition-colors duration-500 hover:text-navy-deep disabled:opacity-50"
          type="submit"
          disabled={sending}
        >
          <span aria-hidden className="absolute inset-0 origin-left scale-x-0 rounded-full bg-gold transition-transform duration-500 ease-lux group-hover:scale-x-100" />
          <span className="relative">{t("send")}</span>
          {sending ? (
            <Loader2 aria-hidden className="relative h-4 w-4 animate-spin" />
          ) : (
            <Send aria-hidden className="relative h-4 w-4 transition-transform duration-500 group-hover:translate-x-1" strokeWidth={1.5} />
          )}
        </button>
        <div aria-live="polite">
          {status ? (
            <p className="flex items-center gap-2 rounded-2xl bg-forest/10 px-4 py-3 text-sm text-forest">
              <CheckCircle2 aria-hidden className="h-4 w-4" strokeWidth={1.5} />
              {status}
            </p>
          ) : null}
          {error ? <p className="rounded-2xl border border-muted-red/25 bg-muted-red/5 px-4 py-3 text-sm text-muted-red">{error}</p> : null}
        </div>
      </div>
    </form>
  );
}
