import { apiBaseUrl } from "../services/site";

declare global {
  interface Window {
    PagSeguro?: {
      encryptCard: (cardData: {
        publicKey: string;
        holder: string;
        number: string;
        expMonth: string;
        expYear: string;
        securityCode: string;
      }) => Promise<{
        encryptedCard: string;
        hasErrors: boolean;
        errors: Array<{ message?: string }>;
      }>;
    };
  }
}

let cachedPublicKey: string | null = null;
let pagSeguroScriptPromise: Promise<void> | null = null;

async function ensurePagSeguroScript(): Promise<void> {
  if (window.PagSeguro) {
    return;
  }

  if (pagSeguroScriptPromise) {
    return pagSeguroScriptPromise;
  }

  pagSeguroScriptPromise = new Promise<void>((resolve, reject) => {
    const existingScript = document.querySelector<HTMLScriptElement>(
      "script[data-pagseguro-sdk='true']",
    );

    if (existingScript) {
      existingScript.addEventListener("load", () => resolve(), { once: true });
      existingScript.addEventListener(
        "error",
        () => reject(new Error("Nao foi possivel carregar o SDK do PagBank")),
        { once: true },
      );
      return;
    }

    const script = document.createElement("script");
    script.src =
      "https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js";
    script.async = true;
    script.defer = true;
    script.dataset.pagseguroSdk = "true";
    script.addEventListener("load", () => resolve(), { once: true });
    script.addEventListener(
      "error",
      () => reject(new Error("Nao foi possivel carregar o SDK do PagBank")),
      { once: true },
    );

    document.head.appendChild(script);
  }).finally(() => {
    if (!window.PagSeguro) {
      pagSeguroScriptPromise = null;
    }
  });

  return pagSeguroScriptPromise;
}

async function getPublicKey(): Promise<string> {
  if (cachedPublicKey) {
    return cachedPublicKey;
  }

  const envKey = import.meta.env.VITE_PAGBANK_PUBLIC_KEY;
  if (envKey) {
    cachedPublicKey = envKey;
    return envKey;
  }

  try {
    const response = await fetch(`${apiBaseUrl()}/pagbank/public-key.php`);
    const data = await response.json();

    if (data.success && data.public_key) {
      cachedPublicKey = data.public_key;
      return data.public_key;
    }
  } catch {
    void 0;
  }

  throw new Error("Chave publica do PagBank nao configurada");
}

export async function encryptCard(cardData: {
  cardNumber: string;
  cardHolder: string;
  expiryMonth: string;
  expiryYear: string;
  cvv: string;
}): Promise<string> {
  await ensurePagSeguroScript();

  if (!window.PagSeguro) {
    throw new Error("PagBank SDK nao foi carregado");
  }

  const publicKey = await getPublicKey();

  const sanitizedHolder = cardData.cardHolder
    .trim()
    .replace(/[^a-zA-Z\s]/g, "")
    .replace(/\s+/g, " ")
    .toUpperCase();

  const result = await window.PagSeguro.encryptCard({
    publicKey,
    holder: sanitizedHolder,
    number: cardData.cardNumber.replace(/\s/g, ""),
    expMonth: cardData.expiryMonth,
    expYear: cardData.expiryYear,
    securityCode: cardData.cvv,
  });

  if (result.hasErrors) {
    const errorMessage =
      result.errors?.[0]?.message || "Erro ao criptografar cartao";
    throw new Error(errorMessage);
  }

  return result.encryptedCard;
}
