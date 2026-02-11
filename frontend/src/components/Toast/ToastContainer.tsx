import type { Toast } from "../../hooks/useToast";
import "../../styles/toast.css";

interface ToastContainerProps {
  toasts: Toast[];
  onRemove: (id: string) => void;
}

const iconMap = {
  success: "✓",
  error: "✕",
  warning: "⚠",
  info: "ℹ",
};

export function ToastContainer({ toasts, onRemove }: ToastContainerProps) {
  return (
    <div className="toast-container">
      {toasts.map((toast) => (
        <div key={toast.id} className={`toast ${toast.type}`}>
          <span className="toast-icon">{iconMap[toast.type]}</span>
          <div className="toast-message">
            <div>{toast.message}</div>
            {toast.description && (
              <div style={{ fontSize: "12px", opacity: 0.8 }}>
                {toast.description}
              </div>
            )}
          </div>
          <button
            className="toast-close"
            onClick={() => onRemove(toast.id)}
            aria-label="Fechar notificação">
            ×
          </button>
        </div>
      ))}
    </div>
  );
}
