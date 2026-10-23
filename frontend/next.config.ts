import type { NextConfig } from "next";
import createNextIntlPlugin from "next-intl/plugin";

const withNextIntl = createNextIntlPlugin("./src/i18n/request.ts");

const apiUrl = new URL(process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1");

// The admin dashboard (Laravel + Filament) is served from this site at /admin. Requests for
// it, and for the Livewire/Filament assets it loads, are proxied to the Laravel app.
// Rewrites are fixed at build time, so set BACKEND_ORIGIN before `next build`.
const backendOrigin = (process.env.BACKEND_ORIGIN || apiUrl.origin).replace(/\/$/, "");
const adminPaths = [
  "/admin",
  "/admin/:path*",
  "/livewire/:path*",
  "/filament/:path*",
  "/css/filament/:path*",
  "/js/filament/:path*",
  "/fonts/filament/:path*",
  "/storage/:path*",
];

const nextConfig: NextConfig = {
  output: "standalone",
  async rewrites() {
    return {
      beforeFiles: adminPaths.map((source) => ({ source, destination: `${backendOrigin}${source}` })),
      afterFiles: [],
      fallback: [],
    };
  },
  images: {
    formats: ["image/avif", "image/webp"],
    qualities: [70, 75, 85],
    remotePatterns: [
      {
        protocol: apiUrl.protocol.replace(":", "") as "http" | "https",
        hostname: apiUrl.hostname,
        port: apiUrl.port,
        pathname: "/storage/**",
      },
      { protocol: "http", hostname: "localhost" },
      { protocol: "https", hostname: "**.stamarbariresort.com" },
    ],
    // The Laravel API serves uploads from localhost during development only.
    dangerouslyAllowLocalIP: process.env.NODE_ENV !== "production",
  },
  async headers() {
    return [
      {
        source: "/(.*)",
        headers: [
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
          { key: "X-Frame-Options", value: "SAMEORIGIN" },
        ],
      },
      {
        source: "/images/stock/:path*",
        headers: [{ key: "Cache-Control", value: "public, max-age=31536000, immutable" }],
      },
    ];
  },
};

export default withNextIntl(nextConfig);
