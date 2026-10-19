import type { ReactNode } from "react";
import { Ornament } from "./Section";

export function EmptyState({ children, actions }: { children: ReactNode; actions?: ReactNode }) {
  return (
    <div className="mx-auto max-w-xl rounded-[28px] border border-line bg-white px-8 py-12 text-center md:px-14 md:py-16">
      <Ornament />
      <p className="lede mt-6 text-navy/70">{children}</p>
      {actions ? <div className="mt-8 flex flex-wrap justify-center gap-3">{actions}</div> : null}
    </div>
  );
}
