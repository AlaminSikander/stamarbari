export function PageSkeleton({ cards = 6, compact = false }: { cards?: number; compact?: boolean }) {
  return (
    <div aria-busy="true" aria-live="polite">
      <div className={`relative overflow-hidden bg-navy-deep ${compact ? "h-[46svh] min-h-[380px]" : "h-[62svh] min-h-[520px]"}`}>
        <div className="container-lux flex h-full flex-col justify-end gap-5 pb-16">
          <span className="block h-3 w-40 rounded-full bg-white/10" />
          <span className="block h-px w-12 bg-gold/60" />
          <span className="block h-12 w-2/3 max-w-xl rounded-2xl bg-white/10 md:h-16" />
          <span className="block h-4 w-1/2 max-w-md rounded-full bg-white/10" />
        </div>
      </div>
      <div className="rounded-top-lux relative z-10 -mt-10 bg-ivory py-20 md:-mt-14">
        <div className="container-lux grid gap-x-8 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: cards }, (_, i) => (
            <div key={i}>
              <div className="skeleton aspect-[4/3] rounded-[24px]" />
              <div className="skeleton mt-6 h-6 w-2/3 rounded-full" />
              <div className="skeleton mt-3 h-4 w-full rounded-full" />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
