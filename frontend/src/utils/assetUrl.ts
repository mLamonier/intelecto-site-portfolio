import { sitePath } from "../services/site";

function safeUrl(url: string): string {
  return encodeURI(url);
}

export function resolveAssetUrl(path?: string | null): string {
  if (!path) return "";
  if (path.startsWith("http://") || path.startsWith("https://")) {
    return safeUrl(path);
  }

  const clean = path.replace(/^\/+/, "");

  if (clean.startsWith("frontend/public/assets/")) {
    const relative = clean.replace(/^frontend\/public\//, "");
    return safeUrl(`/${relative}`);
  }

  if (clean.startsWith("assets/")) {
    return safeUrl(`/${clean}`);
  }

  return safeUrl(sitePath(clean));
}

export function resolveHomepageImage(path?: string | null): string {
  return resolveAssetUrl(path);
}
