

interface CardData {
  cardNumber: string;
  cardHolder: string;
  expiryMonth: string;
  expiryYear: string;
  cvv: string;
}

interface PagBankResponse {
  senderHash: string;
  cardToken: string;
  lastFourDigits: string;
  cardBrand: string;
}

type PagBankSdk = {
  getSenderHash: () => Promise<string>;
  createCardToken: (params: {
    publicKey: string | undefined;
    number: string;
    holder: string;
    expiryMonth: number;
    expiryYear: number;
    securityCode: string;
  }) => Promise<{
    success: boolean;
    error?: { message?: string };
    token: string;
    lastFourDigits: string;
    brand: string;
  }>;
};

type WindowWithPagBank = Window & {
  PagBank?: PagBankSdk;
};

export async function generatePagBankTokens(
  cardData: CardData
): Promise<PagBankResponse> {
  const win = window as WindowWithPagBank;
  if (typeof window === "undefined" || !win.PagBank) {
    throw new Error("SDK do PagBank não foi carregado");
  }

  const pagBank = win.PagBank;
  const senderHash = await pagBank.getSenderHash();
  const cardToken = await pagBank.createCardToken({
    publicKey: process.env.REACT_APP_PAGBANK_PUBLIC_KEY,
    number: cardData.cardNumber,
    holder: cardData.cardHolder,
    expiryMonth: parseInt(cardData.expiryMonth),
    expiryYear: parseInt("20" + cardData.expiryYear),
    securityCode: cardData.cvv,
  });

  if (!cardToken.success) {
    throw new Error(cardToken.error?.message || "Erro ao tokenizar cartão");
  }

  return {
    senderHash,
    cardToken: cardToken.token,
    lastFourDigits: cardToken.lastFourDigits,
    cardBrand: cardToken.brand,
  };
}

export async function monitorPaymentStatus(
  idPagamento: number,
  maxAttempts: number = 30,
  intervalMs: number = 2000
): Promise<{ status: string; pedidoStatus: string }> {
  let attempts = 0;

  return new Promise((resolve, reject) => {
    const interval = setInterval(async () => {
      attempts++;

      try {
        const response = await fetch(
          `${process.env.REACT_APP_API_URL}/pagamentos/${idPagamento}`
        );
        const data = await response.json();

        if (data.status === "APROVADO") {
          clearInterval(interval);
          resolve(data);
        } else if (data.status === "RECUSADO") {
          clearInterval(interval);
          reject(new Error("Pagamento foi recusado"));
        }

        if (attempts >= maxAttempts) {
          clearInterval(interval);
          reject(new Error("Timeout aguardando confirmação do pagamento"));
        }
      } catch (error) {
        if (attempts >= maxAttempts) {
          clearInterval(interval);
          reject(error);
        }
      }
    }, intervalMs);
  });
}

export function formatCardNumber(value: string): string {
  return value
    .replace(/\s/g, "")
    .replace(/(\d{4})/g, "$1 ")
    .trim();
}

export function formatCVV(value: string): string {
  return value.replace(/\D/g, "").slice(0, 4);
}

export function getCardBrand(number: string): string {
  const cleanNumber = number.replace(/\D/g, "");

  if (/^4[0-9]/.test(cleanNumber)) return "VISA";
  if (/^5[1-5]/.test(cleanNumber)) return "MASTERCARD";
  if (/^3[47]/.test(cleanNumber)) return "AMEX";
  if (/^6(?:011|5)/.test(cleanNumber)) return "DISCOVER";
  if (/^36/.test(cleanNumber)) return "DINERS";
  if (/^(?:2131|1800|35)/.test(cleanNumber)) return "JCB";
  if (/^63[0-9]/.test(cleanNumber)) return "ELO";

  return "UNKNOWN";
}

export function validateCardNumber(cardNumber: string): boolean {
  const cleanNumber = cardNumber.replace(/\D/g, "");

  if (!/^[0-9]{13,19}$/.test(cleanNumber)) {
    return false;
  }

  
  let sum = 0;
  let isEven = false;

  for (let i = cleanNumber.length - 1; i >= 0; i--) {
    let digit = parseInt(cleanNumber[i], 10);

    if (isEven) {
      digit *= 2;
      if (digit > 9) {
        digit -= 9;
      }
    }

    sum += digit;
    isEven = !isEven;
  }

  return sum % 10 === 0;
}

export function validateExpiry(month: string, year: string): boolean {
  const now = new Date();
  const currentYear = now.getFullYear();
  const currentMonth = now.getMonth() + 1;

  const expiryYear = parseInt("20" + year);
  const expiryMonth = parseInt(month);

  if (expiryYear < currentYear) {
    return false;
  }

  if (expiryYear === currentYear && expiryMonth < currentMonth) {
    return false;
  }

  return true;
}

export function getErrorMessage(errorCode: string): string {
  const messages: Record<string, string> = {
    CARD_DECLINED: "Cartão foi recusado. Tente outro cartão.",
    INVALID_CARD: "Número do cartão inválido.",
    EXPIRED_CARD: "Cartão expirado.",
    INSUFFICIENT_FUNDS: "Saldo insuficiente.",
    PROCESSING_ERROR: "Erro ao processar pagamento. Tente novamente.",
    NETWORK_ERROR: "Erro de conexão. Verifique sua internet.",
    TIMEOUT: "Tempo limite de espera excedido. Tente novamente.",
  };

  return messages[errorCode] || "Erro ao processar pagamento.";
}
