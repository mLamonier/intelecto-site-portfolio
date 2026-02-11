import { useState, useEffect, useMemo } from "react";
import "./TestimonialsSection.css";
import type { Testimonial } from "../../services/homepage";
import { homepageService } from "../../services/homepage";
import responsiveImageManifest from "../../generated/responsiveImageManifest";
import { resolveAssetUrl, resolveHomepageImage } from "../../utils/assetUrl";
import ResponsiveImage from "../Media/ResponsiveImage";

function isVideoTestimonial(path?: string | null): boolean {
  if (!path) return false;
  return /\.(mp4|webm|ogg)(\?.*)?$/i.test(path.trim());
}

interface TestimonialWithPoster extends Testimonial {
  thumbnail?: string | null;
  poster?: string | null;
}

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

interface VideoPosterConfig {
  poster: string;
  width: number;
  height: number;
  srcSet: string;
}

const manifest = responsiveImageManifest as Record<string, ManifestEntry>;
const canonicalManifest = new Map<string, ManifestEntry>(
  Object.entries(manifest).map(([key, value]) => [canonicalizePath(key), value]),
);

function canonicalizePath(input: string): string {
  return input
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .normalize("NFC");
}

function normalizeManifestPath(input: string): string {
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
    .replace(/^\/frontend\/public/, "")
    .replace(/^frontend\/public\//, "/")
    .replace(/^assets\//, "/assets/");

  if (!pathname.startsWith("/")) {
    pathname = `/${pathname}`;
  }

  return pathname;
}

function getManifestEntry(path: string): ManifestEntry | undefined {
  const normalizedPath = normalizeManifestPath(path);
  if (manifest[normalizedPath]) {
    return manifest[normalizedPath];
  }
  return canonicalManifest.get(canonicalizePath(normalizedPath));
}

function toPosterSrcSet(variants: VariantSource[]): string {
  return variants
    .map((variant) => `${resolveAssetUrl(variant.src)} ${variant.width}w`)
    .join(", ");
}

function chooseVariant(
  variants: VariantSource[],
  targetWidth: number,
): VariantSource | undefined {
  if (!variants.length) return undefined;

  const sorted = [...variants].sort((a, b) => a.width - b.width);
  const equalOrBigger = sorted.find((variant) => variant.width >= targetWidth);
  return equalOrBigger ?? sorted[sorted.length - 1];
}

function supportsAvifPoster(): boolean {
  try {
    const canvas = document.createElement("canvas");
    const dataUrl = canvas.toDataURL("image/avif");
    return dataUrl.startsWith("data:image/avif");
  } catch {
    return false;
  }
}

function toVideoPosterPath(videoPath: string): string {
  const normalized = normalizeManifestPath(videoPath);
  const rawName = normalized
    .split("/")
    .pop()
    ?.replace(/\.[a-z0-9]+$/i, "")
    .trim();

  const slug = (rawName ?? "depoimento")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");

  return `/assets/testimonials/video-${slug || "depoimento"}.jpg`;
}

function resolveVideoPoster(
  sourcePath: string,
  targetWidth: number,
  preferAvif: boolean,
): VideoPosterConfig {
  const entry = getManifestEntry(sourcePath);

  if (!entry) {
    return {
      poster: resolveHomepageImage(sourcePath),
      width: 1080,
      height: 1920,
      srcSet: "",
    };
  }

  const preferredVariants =
    preferAvif && entry.sources.avif.length > 0
      ? entry.sources.avif
      : entry.sources.webp.length > 0
        ? entry.sources.webp
        : entry.sources.fallback;

  const chosenVariant =
    chooseVariant(preferredVariants, targetWidth) ??
    chooseVariant(entry.sources.fallback, targetWidth);

  return {
    poster: resolveAssetUrl(chosenVariant?.src ?? sourcePath),
    width: entry.width,
    height: entry.height,
    srcSet: toPosterSrcSet(preferredVariants),
  };
}

export default function TestimonialsSection() {
  const [testimonials, setTestimonials] = useState<Testimonial[]>([]);
  const [index, setIndex] = useState(0);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchTestimonials = async () => {
      setLoading(true);
      const data = await homepageService.getTestimonials();
      if (data && data.length > 0) {
        setTestimonials(data);
      }
      setLoading(false);
    };
    fetchTestimonials();
  }, []);

  const prev = () =>
    setIndex((i) => (i - 1 + testimonials.length) % testimonials.length);
  const next = () => setIndex((i) => (i + 1) % testimonials.length);
  const currentTestimonial = testimonials[index];
  const testimonialWithPoster = currentTestimonial as
    | TestimonialWithPoster
    | undefined;
  const mediaPath = currentTestimonial?.foto || "";
  const isVideo = isVideoTestimonial(mediaPath);
  const videoPoster = useMemo(() => {
    if (!isVideo) return null;

    const targetPosterWidth =
      typeof window !== "undefined" && window.matchMedia("(max-width: 768px)").matches
        ? 640
        : 1280;

    const explicitPoster =
      testimonialWithPoster?.thumbnail || testimonialWithPoster?.poster;
    const posterSource =
      explicitPoster && explicitPoster.trim() !== ""
        ? explicitPoster
        : toVideoPosterPath(mediaPath);

    return resolveVideoPoster(
      posterSource,
      targetPosterWidth,
      supportsAvifPoster(),
    );
  }, [
    isVideo,
    mediaPath,
    testimonialWithPoster?.poster,
    testimonialWithPoster?.thumbnail,
  ]);

  if (loading || !Array.isArray(testimonials) || testimonials.length === 0) {
    return (
      <section
        className="testimonials-section"
        id="depoimentos"
        style={{ minHeight: "400px" }}
      />
    );
  }

  return (
    <section className="testimonials-section" id="depoimentos">
      <h2 className="section-title">Depoimentos</h2>
      <div className="testimonials-container">
        <button
          className="testimonial-arrow testimonial-arrow-left"
          onClick={prev}
          aria-label="Anterior">
          {"<"}
        </button>

        {isVideo ? (
          <div className="testimonial-card testimonial-card-video" key={index}>
            <video
              controls
              preload="none"
              playsInline
              poster={videoPoster?.poster}
              width={videoPoster?.width ?? 1080}
              height={videoPoster?.height ?? 1920}
              data-testid="testimonial-video"
              data-poster-srcset={videoPoster?.srcSet ?? ""}>
              <source src={resolveHomepageImage(mediaPath)} type="video/mp4" />
              Seu navegador não suporta vídeo HTML5.
            </video>
          </div>
        ) : (
          <div className="testimonial-card" key={index}>
            <div className="testimonial-photo">
              <ResponsiveImage
                src={resolveHomepageImage(mediaPath)}
                alt={currentTestimonial?.nome_aluno || ""}
                sizes="(max-width: 768px) 100vw, 240px"
              />
            </div>
            <div className="testimonial-content">
              <h3 className="testimonial-name">
                {currentTestimonial?.nome_aluno?.toUpperCase() || ""}
              </h3>
              <div className="testimonial-divider"></div>
              <p className="testimonial-course">{currentTestimonial?.curso || ""}</p>
              <p className="testimonial-text">
                "{currentTestimonial?.mensagem || ""}"
              </p>
            </div>
          </div>
        )}

        <button
          className="testimonial-arrow testimonial-arrow-right"
          onClick={next}
          aria-label="Próximo">
          {">"}
        </button>
      </div>
      <div className="testimonial-controls">
        <div className="testimonial-dots">
          {testimonials.map((_, idx) => (
            <button
              key={idx}
              className={`dot ${idx === index ? "active" : ""}`}
              onClick={() => setIndex(idx)}
              aria-label={`Ir para depoimento ${idx + 1}`}
            />
          ))}
        </div>
      </div>
    </section>
  );
}
