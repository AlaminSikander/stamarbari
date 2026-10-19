import type { ApiError, ApiSuccess } from "./types";

export const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";
export const API_ORIGIN = API_URL.replace(/\/api\/v1\/?$/, "");

export function mediaUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith("http://") || path.startsWith("https://")) return path;
  if (path.startsWith("/brand/") || path.startsWith("/images/")) return path;
  const clean = path.replace(/^\//, "");
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
  const response = await fetch(`${API_URL}${path}${separator}locale=${locale}`, {
    next: path.includes("availability") || path.includes("booking") || path.startsWith("/rooms") || path.startsWith("/blog") || path.startsWith("/offers") || path.startsWith("/pages") || path.startsWith("/faqs")
      ? { revalidate: 0 }
      : { revalidate: 120 },
    headers: { Accept: "application/json" },
  });
  return parse(response);
}

export async function apiPost<T>(path: string, body: unknown): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
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

async function parse<T>(response: Response): Promise<T> {
  const json = (await response.json()) as ApiSuccess<T> | ApiError;
  if (!response.ok || !json.success) {
    const message =
      !json.success ? json.message : "Something went wrong. Please try again.";
    throw new ApiRequestError(message, response.status, json.success ? undefined : json);
  }
  return json.data;
}

export function formatBdt(value: number | null | undefined): string {
  if (value === null || value === undefined) return "TBD";
  return `৳${value.toLocaleString("en-BD")}`;
}
