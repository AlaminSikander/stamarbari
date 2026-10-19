import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { PageHero } from "@/components/PageHero";
import { RoomBookingLayout } from "@/components/RoomBookingLayout";
import { apiGet } from "@/lib/api";
import { roomImage, roomTransitionName } from "@/lib/rooms";
import type { BookingPolicies, RoomSummary } from "@/lib/types";

export async function generateMetadata({ params }: { params: Promise<{ locale: string; slug: string }> }): Promise<Metadata> {
  const { locale, slug } = await params;
  try {
    const room = await apiGet<RoomSummary>(`/rooms/${slug}`, locale);
    return {
      title: room.name,
      description: room.description?.slice(0, 160) ?? undefined,
      openGraph: { images: [roomImage(room).src] },
    };
  } catch {
    return {};
  }
}

export default async function RoomDetailPage({
  params,
}: {
  params: Promise<{ locale: string; slug: string }>;
}) {
  const { locale, slug } = await params;
  setRequestLocale(locale);
  const t = await getTranslations("roomDetail");
  const tNav = await getTranslations("nav");

  let data: [RoomSummary, BookingPolicies, { body: string }];
  try {
    data = await Promise.all([
      apiGet<RoomSummary>(`/rooms/${slug}`, locale),
      apiGet<BookingPolicies>("/bookings/policies", locale),
      apiGet<{ body: string }>("/pages/house-rules", locale).catch(() => ({ body: "" })),
    ]);
  } catch {
    notFound();
  }
  const [room, policies, house] = data;

  return (
    <>
      <PageHero
        route="rooms"
        image={roomImage(room).src}
        eyebrow={room.view ?? t("eyebrow")}
        title={room.name}
        crumbs={[{ label: tNav("rooms"), href: "/rooms" }]}
        actions={[{ label: t("reserve"), href: "#reserve" }]}
        transitionName={roomTransitionName(room.slug)}
      />
      <RoomBookingLayout room={room} policies={policies} houseRules={house.body} />
    </>
  );
}
