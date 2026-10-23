import type { ApiError, ApiSuccess } from "./types";

/** Public API base for the browser and for links. Defaults are set in next.config.ts. */
export const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1").replace(/\/$/, "");
export const API_ORIGIN = API_URL.replace(/\/api\/v1\/?$/, "");
/** Public base URL of the uploads bucket when the backend stores files in S3 (FILE_STORAGE=s3). */
const MEDIA_URL = (process.env.NEXT_PUBLIC_MEDIA_URL ?? "").replace(/\/$/, "");

/**
 * Base for API requests. On the server, Vercel's backend service binding (BACKEND_INTERNAL_URL) is
 * preferred so server-rendered pages call Laravel directly. The binding only exists in functions at
 * runtime, not during `next build`, so build-time fetches use the public URL and fall back on failure.
 */
function requestBase(): string {
  const internal = typeof window === "undefined" ? process.env.BACKEND_INTERNAL_URL : undefined;
  if (!internal) return API_URL;
  return new URL("api/v1", internal.endsWith("/") ? internal : `${internal}/`).toString();
}

export function mediaUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith("http://") || path.startsWith("https://")) return path;
  if (path.startsWith("/brand/") || path.startsWith("/images/")) return path;
  const clean = path.replace(/^\//, "");
  if (MEDIA_URL) return `${MEDIA_URL}/${clean.replace(/^storage\//, "")}`;
  if (clean.startsWith("storage/")) return `${API_ORIGIN}/${clean}`;
  return `${API_ORIGIN}/storage/${clean}`;
}

export class ApiRequestError extends Error {
  constructor(
    message: string,
    public status: number,
    public body?: ApiError,
  ) {
    super(message);
  }
}

export async function apiGet<T>(path: string, locale = "en"): Promise<T> {
  const separator = path.includes("?") ? "&" : "?";
  const response = await fetch(`${requestBase()}${path}${separator}locale=${locale}`, {
    next: path.includes("availability") || path.includes("booking") || path.startsWith("/rooms") || path.startsWith("/blog") || path.startsWith("/offers") || path.startsWith("/pages") || path.startsWith("/faqs")
      ? { revalidate: 0 }
      : { revalidate: 120 },
    headers: { Accept: "application/json" },
  });
  return parse(response);
}

export async function apiPost<T>(path: string, body: unknown): Promise<T> {
  const response = await fetch(`${requestBase()}${path}`, {
    method: "POST",
    cache: "no-store",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify(body),
  });
  return parse(response);
}

const GENERIC_ERROR = "Something went wrong. Please try again.";

async function parse<T>(response: Response): Promise<T> {
  const json = (await response.json().catch(() => null)) as ApiSuccess<T> | ApiError | null;
  if (!response.ok || !json?.success) {
    // Server errors carry no message meant for guests.
    const message = response.status < 500 && json && !json.success && json.message ? json.message : GENERIC_ERROR;
    throw new ApiRequestError(message, response.status, json && !json.success ? json : undefined);
  }
  return json.data;
}

export function formatBdt(value: number | null | undefined): string {
  if (value === null || value === undefined) return "TBD";
  return `৳${value.toLocaleString("en-BD")}`;
}
