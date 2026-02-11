import { useEffect, useRef, useState } from "react";

interface DeferredClientEnhancementOptions {
  rootMargin?: string;
  idleDelayMs?: number;
}

type WindowWithIdleCallback = Window & {
  requestIdleCallback?: (
    callback: IdleRequestCallback,
    options?: IdleRequestOptions,
  ) => number;
  cancelIdleCallback?: (handle: number) => void;
};

const DEFAULT_ROOT_MARGIN = "200px";
const DEFAULT_IDLE_DELAY_MS = 300;

export function useDeferredClientEnhancement<T extends Element>({
  rootMargin = DEFAULT_ROOT_MARGIN,
  idleDelayMs = DEFAULT_IDLE_DELAY_MS,
}: DeferredClientEnhancementOptions = {}) {
  const elementRef = useRef<T | null>(null);
  const [shouldEnhance, setShouldEnhance] = useState(false);

  useEffect(() => {
    if (shouldEnhance) {
      return;
    }

    let timeoutId: number | null = null;
    let idleCallbackId: number | null = null;
    let observer: IntersectionObserver | null = null;

    const win = window as WindowWithIdleCallback;

    const enableEnhancement = () => {
      timeoutId = window.setTimeout(() => {
        setShouldEnhance(true);
      }, idleDelayMs);
    };

    const scheduleEnhancement = () => {
      if (typeof win.requestIdleCallback === "function") {
        idleCallbackId = win.requestIdleCallback(
          () => {
            enableEnhancement();
          },
          { timeout: 2000 },
        );
        return;
      }
      enableEnhancement();
    };

    if (typeof IntersectionObserver !== "function") {
      scheduleEnhancement();
    } else if (elementRef.current) {
      observer = new IntersectionObserver(
        (entries) => {
          const isVisible = entries.some((entry) => entry.isIntersecting);
          if (!isVisible) return;

          observer?.disconnect();
          observer = null;
          scheduleEnhancement();
        },
        { rootMargin },
      );
      observer.observe(elementRef.current);
    } else {
      scheduleEnhancement();
    }

    return () => {
      if (observer) {
        observer.disconnect();
      }
      if (timeoutId !== null) {
        window.clearTimeout(timeoutId);
      }
      if (
        idleCallbackId !== null &&
        typeof win.cancelIdleCallback === "function"
      ) {
        win.cancelIdleCallback(idleCallbackId);
      }
    };
  }, [idleDelayMs, rootMargin, shouldEnhance]);

  return { elementRef, shouldEnhance };
}
