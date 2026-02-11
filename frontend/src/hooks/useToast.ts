import { useState, useCallback } from "react";

export interface Toast {
  id: string;
  type: "success" | "error" | "warning" | "info";
  message: string;
  description?: string;
  duration?: number;
}

export function useToast() {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const removeToast = useCallback((id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  const addToast = useCallback(
    (toast: Omit<Toast, "id">) => {
      const id = Math.random().toString(36).substr(2, 9);
      const newToast: Toast = { ...toast, id };

      setToasts((prev) => [...prev, newToast]);

      if (toast.duration !== 0) {
        const duration = toast.duration ?? 5000;
        setTimeout(() => {
          removeToast(id);
        }, duration);
      }

      return id;
    },
    [removeToast],
  );

  const success = useCallback(
    (message: string, description?: string) => {
      return addToast({
        type: "success",
        message,
        description,
        duration: 5000,
      });
    },
    [addToast],
  );

  const error = useCallback(
    (message: string, description?: string) => {
      return addToast({
        type: "error",
        message,
        description,
        duration: 0,
      });
    },
    [addToast],
  );

  const warning = useCallback(
    (message: string, description?: string) => {
      return addToast({
        type: "warning",
        message,
        description,
        duration: 5000,
      });
    },
    [addToast],
  );

  const info = useCallback(
    (message: string, description?: string) => {
      return addToast({
        type: "info",
        message,
        description,
        duration: 5000,
      });
    },
    [addToast],
  );

  return {
    toasts,
    addToast,
    removeToast,
    success,
    error,
    warning,
    info,
  };
}
