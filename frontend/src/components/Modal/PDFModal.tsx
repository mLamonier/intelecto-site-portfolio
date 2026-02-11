import { useEffect, useRef, useState } from "react";
import { X } from "lucide-react";
import { Document, Page, pdfjs } from "react-pdf";
import "./Modal.css";
import { resolveAssetUrl } from "../../utils/assetUrl";

pdfjs.GlobalWorkerOptions.workerSrc = `https://cdn.jsdelivr.net/npm/pdfjs-dist@${pdfjs.version}/build/pdf.worker.min.mjs`;

interface PDFModalProps {
  isOpen: boolean;
  onClose: () => void;
  pdfUrl: string;
  title?: string;
}

export default function PDFModal({
  isOpen,
  onClose,
  pdfUrl,
  title = "Conteudo Programatico",
}: PDFModalProps) {
  const [numPages, setNumPages] = useState<number | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [pdfLoading, setPdfLoading] = useState(false);
  const [pageWidth, setPageWidth] = useState(280);
  const containerRef = useRef<HTMLDivElement | null>(null);
  const fullPdfUrl = resolveAssetUrl(pdfUrl);

  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "unset";
    }

    return () => {
      document.body.style.overflow = "unset";
    };
  }, [isOpen]);

  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        onClose();
      }
    };

    if (isOpen) {
      window.addEventListener("keydown", handleEscape);
    }

    return () => {
      window.removeEventListener("keydown", handleEscape);
    };
  }, [isOpen, onClose]);

  useEffect(() => {
    if (!isOpen) return;

    const element = containerRef.current;
    if (!element) return;

    const updateWidth = () => {
      const style = window.getComputedStyle(element);
      const paddingLeft = parseFloat(style.paddingLeft || "0");
      const paddingRight = parseFloat(style.paddingRight || "0");
      const usableWidth = Math.floor(
        element.clientWidth - paddingLeft - paddingRight - 2,
      );
      setPageWidth(Math.max(usableWidth, 240));
    };

    updateWidth();
    const observer = new ResizeObserver(updateWidth);
    observer.observe(element);

    return () => observer.disconnect();
  }, [isOpen]);

  useEffect(() => {
    if (isOpen) {
      setNumPages(null);
      setLoadError(null);
    }
  }, [isOpen, pdfUrl]);

  useEffect(() => {
    if (!isOpen) return;
    if (!fullPdfUrl) {
      setLoadError("PDF indisponivel.");
      setPdfLoading(false);
    }
  }, [isOpen, fullPdfUrl]);

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div
        className="modal-content pdf-modal"
        onClick={(e) => e.stopPropagation()}>
        <div className="modal-header-minimal">
          <h3>{title}</h3>
          <button className="modal-close" onClick={onClose} aria-label="Fechar">
            <X size={24} />
          </button>
        </div>
        <div className="modal-body">
          <div className="pdf-container" ref={containerRef}>
            {pdfLoading && <div className="pdf-loading">Carregando PDF...</div>}
            {loadError && !pdfLoading && (
              <div className="pdf-error">{loadError}</div>
            )}
            {!loadError && fullPdfUrl && (
              <Document
                file={fullPdfUrl}
                key={fullPdfUrl}
                options={{
                  disableRange: true,
                  disableStream: true,
                }}
                onLoadStart={() => {
                  setPdfLoading(true);
                  setLoadError(null);
                }}
                onLoadSuccess={({ numPages: total }) => {
                  setNumPages(total);
                  setLoadError(null);
                  setPdfLoading(false);
                }}
                onLoadError={(error) => {
                  console.error("PDF load error:", error);
                  setLoadError("Nao foi possivel carregar o PDF.");
                  setPdfLoading(false);
                }}
                onSourceError={(error) => {
                  console.error("PDF source error:", error);
                  setLoadError("Nao foi possivel carregar o PDF.");
                  setPdfLoading(false);
                }}
                loading={<div className="pdf-loading">Carregando PDF...</div>}
                error={<div className="pdf-error">Falha ao carregar o PDF.</div>}
                className="pdf-document">
                {numPages &&
                  Array.from({ length: numPages }, (_, index) => (
                    <Page
                      key={`page_${index + 1}`}
                      pageNumber={index + 1}
                      width={pageWidth}
                      renderAnnotationLayer={false}
                      renderTextLayer={false}
                      loading={
                        <div className="pdf-page-loading">
                          Carregando pagina...
                        </div>
                      }
                    />
                  ))}
              </Document>
            )}
          </div>
          <div className="pdf-fallback">
            <a
              className="pdf-fallback-link"
              href={fullPdfUrl}
              target="_blank"
              rel="noreferrer">
              Abrir em outra aba/baixar
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
