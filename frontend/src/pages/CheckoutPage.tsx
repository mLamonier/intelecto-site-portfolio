import { useEffect, useState } from "react";
import { useParams, Link, useNavigate, useSearchParams } from "react-router-dom";
import axios from "axios";
import api from "../services/api";
import type { Pedido } from "../types/pedido";
import type { PaymentData } from "../components/Checkout/CheckoutForm";
import CheckoutForm from "../components/Checkout/CheckoutForm";
import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import { useToast } from "../hooks/useToast";
import { ToastContainer } from "../components/Toast/ToastContainer";
import { useRetry } from "../hooks/useRetry";
import "./CheckoutPage.css";
import { apiBaseUrl } from "../services/site";

interface PedidoResponse {
  data: Pedido[];
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

interface PaymentResponse {
  id_pagamento: number;
  id_pedido?: number;
  metodo?: string;
  qr_code?: string;
  copy_and_paste?: string;
  link_boleto?: string;
  qr_code_image_url?: string;
  qr_code_base64_url?: string;
  expiration_date?: string;
  dados?: Record<string, unknown>;
}

type PedidoTemp = Pedido & {
  id_usuario?: number | null;
  cliente?: {
    nome?: string;
    email?: string;
    telefone?: string;
    cpf?: string;
  };
};

interface PaymentQrLink {
  rel?: string;
  href?: string;
}

interface PaymentDados {
  qr_codes?: Array<{
    links?: PaymentQrLink[];
    expiration_date?: string;
  }>;
}

type PaymentMethod = "PIX" | "BOLETO" | "CARTAO";

interface LatestPaymentResponse {
  id_pagamento: number;
  id_pedido: number;
  metodo: PaymentMethod;
  status: "PENDENTE" | "APROVADO" | "RECUSADO";
  criado_em?: string | null;
  expiration_date?: string | null;
  is_pix_expired?: boolean;
}

export default function CheckoutPage() {
  const { id_pedido } = useParams();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { toasts, removeToast, success, error: showError, info } = useToast();
  const { retry } = useRetry({ maxAttempts: 3, delayMs: 2000 });
  const [loading, setLoading] = useState(false);
  const [processingPayment, setProcessingPayment] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [paymentResponse, setPaymentResponse] =
    useState<PaymentResponse | null>(null);
  const [copySuccess, setCopySuccess] = useState(false);
  const [pixTimeLeftSeconds, setPixTimeLeftSeconds] = useState<number | null>(
    null,
  );
  const [pedido, setPedido] = useState<Pedido | null>(null);
  const [pedidoTemp, setPedidoTemp] = useState<PedidoTemp | null>(null);
  const [totalPedidosUsuario, setTotalPedidosUsuario] = useState(0);
  const [posicaoPedido, setPosicaoPedido] = useState(0);
  const [idUsuario, setIdUsuario] = useState<number | null>(null);
  const [resumeMessage, setResumeMessage] = useState<string | null>(null);

  const requestedMethodParam = searchParams.get("method");
  const resumeRequested = searchParams.get("resume") === "1";

  const normalizeMethod = (method: string | null): PaymentMethod | null => {
    if (!method) return null;
    const upperMethod = method.toUpperCase();
    if (
      upperMethod === "PIX" ||
      upperMethod === "BOLETO" ||
      upperMethod === "CARTAO"
    ) {
      return upperMethod;
    }
    return null;
  };

  const requestedMethod = normalizeMethod(requestedMethodParam);

  const getPaymentCacheKey = (orderId: number) => `checkout_payment_${orderId}`;

  const savePaymentCache = (orderId: number, data: PaymentResponse) => {
    const payload = {
      ...data,
      id_pedido: orderId,
      cached_at: new Date().toISOString(),
    };
    localStorage.setItem(getPaymentCacheKey(orderId), JSON.stringify(payload));
  };

  const getPaymentCache = (orderId: number): PaymentResponse | null => {
    const raw = localStorage.getItem(getPaymentCacheKey(orderId));
    if (!raw) return null;
    try {
      return JSON.parse(raw) as PaymentResponse;
    } catch {
      return null;
    }
  };

  useEffect(() => {
    const checkAuth = async () => {
      try {
        const response = await axios.get(`${apiBaseUrl()}/auth-check.php`, {
          withCredentials: true,
        });

        if (response.data.logado && response.data.usuario) {
          setIdUsuario(
            response.data.usuario.id || response.data.usuario.id_usuario,
          );
        }
      } catch {
        void 0;
      }
    };

    checkAuth();
  }, []);

  useEffect(() => {
    if (id_pedido === "temp") {
      const tempData = localStorage.getItem("pedido_temp");
      if (tempData) {
        try {
          const data = JSON.parse(tempData) as PedidoTemp;
          setPedidoTemp(data);
        } catch {
          setError("Dados do pedido invÃ¡lidos.");
        }
      } else {
        setError("Nenhum pedido encontrado.");
      }
      return;
    }

    if (!id_pedido || !idUsuario) return;
    async function load() {
      try {
        setLoading(true);
        setError(null);
        setResumeMessage(null);
        const res = await api.get<Pedido>(`/pedidos/${id_pedido}`);
        setPedido(res.data);

        const allOrdersRes = await api.get<PedidoResponse>("/pedidos", {
          params: {
            id_usuario: idUsuario,
            limit: 1000,
          },
        });
        const allOrders = allOrdersRes.data.data || [];
        setTotalPedidosUsuario(allOrdersRes.data.total || allOrders.length);

        const pos = allOrders.findIndex(
          (p: Pedido) => p.id_pedido === Number(id_pedido),
        );
        setPosicaoPedido(pos >= 0 ? pos : 0);

        if (resumeRequested && requestedMethod === "PIX") {
          let latestPayment: LatestPaymentResponse | null = null;
          try {
            const latestRes = await api.get<LatestPaymentResponse>(
              `/pagamentos/pedido/${id_pedido}/ultimo`,
            );
            latestPayment = latestRes.data;
          } catch {
            latestPayment = null;
          }

          const cachedPayment = getPaymentCache(Number(id_pedido));
          if (
            cachedPayment &&
            cachedPayment.metodo === "PIX" &&
            latestPayment &&
            latestPayment.id_pagamento === cachedPayment.id_pagamento
          ) {
            setPaymentResponse(cachedPayment);
          } else if (latestPayment?.metodo === "PIX") {
            if (latestPayment.expiration_date) {
              setPaymentResponse({
                id_pagamento: latestPayment.id_pagamento,
                id_pedido: latestPayment.id_pedido,
                metodo: "PIX",
                expiration_date: latestPayment.expiration_date,
              });
            }
            setResumeMessage(
              "Nao foi possivel recuperar o QR Code anterior. Gere um novo PIX abaixo.",
            );
          }
        }
      } catch {
        setError("Pedido nÃ£o encontrado.");
      } finally {
        setLoading(false);
      }
    }
    load();
  }, [id_pedido, idUsuario, resumeRequested, requestedMethod]);

  const getOrderNumber = () => {
    if (!pedido) return null;
    return totalPedidosUsuario - posicaoPedido;
  };

  const handlePaymentStart = async (paymentData: PaymentData) => {
    setProcessingPayment(true);
    setError(null);

    try {
      let pedidoId: number | string = id_pedido || "";

      if (id_pedido === "temp" && pedidoTemp) {
        let idUsuario = pedidoTemp.id_usuario;

        if (!idUsuario) {
          const cpfLimpo = (pedidoTemp.cliente?.cpf || "").replace(/\D/g, "");
          const usuarioPayload = {
            nome: pedidoTemp.cliente?.nome,
            email: pedidoTemp.cliente?.email,
            telefone: pedidoTemp.cliente?.telefone,
            cpf: cpfLimpo,
          };

          await api.post("/usuarios/verificar", {
            email: usuarioPayload.email,
            cpf: cpfLimpo,
          });

          const usuarioRes = await api.post(
            "/usuarios/primeiro-acesso",
            usuarioPayload,
          );
          idUsuario = usuarioRes.data?.id_usuario || usuarioRes.data?.id;
        }

        if (!idUsuario) {
          throw new Error(
            "NÃ£o foi possÃ­vel vincular o usuÃ¡rio. FaÃ§a login e tente novamente.",
          );
        }

        const pedidoPayload = {
          ...pedidoTemp,
          id_usuario: idUsuario,
        };
        delete pedidoPayload.cliente;

        const pedidoRes = await api.post("/pedidos", pedidoPayload);
        pedidoId = pedidoRes.data.id_pedido;

        localStorage.removeItem("pedido_temp");
      }

      if (!pedidoId || pedidoId === "temp") {
        throw new Error("ID do pedido invÃ¡lido");
      }

      const paymentPayload = {
        ...paymentData,
        id_pedido: typeof pedidoId === "string" ? parseInt(pedidoId) : pedidoId,
      };

      let res: { status: number; data: PaymentResponse };

      if (paymentData.metodo === "PIX" || paymentData.metodo === "BOLETO") {
        res = await retry(
          async () => api.post<PaymentResponse>("/pagamentos", paymentPayload),
          (attempt) => {
            info(
              `Tentativa ${attempt} de ${3}`,
              `Processando ${paymentData.metodo}...`,
            );
          },
          (retryError) => {
            if (!axios.isAxiosError(retryError)) return true;
            const status = retryError.response?.status;
            if (!status) return true;
            return status >= 500 || status === 429;
          },
        );
      } else {
        res = await api.post<PaymentResponse>("/pagamentos", paymentPayload);
      }

      if (res.status === 201) {
        setPaymentResponse(res.data);
        setResumeMessage(null);
        savePaymentCache(
          typeof pedidoId === "string" ? parseInt(pedidoId) : pedidoId,
          res.data,
        );

        switch (paymentData.metodo) {
          case "PIX":
            info(
              "PIX gerado com sucesso",
              "ApÃ³s a aprovaÃ§Ã£o do pagamento, enviaremos por e-mail o link de agendamento das aulas.",
            );
            showPixModal();
            break;
          case "BOLETO":
            success(
              "Boleto gerado com sucesso!",
              "Assim que o boleto for compensado, vocÃª receberÃ¡ por e-mail o link para agendar as aulas.",
            );
            break;
          case "CARTAO":
            info(
              "Pagamento processado",
              "ApÃ³s a aprovaÃ§Ã£o, vocÃª receberÃ¡ por e-mail o link de agendamento das aulas.",
            );
            break;
        }
      }
    } catch (err: unknown) {
      const errorData = err as {
        response?: {
          status?: number;
          data?: {
            error?: string;
            details?: string;
            message?: string;
            debug?: { error_messages?: Array<{ description?: string }> };
          };
        };
        message?: string;
      };

      let errorMsg = "Erro ao processar pagamento";

      if (errorData.response?.status === 409) {
        errorMsg =
          "E-mail ou CPF jÃ¡ cadastrados. FaÃ§a login e tente novamente.";
      } else if (errorData.response?.data?.details) {
        errorMsg = errorData.response.data.details;
      } else if (
        errorData.response?.data?.debug?.error_messages?.[0]?.description
      ) {
        errorMsg = errorData.response.data.debug.error_messages[0].description;
      } else if (errorData.response?.data?.message) {
        errorMsg = errorData.response.data.message;
      } else if (errorData.response?.data?.error) {
        errorMsg = errorData.response.data.error;
      } else if (errorData.response?.status) {
        errorMsg = `Falha no pagamento (HTTP ${errorData.response.status}).`;
      } else if (errorData.message) {
        errorMsg = errorData.message;
      }

      setError(errorMsg);
      showError("Erro ao processar pagamento", errorMsg);
    } finally {
      setProcessingPayment(false);
    }
  };

  const showPixModal = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text).then(() => {
      setCopySuccess(true);
      setTimeout(() => setCopySuccess(false), 2000);
    });
  };

  const handleRegeneratePix = () => {
    if (!pedidoAtual?.id_pedido) return;
    void handlePaymentStart({
      id_pedido: pedidoAtual.id_pedido,
      metodo: "PIX",
    });
  };

  useEffect(() => {
    if (paymentResponse?.metodo !== "PIX") {
      setPixTimeLeftSeconds(null);
      return;
    }

    const dados = paymentResponse.dados as PaymentDados | undefined;
    const expirationDateRaw =
      paymentResponse.expiration_date || dados?.qr_codes?.[0]?.expiration_date;

    if (!expirationDateRaw) {
      setPixTimeLeftSeconds(null);
      return;
    }

    const expirationDateMs = new Date(expirationDateRaw).getTime();
    if (Number.isNaN(expirationDateMs)) {
      setPixTimeLeftSeconds(null);
      return;
    }

    const updateCountdown = () => {
      const secondsLeft = Math.max(
        0,
        Math.floor((expirationDateMs - Date.now()) / 1000),
      );
      setPixTimeLeftSeconds(secondsLeft);
    };

    updateCountdown();
    const intervalId = window.setInterval(updateCountdown, 1000);

    return () => window.clearInterval(intervalId);
  }, [paymentResponse]);

  const formatPixCountdown = (secondsLeft: number | null) => {
    if (secondsLeft === null) return "--:--";

    const minutes = Math.floor(secondsLeft / 60);
    const seconds = secondsLeft % 60;

    return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
  };

  if (loading) {
    return (
      <>
        <Header />
        <main className="container">Carregando checkout...</main>
        <Footer />
      </>
    );
  }
  if (error && !paymentResponse) {
    return (
      <>
        <Header />
        <main className="container">
          <div className="error-box">
            <p>{error}</p>
            <Link to="/" className="btn-link">
              Voltar para home
            </Link>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  // Pedido pode ser do banco ou temporÃ¡rio
  const pedidoAtual = pedido || pedidoTemp;

  const paymentDados = paymentResponse?.dados as PaymentDados | undefined;
  const qrImageUrl =
    paymentResponse?.qr_code_image_url ||
    paymentResponse?.qr_code_base64_url ||
    (paymentDados?.qr_codes?.[0]?.links?.find(
      (link) => link?.rel === "QRCODE.PNG",
    )?.href ??
      null) ||
    (paymentDados?.qr_codes?.[0]?.links?.find(
      (link) => link?.rel === "QRCODE.BASE64",
    )?.href ??
      null);
  const pixCodeText =
    paymentResponse?.qr_code || paymentResponse?.copy_and_paste || "";
  const isPixExpired =
    paymentResponse?.metodo === "PIX" && pixTimeLeftSeconds === 0;
  const showGenerateNewPixButton =
    paymentResponse?.metodo === "PIX" && (isPixExpired || !pixCodeText);

  if (!pedidoAtual) {
    return (
      <>
        <Header />
        <main className="container">Pedido nÃ£o encontrado.</main>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main className="container checkout-page">
        {pedido && (
          <Link
            to={`/meus-pedidos/${pedido.id_pedido}`}
            className="order-detail-back">
            â† Voltar ao Pedido
          </Link>
        )}

        <h1 className="text-2xl font-semibold mt-4 mb-4">
          {pedido
            ? `Checkout do Pedido #${getOrderNumber()}`
            : "Finalizar Pedido"}
        </h1>

        {!paymentResponse ? (
          <CheckoutForm
            pedido={pedidoAtual}
            onPaymentStart={handlePaymentStart}
            isProcessing={processingPayment}
            initialMethod={requestedMethod}
          />
        ) : paymentResponse.metodo === "PIX" ? (
          <div className="pix-payment-section">
            {!isPixExpired && (
              <>
            <div className="pix-success-header">
              <h2>âœ“ PIX Gerado com Sucesso! ðŸŽ‰</h2>
              <p className="pix-subtitle">
                Escaneie o cÃ³digo abaixo ou copie a chave PIX para pagar
              </p>
            </div>
            <div className="payment-access-release-note">
              ApÃ³s a aprovaÃ§Ã£o do pagamento, vocÃª receberÃ¡ no e-mail cadastrado
              um link para solicitar o agendamento das aulas e efetivar a
              matrÃ­cula.
            </div>

            {qrImageUrl && (
              <div className="qr-code-container">
                <img
                  src={qrImageUrl}
                  alt="QR Code PIX"
                  className="qr-code-image"
                />
              </div>
            )}
              </>
            )}


            <div className={`pix-timer-box ${isPixExpired ? "expired" : ""}`}>
              <p className="pix-timer-label">Tempo para expiraÃ§Ã£o do PIX</p>
              <p className="pix-timer-value">
                {isPixExpired
                  ? "PIX expirado"
                  : formatPixCountdown(pixTimeLeftSeconds)}
              </p>
              {isPixExpired && (
                <p className="pix-timer-note">
                  O tempo de 15 minutos acabou. Gere um novo PIX para pagar.
                </p>
              )}
            </div>

            {!isPixExpired && resumeMessage && (
              <div className="pix-resume-message">{resumeMessage}</div>
            )}

            {!isPixExpired && (
            <div className="pix-code-box">
              <p className="pix-label">CÃ³digo PIX (Copie e Cole):</p>
              <div className="pix-code-input-group">
                <input
                  type="text"
                  value={pixCodeText}
                  readOnly
                  className="pix-code-input"
                />
                <button
                  className={`copy-btn ${copySuccess ? "copied" : ""}`}
                  onClick={() => copyToClipboard(pixCodeText)}
                  disabled={isPixExpired || !pixCodeText}>
                  {isPixExpired
                    ? "Expirado"
                    : copySuccess
                      ? "Copiado!"
                      : "Copiar"}
                </button>
              </div>
            </div>
            )}


            {showGenerateNewPixButton && (
              <button
                className="btn btn-secondary btn-regenerate-pix"
                onClick={handleRegeneratePix}
                disabled={processingPayment}>
                {processingPayment ? "Gerando novo PIX..." : "Gerar novo PIX"}
              </button>
            )}

            {!isPixExpired && (
            <div className="pix-instructions">
              <h3>Como Pagar:</h3>
              <ol>
                <li>Abra o app do seu banco</li>
                <li>Escolha a opÃ§Ã£o PIX / TransferÃªncia</li>
                <li>Escaneie o cÃ³digo QR ou cole a chave</li>
                <li>Confirme o pagamento</li>
              </ol>
              <p className="pix-note">
                ApÃ³s a aprovaÃ§Ã£o do pagamento, enviaremos o link de liberaÃ§Ã£o do
                acesso para o seu e-mail cadastrado.
              </p>
            </div>
            )}


            <button
              className="btn btn-primary"
              onClick={() => {
                navigate(`/meus-pedidos/${paymentResponse.id_pedido}`);
              }}>
              Ver Pedidos
            </button>
          </div>
        ) : paymentResponse.metodo === "BOLETO" ? (
          <div className="boleto-payment-section">
            <div className="boleto-header">
              <h2>âœ“ Boleto Gerado! ðŸŽ‰</h2>
              <p className="boleto-subtitle">
                Clique no botÃ£o abaixo para abrir o boleto em outra guia
              </p>
            </div>
            <div className="payment-access-release-note">
              Quando o boleto for compensado e aprovado, vocÃª receberÃ¡ no e-mail
              cadastrado o link para agendamento das aulas e efetivaÃ§Ã£o da
              matrÃ­cula.
            </div>

            {paymentResponse.link_boleto ? (
              <a
                className="btn btn-primary boleto-btn"
                href={paymentResponse.link_boleto}
                target="_blank"
                rel="noreferrer">
                Abrir boleto em outra guia
              </a>
            ) : (
              <p className="boleto-warning">
                NÃ£o recebemos o link do boleto. Tente novamente ou escolha outro
                mÃ©todo.
              </p>
            )}

            <button
              className="btn btn-secondary"
              onClick={() => {
                navigate(`/meus-pedidos/${paymentResponse.id_pedido}`);
              }}>
              Ver Pedidos
            </button>
          </div>
        ) : (
          <div className="payment-success">
            <h2>Pagamento processado</h2>
            <p>
              Assim que o pagamento for aprovado, enviaremos para o e-mail
              cadastrado o link de agendamento das aulas e efetivaÃ§Ã£o da
              matrÃ­cula.
            </p>
            <button
              className="btn btn-primary"
              onClick={() =>
                navigate(`/meus-pedidos/${paymentResponse.id_pedido}`)
              }>
              Ver Pedidos
            </button>
          </div>
        )}
      </main>

      <Footer />
      <ToastContainer toasts={toasts} onRemove={removeToast} />
    </>
  );
}

