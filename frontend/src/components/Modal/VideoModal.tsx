import { useEffect, useRef } from "react";
import { X, RotateCw } from "lucide-react";
import "./Modal.css";

interface VideoModalProps {
  isOpen: boolean;
  onClose: () => void;
  videoUrl: string;
  title?: string;
}

export default function VideoModal({
  isOpen,
  onClose,
  videoUrl,
  title = "Aula Demonstrativa",
}: VideoModalProps) {
  const landscapeNoticeRef = useRef<HTMLDivElement>(null);
  const hasShownNoticeRef = useRef(false);

  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = "hidden";

      
      if (
        window.innerWidth < 1024 &&
        !hasShownNoticeRef.current &&
        landscapeNoticeRef.current
      ) {
        hasShownNoticeRef.current = true;

        
        landscapeNoticeRef.current.style.display = "flex";

        
        setTimeout(() => {
          if (landscapeNoticeRef.current) {
            landscapeNoticeRef.current.style.display = "none";
          }
        }, 3000);
      }
    } else {
      document.body.style.overflow = "unset";
      hasShownNoticeRef.current = false;

      
      if (landscapeNoticeRef.current) {
        landscapeNoticeRef.current.style.display = "none";
      }
    }
  }, [isOpen]);

  
  useEffect(() => {
    return () => {
      document.body.style.overflow = "unset";
    };
  }, []);

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

  if (!isOpen) return null;

  
  const getYouTubeEmbedUrl = (url: string) => {
    const regExp =
      /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    const videoId = match && match[2].length === 11 ? match[2] : null;

    if (videoId) {
      return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    }

    
    return url;
  };

  const embedUrl = getYouTubeEmbedUrl(videoUrl);

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div
        ref={landscapeNoticeRef}
        className="landscape-notice"
        style={{ display: "none" }}>
        <RotateCw size={32} className="rotate-icon" />
        <p>Gire seu dispositivo para melhor experiência</p>
      </div>
      <div
        className="modal-content video-modal"
        onClick={(e) => e.stopPropagation()}>
        <div className="modal-header-minimal">
          <h3>{title}</h3>
          <button className="modal-close" onClick={onClose} aria-label="Fechar">
            <X size={24} />
          </button>
        </div>
        <div className="modal-body">
          <div className="video-container">
            <iframe
              src={embedUrl}
              title={title}
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
            />
          </div>
        </div>
      </div>
    </div>
  );
}
