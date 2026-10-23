import createMiddleware from "next-intl/middleware";
import { routing } from "./i18n/routing";

export default createMiddleware(routing);

export const config = {
  // /admin, /livewire, /filament and /storage are proxied to the Laravel admin (see next.config.ts)
  // and must not get a locale prefix.
  matcher: ["/((?!api|_next|_vercel|admin|livewire|filament|storage|.*\\..*).*)"],
};
