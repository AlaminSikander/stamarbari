import { ArrowLeft, Baby, Check, Clock, Leaf, ScrollText, ShieldAlert, Users, Wallet } from "lucide-react";
import { useTranslations } from "next-intl";
import type { ReactNode } from "react";
import { Link } from "@/i18n/navigation";
import { formatBdt } from "@/lib/api";
import type { BookingPolicies, RoomSummary } from "@/lib/types";
import { RoomBookingPanel } from "@/components/RoomBookingPanel";
import { RoomGallery } from "@/components/RoomGallery";
import { RichHtml } from "@/components/RichHtml";

export function RoomBookingLayout({
  room,
  policies,
  houseRules,
  showTitle = false,
}: {
  room: RoomSummary;
  policies: BookingPolicies;
  houseRules?: string | null;
  /** Render the room name here when the page hero above is generic (booking flow). */
  showTitle?: boolean;
}) {
  const t = useTranslations("roomDetail");
  const cancellationHours = policies.cancellation_hours;
  const refund = policies.cancellation_refund_percent;
  const checkIn = formatClock(policies.check_in_time);
  const checkOut = formatClock(policies.check_out_time);

  const details: { icon: ReactNode; label: string; value: string }[] = [
    ...(room.view ? [{ icon: <Leaf aria-hidden className="h-5 w-5" strokeWidth={1.25} />, label: t("view"), value: room.view }] : []),
    { icon: <Users aria-hidden className="h-5 w-5" strokeWidth={1.25} />, label: t("occupancy"), value: t("occupancyValue", { count: room.max_adults }) },
    { icon: <Baby aria-hidden className="h-5 w-5" strokeWidth={1.25} />, label: t("children"), value: t("childrenValue", { count: room.max_children }) },
    {
      icon: <Wallet aria-hidden className="h-5 w-5" strokeWidth={1.25} />,
      label: t("price"),
      value: room.starting_price ? t("priceValue", { price: formatBdt(room.starting_price) }) : t("onRequest"),
    },
  ];

  return (
    <div className="rounded-top-lux relative z-10 -mt-10 bg-ivory md:-mt-14">
      <div className="container-lux pb-28 pt-14 md:pt-20">
        <Link
          href="/rooms"
          className="group inline-flex min-h-11 items-center gap-3 text-[0.7rem] font-medium uppercase tracking-[0.2em] text-navy"
        >
          <ArrowLeft aria-hidden className="h-4 w-4 text-gold transition-transform duration-500 ease-lux group-hover:-translate-x-1" strokeWidth={1.5} />
          <span className="link-underline">{t("back")}</span>
        </Link>
        {showTitle ? (
          <div className="mt-6">
            <p className="eyebrow text-gold">{room.view ?? t("eyebrow")}</p>
            <h1 className="display-lg mt-4 font-light text-navy-deep">{room.name}</h1>
          </div>
        ) : null}

        <div className="mt-8 grid items-start gap-12 lg:grid-cols-[minmax(0,1fr)_400px] lg:gap-16">
          <div className="min-w-0">
            <RoomGallery room={room} />

            <section className="mt-16" aria-labelledby="room-overview">
              <p className="eyebrow text-gold">{t("overview")}</p>
              <h2 id="room-overview" className="display-md mt-4 text-navy-deep">
                {t("details")}
              </h2>
              <dl className="mt-8 grid grid-cols-2 border-t border-line md:grid-cols-4">
                {details.map((item) => (
                  <div key={item.label} className="border-b border-line py-6 pr-4 md:border-b-0 md:py-8">
                    <span className="text-gold">{item.icon}</span>
                    <dt className="field-label mt-4">{item.label}</dt>
                    <dd className="mt-1.5 font-display text-xl text-navy-deep">{item.value}</dd>
                  </div>
                ))}
              </dl>
              {room.description ? <p className="lede mt-8 max-w-2xl text-navy/75">{room.description}</p> : null}
            </section>

            <section className="mt-16 rounded-[28px] bg-sand/50 p-8 md:p-10" aria-labelledby="room-amenities">
              <h2 id="room-amenities" className="font-display text-[1.9rem] font-light text-navy-deep">
                {t("amenities")}
              </h2>
              <ul className="mt-6 grid gap-x-8 gap-y-4 md:grid-cols-2">
                {room.amenities.map((item) => (
                  <li key={item.slug} className="flex items-center gap-3 text-navy/85">
                    <Check aria-hidden className="h-4 w-4 shrink-0 text-gold" strokeWidth={1.5} />
                    {item.name}
                  </li>
                ))}
              </ul>
              <p className="mt-6 border-t border-navy/10 pt-5 text-sm text-muted">{t("honest")}</p>
            </section>

            <section className="mt-16" aria-labelledby="room-policies">
              <h2 id="room-policies" className="font-display text-[1.9rem] font-light text-navy-deep">
                {t("policies")}
              </h2>
              <div className="mt-6 divide-y divide-line border-y border-line">
                <Policy icon={<Clock aria-hidden className="h-5 w-5" strokeWidth={1.25} />} title={t("checkTimes")}>
                  <p>{t("checkIn", { time: checkIn })}</p>
                  <p>{t("checkOut", { time: checkOut })}</p>
                </Policy>
                <Policy icon={<ShieldAlert aria-hidden className="h-5 w-5" strokeWidth={1.25} />} title={t("cancellation")}>
                  <p>{t("cancellationBody", { hours: cancellationHours, refund })}</p>
                  <p className="mt-2">{t("missedShip")}</p>
                </Policy>
                <Policy icon={<ScrollText aria-hidden className="h-5 w-5" strokeWidth={1.25} />} title={t("houseRules")}>
                  <RichHtml html={houseRules || t("houseRulesFallback")} className="text-[0.95rem] leading-relaxed text-navy/75" />
                </Policy>
              </div>
            </section>
          </div>

          <div id="reserve" className="w-full scroll-mt-28">
            <RoomBookingPanel room={room} policies={policies} />
          </div>
        </div>
      </div>
    </div>
  );
}

function Policy({ icon, title, children }: { icon: ReactNode; title: string; children: ReactNode }) {
  return (
    <div className="grid gap-3 py-7 md:grid-cols-[220px_1fr] md:gap-8">
      <h3 className="flex items-center gap-3 font-display text-xl text-navy-deep">
        <span className="text-gold">{icon}</span>
        {title}
      </h3>
      <div className="text-[0.95rem] leading-relaxed text-navy/75">{children}</div>
    </div>
  );
}

function formatClock(value: string): string {
  const [h, m] = value.split(":");
  const hour = Number(h);
  if (Number.isNaN(hour)) return value;
  const suffix = hour >= 12 ? "PM" : "AM";
  const twelve = hour % 12 || 12;
  return `${twelve}:${m ?? "00"} ${suffix}`;
}
