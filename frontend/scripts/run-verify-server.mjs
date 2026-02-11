import { createServer } from "node:http";
import { existsSync } from "node:fs";
import { promises as fs } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const distDir = path.resolve(__dirname, "../dist");
const apiOrigin = "https://cursosintelecto.com.br";
const port = Number(process.env.VERIFY_PORT || 4173);

const mimeTypes = {
  ".html": "text/html; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".svg": "image/svg+xml",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".webp": "image/webp",
  ".avif": "image/avif",
  ".ico": "image/x-icon",
  ".woff": "font/woff",
  ".woff2": "font/woff2",
  ".webmanifest": "application/manifest+json",
};

async function sendFile(filePath, response) {
  const ext = path.extname(filePath).toLowerCase();
  const contentType = mimeTypes[ext] || "application/octet-stream";
  const content = await fs.readFile(filePath);

  response.statusCode = 200;
  response.setHeader("Content-Type", contentType);
  response.end(content);
}

async function serveStatic(requestPath, response) {
  const decodedPath = decodeURIComponent(requestPath);
  const cleanPath = decodedPath.replace(/^\/+/, "");
  const localFile = path.join(distDir, cleanPath);

  if (existsSync(localFile)) {
    const stat = await fs.stat(localFile);
    if (stat.isFile()) {
      await sendFile(localFile, response);
      return;
    }
  }

  await sendFile(path.join(distDir, "index.html"), response);
}

async function proxyApi(request, response, requestUrl) {
  const targetUrl = new URL(requestUrl.pathname + requestUrl.search, apiOrigin);
  const proxied = await fetch(targetUrl, {
    method: request.method || "GET",
    headers: {
      accept: request.headers.accept || "*/*",
      "user-agent": request.headers["user-agent"] || "verify-server",
    },
  });

  const body = Buffer.from(await proxied.arrayBuffer());

  response.statusCode = proxied.status;
  response.setHeader(
    "Content-Type",
    proxied.headers.get("content-type") || "application/json; charset=utf-8",
  );
  response.end(body);
}

const server = createServer(async (request, response) => {
  try {
    const requestUrl = new URL(request.url || "/", `http://127.0.0.1:${port}`);

    if (requestUrl.pathname.startsWith("/api/")) {
      await proxyApi(request, response, requestUrl);
      return;
    }

    await serveStatic(requestUrl.pathname, response);
  } catch (error) {
    response.statusCode = 500;
    response.end(String(error));
  }
});

server.listen(port, "127.0.0.1", () => {
  console.log(`Verify server running at http://127.0.0.1:${port}`);
});
