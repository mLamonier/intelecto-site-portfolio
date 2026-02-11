export function whatsAppUrl(): string {
  const raw = (import.meta.env.VITE_WHATSAPP_NUMBER ?? "").toString();
  const number = raw.replace(/\D/g, "");
  const message =
    (import.meta.env.VITE_WHATSAPP_MESSAGE as string) ??
    "Olá! Quero saber mais sobre os cursos.";
  const base = "https://wa.me/5535998421176";
  if (number) {
    return `${base}${number}?text=${encodeURIComponent(message)}`;
  }
  return `${base}?text=${encodeURIComponent(message)}`;
}

const LOCAL_BASE_PATH = "/intelecto-site";

function isLocalhost(): boolean {
  if (typeof window === "undefined") {
    return import.meta.env.DEV;
  }
  const host = window.location.hostname.toLowerCase();
  return host === "localhost" || host === "127.0.0.1" || host === "::1";
}

export function siteBasePath(): string {
  return isLocalhost() ? LOCAL_BASE_PATH : "";
}

export function siteBaseUrl(): string {
  if (isLocalhost()) {
    return `http://localhost${LOCAL_BASE_PATH}`;
  }
  return "";
}

export function sitePath(path?: string | null): string {
  const clean = (path ?? "").replace(/^\/+/, "");
  if (clean === "") {
    return isLocalhost() ? `${siteBaseUrl()}/` : "/";
  }
  if (isLocalhost()) {
    return `${siteBaseUrl()}/${clean}`;
  }
  return `/${clean}`;
}

export function apiBaseUrl(): string {
  if (isLocalhost()) {
    return "/api";
  }
  return sitePath("api");
}

export function adminLoginUrl(): string {
  return sitePath("login.php");
}

export function adminDashboardUrl(): string {
  return sitePath("admin/index.php");
}
