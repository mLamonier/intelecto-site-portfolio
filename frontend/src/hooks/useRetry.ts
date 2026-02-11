import { useState } from "react";

interface RetryConfig {
  maxAttempts: number;
  delayMs: number;
  backoffMultiplier: number;
}

interface RetryState {
  attempts: number;
  isRetrying: boolean;
  lastError: Error | null;
  nextRetryIn: number;
}

const DEFAULT_CONFIG: RetryConfig = {
  maxAttempts: 3,
  delayMs: 2000,
  backoffMultiplier: 2,
};

export function useRetry(config: Partial<RetryConfig> = {}) {
  const finalConfig = { ...DEFAULT_CONFIG, ...config };
  const [state, setState] = useState<RetryState>({
    attempts: 0,
    isRetrying: false,
    lastError: null,
    nextRetryIn: 0,
  });

  const retry = async <T>(
    fn: () => Promise<T>,
    onRetry?: (attempt: number, nextIn: number) => void,
    shouldRetry?: (error: unknown, attempt: number) => boolean,
  ): Promise<T> => {
    let lastError: Error | null = null;

    for (let attempt = 1; attempt <= finalConfig.maxAttempts; attempt++) {
      try {
        setState({
          attempts: attempt,
          isRetrying: attempt > 1,
          lastError: null,
          nextRetryIn: 0,
        });

        return await fn();
      } catch (error) {
        lastError = error instanceof Error ? error : new Error(String(error));

        const retryAllowed = shouldRetry ? shouldRetry(error, attempt) : true;

        if (attempt < finalConfig.maxAttempts && retryAllowed) {
          const delay =
            finalConfig.delayMs *
            Math.pow(finalConfig.backoffMultiplier, attempt - 1);

          if (onRetry) {
            onRetry(attempt, delay);
          }

          setState({
            attempts: attempt,
            isRetrying: true,
            lastError,
            nextRetryIn: delay,
          });

          await new Promise((resolve) => setTimeout(resolve, delay));
        } else {
          break;
        }
      }
    }

    setState({
      attempts: finalConfig.maxAttempts,
      isRetrying: false,
      lastError,
      nextRetryIn: 0,
    });

    throw lastError;
  };

  const reset = () => {
    setState({
      attempts: 0,
      isRetrying: false,
      lastError: null,
      nextRetryIn: 0,
    });
  };

  return { ...state, retry, reset };
}
