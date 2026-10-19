import { looksLikeHtml, sanitizeAdminHtml } from "@/lib/html";

export function RichHtml({ html, className = "text-lg leading-relaxed text-navy/80" }: { html: string; className?: string }) {
  if (looksLikeHtml(html)) {
    return <div className={`rich-content ${className}`} dangerouslySetInnerHTML={{ __html: sanitizeAdminHtml(html) }} />;
  }

  return <div className={`whitespace-pre-line ${className}`}>{html}</div>;
}
