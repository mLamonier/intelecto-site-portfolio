import type { ImgHTMLAttributes } from "react";
import responsiveImageManifest from "../../generated/responsiveImageManifest";
import { resolveAssetUrl } from "../../utils/assetUrl";

type LoadingMode = "lazy" | "eager";

interface VariantSource {
  src: string;
  width: number;
}

interface ManifestEntry {
  width: number;
  height: number;
  sources: {
    avif: VariantSource[];
    webp: VariantSource[];
    fallback: VariantSource[];
  };
}

type ManifestMap = Record<string, ManifestEntry>;

interface ResponsiveImageProps
  extends Omit<ImgHTMLAttributes<HTMLImageElement>, "src" | "srcSet" | "sizes"> {
  src: string;
  mobileSrc?: string | null;
  mobileMedia?: string;
  sizes?: string;
  loading?: LoadingMode;
  priority?: boolean;
}

const manifest = responsiveImageManifest as ManifestMap;
const canonicalManifest = new Map<string, ManifestEntry>(
  Object.entries(manifest).map(([key, value]) => [canonicalizePath(key), value]),
);

function canonicalizePath(input: string): string {
  return input
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .normalize("NFC");
}

function normalizeAssetPath(input: string): string {
  const trimmed = input.trim();
  if (!trimmed) return "";

  let pathname = trimmed;

  try {
    const url = new URL(trimmed, window.location.origin);
    pathname = url.pathname;
  } catch {
    pathname = trimmed;
  }

  pathname = decodeURI(pathname)
    .replace(/^https?:\/\/[^/]+/i, "")
    .replace(/^\/intelecto-site/, "")
    .replace(/^\/frontend\/public/, "")
    .replace(/^frontend\/public\//, "/")
    .replace(/^assets\//, "/assets/");

  if (!pathname.startsWith("/")) {
    pathname = `/${pathname}`;
  }

  return pathname;
}

function toSrcSet(variants: VariantSource[]): string | undefined {
  if (!variants || variants.length === 0) {
    return undefined;
  }

  return variants
    .map((variant) => `${resolveAssetUrl(variant.src)} ${variant.width}w`)
    .join(", ");
}

function getManifestEntry(source: string): ManifestEntry | undefined {
  const normalizedPath = normalizeAssetPath(source);
  if (manifest[normalizedPath]) {
    return manifest[normalizedPath];
  }
  return canonicalManifest.get(canonicalizePath(normalizedPath));
}

function getFallbackSource(source: string, entry?: ManifestEntry): string {
  if (!entry || entry.sources.fallback.length === 0) {
    return resolveAssetUrl(source);
  }

  return resolveAssetUrl(entry.sources.fallback[entry.sources.fallback.length - 1].src);
}

export default function ResponsiveImage({
  src,
  mobileSrc,
  mobileMedia = "(max-width: 768px)",
  sizes = "100vw",
  loading = "lazy",
  priority = false,
  alt,
  width,
  height,
  fetchPriority,
  decoding,
  ...imgProps
}: ResponsiveImageProps) {
  const desktopEntry = getManifestEntry(src);
  const mobileEntry = mobileSrc ? getManifestEntry(mobileSrc) : undefined;

  const normalizedDesktopLoading: LoadingMode = priority ? "eager" : loading;
  const normalizedFetchPriority = priority ? "high" : fetchPriority;

  const desktopFallbackSrc = getFallbackSource(src, desktopEntry);
  const desktopFallbackSrcSet = desktopEntry
    ? toSrcSet(desktopEntry.sources.fallback)
    : undefined;

  const mobileAvifSet = mobileEntry ? toSrcSet(mobileEntry.sources.avif) : undefined;
  const mobileWebpSet = mobileEntry ? toSrcSet(mobileEntry.sources.webp) : undefined;
  const mobileFallbackSet = mobileEntry
    ? toSrcSet(mobileEntry.sources.fallback)
    : undefined;

  const desktopAvifSet = desktopEntry ? toSrcSet(desktopEntry.sources.avif) : undefined;
  const desktopWebpSet = desktopEntry ? toSrcSet(desktopEntry.sources.webp) : undefined;

  return (
    <picture>
      {mobileAvifSet && (
        <source media={mobileMedia} type="image/avif" srcSet={mobileAvifSet} sizes={sizes} />
      )}
      {mobileWebpSet && (
        <source media={mobileMedia} type="image/webp" srcSet={mobileWebpSet} sizes={sizes} />
      )}
      {mobileFallbackSet && (
        <source media={mobileMedia} srcSet={mobileFallbackSet} sizes={sizes} />
      )}

      {desktopAvifSet && <source type="image/avif" srcSet={desktopAvifSet} sizes={sizes} />}
      {desktopWebpSet && <source type="image/webp" srcSet={desktopWebpSet} sizes={sizes} />}
      {desktopFallbackSrcSet && <source srcSet={desktopFallbackSrcSet} sizes={sizes} />}

      <img
        src={desktopFallbackSrc}
        srcSet={desktopFallbackSrcSet}
        sizes={sizes}
        alt={alt}
        width={width ?? desktopEntry?.width}
        height={height ?? desktopEntry?.height}
        loading={normalizedDesktopLoading}
        fetchPriority={normalizedFetchPriority}
        decoding={decoding ?? "async"}
        {...imgProps}
      />
    </picture>
  );
}
