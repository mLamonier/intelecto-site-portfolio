import { useState, useEffect, useMemo } from "react";
import type { ReactNode } from "react";
import { FaCreditCard, FaQrcode, FaBarcode } from "react-icons/fa";
import type { Pedido } from "../../types/pedido";
import "./CheckoutForm.css";

interface CheckoutFormProps {
  pedido: Pedido;
  onPaymentStart: (data: PaymentData) => void;
  isProcessing: boolean;
  initialMethod?: PaymentData["metodo"] | null;
}

export interface PaymentData {
  id_pedido: number;
  metodo: "CARTAO" | "PIX" | "BOLETO";
  senderHash?: string;
  cardToken?: string;
}

interface PaymentStartPayload extends PaymentData {
  cardData?: {
    number: string;
    holder: string;
    exp_month: string;
    exp_year: string;
    security_code: string;
  };
  installments?: number;
}

export default function CheckoutForm({
  pedido,
  onPaymentStart,
  isProcessing,
  initialMethod = null,
}: CheckoutFormProps) {
  const [metodo, setMetodo] = useState<PaymentData["metodo"]>("CARTAO");
  const [parcelas, setParcelas] = useState<number>(1);
  const [cardData, setCardData] = useState({
    cardNumber: "",
    cardHolder: "",
    expiryMonth: "",
    expiryYear: "",
    cvv: "",
  });

  const maxParcelas = Math.min(pedido.meses_duracao || 12, 12);

  const opcoesParcelas = Array.from({ length: maxParcelas }, (_, i) => i + 1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const tipoRaw = (pedido.tipo ?? "").toString().toUpperCase();
  const tipoLabel = tipoRaw.includes("PERSON")
    ? "GRADE PERSONALIZADA"
    : "GRADE PRÉ-MONTADA";
  const isPreMontada = !tipoRaw.includes("PERSON");

  const plano = (pedido.forma_pagamento ?? null) as
    | "MENSAL"
    | "AVISTA"
    | "PARCELADO"
    | null;

  const allowedMethods = useMemo<PaymentData["metodo"][]>(() => {
    if (plano === "PARCELADO") return ["CARTAO"];
    if (plano === "MENSAL" || plano === "AVISTA") {
      return ["PIX", "BOLETO", "CARTAO"];
    }

    return ["PIX", "BOLETO", "CARTAO"];
  }, [plano]);

  useEffect(() => {
    if (initialMethod && allowedMethods.includes(initialMethod)) {
      setMetodo(initialMethod);
    } else if (plano === "PARCELADO") {
      setMetodo("CARTAO");
    } else if (allowedMethods.includes("PIX")) {
      setMetodo("PIX");
    } else {
      setMetodo(allowedMethods[0] ?? "PIX");
    }
  }, [plano, allowedMethods, initialMethod]);

  useEffect(() => {
    if (metodo === "CARTAO" && plano !== "PARCELADO") {
      setParcelas(1);
    }
  }, [metodo, plano]);

  const validarCartao = (): boolean => {
    if (!cardData.cardNumber || cardData.cardNumber.length < 13) {
      setError("Número do cartão inválido");
      return false;
    }

    const nomeCompleto = cardData.cardHolder.trim();
    if (!nomeCompleto) {
      setError("Nome do titular é obrigatório");
      return false;
    }

    if (/^\d+$/.test(nomeCompleto)) {
      setError("Nome do titular deve conter apenas letras");
      return false;
    }

    const palavras = nomeCompleto.split(/\s+/).filter((p) => p.length > 0);
    if (palavras.length < 2) {
      setError("Digite nome completo (ex: JOAO SILVA)");
      return false;
    }

    if (palavras.some((p) => p.length < 2)) {
      setError("Cada nome deve ter pelo menos 2 caracteres");
      return false;
    }

    if (!cardData.expiryMonth || !cardData.expiryYear) {
      setError("Data de validade inválida");
      return false;
    }
    if (!cardData.cvv || cardData.cvv.length < 3) {
      setError("CVV inválido");
      return false;
    }
    return true;
  };

  const handlePagamento = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const paymentData: PaymentStartPayload = {
        id_pedido: pedido.id_pedido,
        metodo: metodo,
      };

      if (metodo === "CARTAO") {
        if (!validarCartao()) {
          setLoading(false);
          return;
        }

        const fullYear =
          cardData.expiryYear.length === 2
            ? `20${cardData.expiryYear}`
            : cardData.expiryYear;

        paymentData.cardData = {
          number: cardData.cardNumber.replace(/\s/g, ""),
          holder: cardData.cardHolder.trim().toUpperCase(),
          exp_month: cardData.expiryMonth.padStart(2, "0"),
          exp_year: fullYear,
          security_code: cardData.cvv,
        };

        paymentData.installments = plano === "PARCELADO" ? parcelas : 1;
      }

      onPaymentStart(paymentData);
    } catch (err: unknown) {
      setError(
        err instanceof Error ? err.message : "Erro ao processar pagamento",
      );
    } finally {
      setLoading(false);
    }
  };

  const meses = Number(pedido.meses_duracao ?? 0) || 0;

  const formatarBRL = (valor: number): string =>
    valor.toLocaleString("pt-BR", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const totalCalculado = (() => {
    const forma = pedido.forma_pagamento ?? null;
    const valorTotal = Number(pedido.valor_total ?? 0) || 0;
    const valorMensal = Number(pedido.valor_mensal ?? 0) || 0;
    const valorMatricula = Number(pedido.valor_matricula ?? 0) || 0;
    const valorAvista = Number(pedido.valor_avista ?? 0) || 0;

    if (forma === "MENSAL" && valorMatricula > 0) {
      return valorMensal + valorMatricula;
    }

    if (valorTotal > 0) return valorTotal;
    if (forma === "MENSAL" || forma === "PARCELADO") return valorMensal * meses;
    if (forma === "AVISTA") return valorAvista;
    return 0;
  })();

  const isMensalComMatricula =
    plano === "MENSAL" && (Number(pedido.valor_matricula ?? 0) || 0) > 0;

  const metodoLabel: Record<PaymentData["metodo"], ReactNode> = {
    CARTAO: (
      <>
        <FaCreditCard /> Cartão de Crédito
      </>
    ),
    PIX: (
      <>
        <FaQrcode /> PIX
      </>
    ),
    BOLETO: (
      <>
        <FaBarcode /> Boleto
      </>
    ),
  };
  const submitLabelByMethod: Record<PaymentData["metodo"], string> = {
    CARTAO: "Pagar com Cartão de Crédito",
    PIX: "Pagar via PIX",
    BOLETO: "Gerar Boleto",
  };
  const submitLabel = submitLabelByMethod[metodo];

  const planoLabelMap: Record<NonNullable<typeof plano>, string> = {
    MENSAL: "Plano Mensal",
    AVISTA: "Plano À Vista",
    PARCELADO: "Plano Parcelado no Cartão",
  };

  return (
    <form onSubmit={handlePagamento} className="checkout-form">
      {error && <div className="error-message">{error}</div>}

      {}
      <div className="pedido-resumo">
        <h3>Resumo do Pedido</h3>
        <p>
          <strong>Tipo:</strong> {tipoLabel}
        </p>
        {isPreMontada && pedido.grade_nome && (
          <p>
            <strong>Nome da Grade:</strong> {pedido.grade_nome}
          </p>
        )}
        <p>
          <strong>Modalidade:</strong> {pedido.modalidade}
        </p>
        {plano && (
          <p>
            <strong>Plano:</strong> {planoLabelMap[plano]}
          </p>
        )}
        {metodo === "CARTAO" && plano !== "PARCELADO" && (
          <p
            style={{
              fontSize: 12,
              color: "#8a4b0f",
              marginTop: -2,
              marginBottom: 10,
            }}>
            No cartão, o pagamento é em 1x (valor do plano não muda).
          </p>
        )}
        <p className="valor-total">
          <strong>
            {isMensalComMatricula
              ? "Valor primeira parcela + matrícula:"
              : "Valor Total:"}
          </strong>{" "}
          R$ {formatarBRL(totalCalculado)}
        </p>
        {isMensalComMatricula && (
          <p
            style={{
              fontSize: 13,
              color: "#666",
              fontStyle: "italic",
              marginTop: 8,
              paddingTop: 8,
              borderTop: "1px solid #eee",
            }}>
            Você está pagando apenas a primeira parcela desta{" "}
            {isPreMontada ? "curso" : "grade"} de {meses}{" "}
            {meses === 1 ? "mês" : "meses"}. As demais parcelas devem ser pagas
            mensalmente.
          </p>
        )}
      </div>

      <div className="checkout-access-info">
        <h3>Como funciona a liberação do acesso</h3>
        <p>
          Assim que o pagamento for <strong>aprovado</strong>, enviaremos para o
          seu e-mail cadastrado um link para solicitar o agendamento das aulas e
          efetivar a matrícula.
        </p>
      </div>

      <div className="metodos-pagamento">
        <h3>Escolha o Método de Pagamento</h3>
        {plano && (
          <p
            style={{
              fontSize: 12,
              color: "#555",
              marginTop: 4,
              marginBottom: 8,
            }}>
            Método compatível com {planoLabelMap[plano]}:
            {plano === "PARCELADO"
              ? " Cartão de crédito"
              : " PIX, Boleto ou Cartão (1x)"}
          </p>
        )}
        {allowedMethods.includes("CARTAO") && (
          <label className="metodo-option">
            <input
              type="radio"
              name="metodo"
              value="CARTAO"
              checked={metodo === "CARTAO"}
              onChange={(e) =>
                setMetodo(e.target.value as PaymentData["metodo"])
              }
              disabled={isProcessing || loading}
            />
            <span className="metodo-label">{metodoLabel.CARTAO}</span>
          </label>
        )}

        {allowedMethods.includes("PIX") && (
          <label className="metodo-option">
            <input
              type="radio"
              name="metodo"
              value="PIX"
              checked={metodo === "PIX"}
              onChange={(e) =>
                setMetodo(e.target.value as PaymentData["metodo"])
              }
              disabled={isProcessing || loading}
            />
            <span className="metodo-label">{metodoLabel.PIX}</span>
          </label>
        )}

        {allowedMethods.includes("BOLETO") && (
          <label className="metodo-option">
            <input
              type="radio"
              name="metodo"
              value="BOLETO"
              checked={metodo === "BOLETO"}
              onChange={(e) =>
                setMetodo(e.target.value as PaymentData["metodo"])
              }
              disabled={isProcessing || loading}
            />
            <span className="metodo-label">{metodoLabel.BOLETO}</span>
          </label>
        )}
      </div>

      {/* Formulário de Cartão */}
      {metodo === "CARTAO" && (
        <div className="cartao-form">
          <h4>Dados do Cartão</h4>

          <div className="form-group">
            <label htmlFor="cardNumber">Número do Cartão</label>
            <input
              id="cardNumber"
              type="text"
              autoComplete="cc-number"
              placeholder="1234 5678 9012 3456"
              value={cardData.cardNumber}
              onChange={(e) => {
                const value = e.target.value.replace(/\D/g, "").slice(0, 16);
                setCardData({ ...cardData, cardNumber: value });
              }}
              maxLength={16}
              disabled={isProcessing || loading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="cardHolder">Nome do Titular (nome completo)</label>
            <input
              id="cardHolder"
              type="text"
              autoComplete="cc-name"
              placeholder="JOAO SILVA"
              value={cardData.cardHolder}
              onChange={(e) =>
                setCardData({
                  ...cardData,
                  cardHolder: e.target.value.toUpperCase(),
                })
              }
              disabled={isProcessing || loading}
            />
          </div>

          <div className="form-row">
            <div className="form-group">
              <label htmlFor="expiryMonth">Mês</label>
              <input
                id="expiryMonth"
                type="number"
                autoComplete="cc-exp-month"
                placeholder="MM"
                value={cardData.expiryMonth}
                onChange={(e) =>
                  setCardData({
                    ...cardData,
                    expiryMonth: Math.min(12, Number(e.target.value))
                      .toString()
                      .padStart(2, "0"),
                  })
                }
                min="1"
                max="12"
                disabled={isProcessing || loading}
              />
            </div>

            <div className="form-group">
              <label htmlFor="expiryYear">Ano</label>
              <input
                id="expiryYear"
                type="number"
                autoComplete="cc-exp-year"
                placeholder="30"
                value={cardData.expiryYear}
                onChange={(e) =>
                  setCardData({
                    ...cardData,
                    expiryYear: e.target.value.slice(0, 2),
                  })
                }
                disabled={isProcessing || loading}
              />
            </div>

            <div className="form-group">
              <label htmlFor="cvv">CVV</label>
              <input
                id="cvv"
                type="password"
                autoComplete="cc-csc"
                placeholder="123"
                value={cardData.cvv}
                onChange={(e) =>
                  setCardData({ ...cardData, cvv: e.target.value.slice(0, 4) })
                }
                maxLength={4}
                disabled={isProcessing || loading}
              />
            </div>

            {plano === "PARCELADO" && (
              <div className="form-group parcelas-group">
                <label htmlFor="parcelas">Parcelas</label>
                <select
                  id="parcelas"
                  value={parcelas}
                  onChange={(e) => setParcelas(Number(e.target.value))}
                  disabled={isProcessing || loading}
                  className="parcelas-select">
                  {opcoesParcelas.map((n) => {
                    const valorParcela = totalCalculado / n;
                    return (
                      <option key={n} value={n}>
                        {n}x de R$ {formatarBRL(valorParcela)} (sem juros)
                      </option>
                    );
                  })}
                </select>
                <small className="parcelas-info">
                  Curso de {pedido.meses_duracao || 12} meses - Parcelamento em
                  até {maxParcelas}x
                </small>
              </div>
            )}
          </div>
        </div>
      )}

      {}
      {metodo === "PIX" && (
        <div className="metodo-info">
          <p>Você receberá QR Code ou chave para copiar e colar.</p>
          <p>Confirmação imediata após o pagamento.</p>
        </div>
      )}

      {metodo === "BOLETO" && (
        <div className="metodo-info">
          <p>Geraremos um boleto (validade: 3 dias).</p>
          <p>Confirmação após a compensação do pagamento.</p>
        </div>
      )}

      {}

      {}
      <button
        type="submit"
        className="btn-pagar"
        disabled={isProcessing || loading}>
        {isProcessing || loading
          ? "Processando..."
          : `${submitLabel} — R$ ${formatarBRL(totalCalculado)}`}
      </button>

      {/* Aviso de Segurança */}
      <p className="security-notice">
        🔒 Seus dados estão protegidos e criptografados. Confiamos em padrões de
        segurança internacionais.
      </p>
    </form>
  );
}
