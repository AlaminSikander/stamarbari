"use client";

import { CheckCircle2, X } from "lucide-react";
import { useEffect } from "react";

export function Toast({
  message,
  dismissLabel,
  onDismiss,
  duration = 9000,
}: {
  message: string;
  dismissLabel: string;
  onDismiss: () => void;
  duration?: number;
}) {
  useEffect(() => {
    const timer = window.setTimeout(onDismiss, duration);
    return () => window.clearTimeout(timer);
  }, [onDismiss, duration]);

  return (
    <div className="pointer-events-none fixed inset-x-0 top-24 z-[60] flex justify-center px-4 md:top-28">
      <div
        role="status"
        aria-live="polite"
        className="toast-in pointer-events-auto flex w-full max-w-lg items-start gap-3 rounded-[20px] border border-[#1f6f5c]/25 bg-white px-5 py-4 text-sm text-navy-deep shadow-[0_30px_60px_-30px_rgba(10,34,54,0.55)]"
      >
        <CheckCircle2 aria-hidden className="mt-0.5 h-5 w-5 shrink-0 text-[#1f6f5c]" strokeWidth={1.75} />
        <p className="flex-1 leading-relaxed">{message}</p>
        <button
          type="button"
          onClick={onDismiss}
          aria-label={dismissLabel}
          className="-mr-1 -mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-muted transition-colors hover:bg-ivory hover:text-navy-deep"
        >
          <X aria-hidden className="h-4 w-4" strokeWidth={1.75} />
        </button>
      </div>
    </div>
  );
}
