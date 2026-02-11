import { sitePath } from "./site";

export function assetUrl(path?: string | null): string {
  if (!path) return "";
  const clean = path.replace(/^\/+/, "");
  return sitePath(clean);
}
