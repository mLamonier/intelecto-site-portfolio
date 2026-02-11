import { createHash } from "node:crypto";
import { promises as fs } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const frontendRoot = path.resolve(__dirname, "..");
const publicAssetsDir = path.join(frontendRoot, "public", "assets");
const manifestFilePath = path.join(
  frontendRoot,
  "src",
  "generated",
  "responsiveImageManifest.ts",
);

const SOURCE_EXTENSIONS = new Set([".jpg", ".jpeg", ".png"]);
const TARGET_WIDTHS = [320, 480, 768, 1024, 1280, 1600];

const FORMAT_OPTIONS = {
  avif: { extension: "avif", outputFormat: "avif", options: { quality: 45 } },
  webp: { extension: "webp", outputFormat: "webp", options: { quality: 72 } },
};

function toPosixPath(value) {
  return value.split(path.sep).join("/");
}

function slugifySegment(segment) {
  const slug = segment
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-zA-Z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .toLowerCase();

  return slug || "asset";
}

function getOriginalFormatConfig(extension) {
  if (extension === ".png") {
    return {
      extension: "png",
      outputFormat: "png",
      options: { compressionLevel: 9 },
    };
  }

  return {
    extension: "jpg",
    outputFormat: "jpeg",
    options: { quality: 74, mozjpeg: true },
  };
}

function getTargetWidths(sourceWidth) {
  const widths = TARGET_WIDTHS.filter((width) => width <= sourceWidth);

  if (widths.length === 0 || widths[widths.length - 1] !== sourceWidth) {
    widths.push(sourceWidth);
  }

  return [...new Set(widths)].sort((a, b) => a - b);
}

async function ensureDirectory(directoryPath) {
  await fs.mkdir(directoryPath, { recursive: true });
}

async function walkDirectory(directoryPath) {
  const entries = await fs.readdir(directoryPath, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = path.join(directoryPath, entry.name);

    if (entry.isDirectory()) {
      if (entry.name === "responsive") {
        continue;
      }
      const nestedFiles = await walkDirectory(fullPath);
      files.push(...nestedFiles);
      continue;
    }

    const extension = path.extname(entry.name).toLowerCase();
    if (SOURCE_EXTENSIONS.has(extension)) {
      files.push(fullPath);
    }
  }

  return files;
}

async function shouldRegenerate(sourcePath, outputPath) {
  try {
    const [sourceStats, outputStats] = await Promise.all([
      fs.stat(sourcePath),
      fs.stat(outputPath),
    ]);
    return outputStats.mtimeMs < sourceStats.mtimeMs;
  } catch {
    return true;
  }
}

async function generateVariant({
  sourcePath,
  outputPath,
  width,
  outputFormat,
  options,
}) {
  const regenerate = await shouldRegenerate(sourcePath, outputPath);
  if (!regenerate) {
    return;
  }

  await sharp(sourcePath)
    .resize({ width, withoutEnlargement: true })
    .toFormat(outputFormat, options)
    .toFile(outputPath);
}

function createVariantRelativePath({ relativeInputPath, width, extension }) {
  const parsed = path.parse(relativeInputPath);
  const safeDirectorySegments = parsed.dir
    ? parsed.dir.split("/").map(slugifySegment)
    : [];
  const safeBaseName = slugifySegment(parsed.name);
  const fingerprint = createHash("sha1")
    .update(relativeInputPath)
    .digest("hex")
    .slice(0, 10);

  const safeRelativeDirectory = safeDirectorySegments.join("/");
  const fileName = `${safeBaseName}-${fingerprint}-w${width}.${extension}`;

  return safeRelativeDirectory
    ? `responsive/${safeRelativeDirectory}/${fileName}`
    : `responsive/${fileName}`;
}

async function main() {
  await ensureDirectory(path.dirname(manifestFilePath));

  const sourceFiles = await walkDirectory(publicAssetsDir);
  const manifest = {};

  let generatedCount = 0;

  for (const sourceFilePath of sourceFiles) {
    const relativeInputPath = toPosixPath(
      path.relative(publicAssetsDir, sourceFilePath),
    );

    const metadata = await sharp(sourceFilePath).metadata();
    if (!metadata.width || !metadata.height) {
      continue;
    }

    const sourceWidth = metadata.width;
    const sourceHeight = metadata.height;
    const extension = path.extname(sourceFilePath).toLowerCase();
    const widths = getTargetWidths(sourceWidth);

    const originalFormatConfig = getOriginalFormatConfig(extension);

    const variantsByFormat = {
      avif: [],
      webp: [],
      fallback: [],
    };

    for (const width of widths) {
      for (const formatKey of Object.keys(FORMAT_OPTIONS)) {
        const formatConfig = FORMAT_OPTIONS[formatKey];
        const variantRelativePath = createVariantRelativePath({
          relativeInputPath,
          width,
          extension: formatConfig.extension,
        });
        const outputPath = path.join(publicAssetsDir, variantRelativePath);

        await ensureDirectory(path.dirname(outputPath));
        await generateVariant({
          sourcePath: sourceFilePath,
          outputPath,
          width,
          outputFormat: formatConfig.outputFormat,
          options: formatConfig.options,
        });

        variantsByFormat[formatKey].push({
          src: `/assets/${variantRelativePath}`,
          width,
        });

        generatedCount += 1;
      }

      const fallbackVariantRelativePath = createVariantRelativePath({
        relativeInputPath,
        width,
        extension: originalFormatConfig.extension,
      });
      const fallbackOutputPath = path.join(
        publicAssetsDir,
        fallbackVariantRelativePath,
      );

      await ensureDirectory(path.dirname(fallbackOutputPath));
      await generateVariant({
        sourcePath: sourceFilePath,
        outputPath: fallbackOutputPath,
        width,
        outputFormat: originalFormatConfig.outputFormat,
        options: originalFormatConfig.options,
      });

      variantsByFormat.fallback.push({
        src: `/assets/${fallbackVariantRelativePath}`,
        width,
      });

      generatedCount += 1;
    }

    const originalAssetPath = `/assets/${relativeInputPath}`;
    manifest[originalAssetPath] = {
      width: sourceWidth,
      height: sourceHeight,
      sources: variantsByFormat,
    };
  }

  const sortedManifest = Object.keys(manifest)
    .sort((a, b) => a.localeCompare(b))
    .reduce((accumulator, key) => {
      accumulator[key] = manifest[key];
      return accumulator;
    }, {});

  const fileContents = `/* eslint-disable */\n// This file is auto-generated by scripts/generate-responsive-images.mjs\nconst manifest = ${JSON.stringify(sortedManifest, null, 2)};\n\nexport default manifest;\n`;

  await fs.writeFile(manifestFilePath, fileContents, "utf-8");

  const sourceCount = sourceFiles.length;
  const manifestCount = Object.keys(sortedManifest).length;

  console.log(
    `Responsive images generated for ${manifestCount} source files (${sourceCount} discovered, ${generatedCount} variants).`,
  );
  console.log(`Manifest written to ${manifestFilePath}`);
}

main().catch((error) => {
  console.error("Failed to generate responsive images.");
  console.error(error);
  process.exitCode = 1;
});
