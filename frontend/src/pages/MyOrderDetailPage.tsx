import { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import axios from "axios";
import api from "../services/api";
import type { Pedido } from "../types/pedido";
import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import { FaWhatsapp } from "react-icons/fa";
import "./MyOrderDetailPage.css";
import { apiBaseUrl } from "../services/site";

interface PedidoResponse {
  data: Pedido[];
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

type PaymentMethod = "PIX" | "BOLETO" | "CARTAO";

interface LatestPaymentResponse {
  id_pagamento: number;
  id_pedido: number;
  metodo: PaymentMethod;
  status: "PENDENTE" | "APROVADO" | "RECUSADO";
  parcelas?: number | null;
  valor_parcela?: number | null;
  criado_em?: string | null;
  expiration_date?: string | null;
  is_pix_expired?: boolean;
  link_boleto?: string | null;
}

export default function MyOrderDetailPage() {
  const params = useParams();
  const id = params.id as string | undefined;
  const [pedido, setPedido] = useState<Pedido | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [totalPedidosUsuario, setTotalPedidosUsuario] = useState(0);
  const [posicaoPedido, setPosicaoPedido] = useState(0);
  const [idUsuario, setIdUsuario] = useState<number | null>(null);
  const [latestPayment, setLatestPayment] =
    useState<LatestPaymentResponse | null>(null);
  const [approvingDev, setApprovingDev] = useState(false);

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
    if (!id || !idUsuario) return;
    async function load() {
      try {
        setLoading(true);
        setError(null);
        const res = await api.get<Pedido>(`/pedidos/${id}`);
        setPedido(res.data);

        try {
          const payRes = await api.get<LatestPaymentResponse>(
            `/pagamentos/pedido/${id}/ultimo`,
          );
          setLatestPayment(payRes.data);
        } catch {
          setLatestPayment(null);
        }

        const allOrdersRes = await api.get<PedidoResponse>("/pedidos", {
          params: {
            id_usuario: idUsuario,
            limit: 1000,
          },
        });
        const allOrders = allOrdersRes.data.data || [];
        setTotalPedidosUsuario(allOrdersRes.data.total || allOrders.length);

        const pos = allOrders.findIndex(
          (p: Pedido) => p.id_pedido === Number(id),
        );
        setPosicaoPedido(pos >= 0 ? pos : 0);
      } catch {
        setError("Pedido não encontrado.");
      } finally {
        setLoading(false);
      }
    }
    load();
  }, [id, idUsuario]);

  const getOrderNumber = () => {
    return totalPedidosUsuario - posicaoPedido;
  };

  const refreshPedidoAndPayment = async () => {
    if (!id) return;

    const res = await api.get<Pedido>(`/pedidos/${id}`);
    setPedido(res.data);

    try {
      const payRes = await api.get<LatestPaymentResponse>(
        `/pagamentos/pedido/${id}/ultimo`,
      );
      setLatestPayment(payRes.data);
    } catch {
      setLatestPayment(null);
    }
  };

  const getValorExibicao = () => {
    if (!pedido) return 0;
    const valorTotal = Number(pedido.valor_total ?? 0);
    const valorMensal = pedido.valor_mensal ? Number(pedido.valor_mensal) : 0;
    const valorMatricula = pedido.valor_matricula
      ? Number(pedido.valor_matricula)
      : 0;

    if (pedido.forma_pagamento === "PARCELADO") {
      return valorTotal;
    }

    if (pedido.forma_pagamento === "MENSAL" && valorMatricula > 0) {
      return valorMensal + valorMatricula;
    }

    return valorMensal || valorTotal;
  };

  const getParcelamentoResumo = () => {
    if (!pedido || pedido.forma_pagamento !== "PARCELADO") return null;

    const valorTotal = Number(pedido.valor_total ?? 0);
    const parcelasEscolhidas = Number(latestPayment?.parcelas ?? 0);
    const valorParcelaRegistrado = Number(latestPayment?.valor_parcela ?? 0);
    const meses = Number(pedido.meses_duracao ?? 0);
    const valorMensal = Number(pedido.valor_mensal ?? 0);
    let parcelas = parcelasEscolhidas > 0 ? parcelasEscolhidas : 0;

    if (!parcelas && meses > 0) {
      parcelas = meses;
    }

    if (!parcelas && valorMensal > 0 && valorTotal > 0) {
      parcelas = Math.max(1, Math.round(valorTotal / valorMensal));
    }

    if (!parcelas || valorTotal <= 0) return null;

    return {
      parcelas,
      valorParcela:
        valorParcelaRegistrado > 0
          ? valorParcelaRegistrado
          : valorTotal / parcelas,
    };
  };

  const formatFormaPagamento = (forma: string) => {
    const map: Record<string, string> = {
      AVISTA: "À VISTA",
      MENSAL: "Mensalidade",
      PARCELADO: "Parcelado",
    };
    return map[forma] || forma;
  };

  const formatTipo = (tipo: string) => {
    if (tipo === "GRADE") return "GRADE PRÉ-MONTADA";
    if (tipo === "PERSONALIZADA") return "GRADE PERSONALIZADA";
    return tipo;
  };

  const formatDuracao = (meses: string | null) => {
    if (!meses || !meses.trim()) return "";
    const valor = Number(meses);
    if (isNaN(valor) || valor === 0) return "";
    if (valor === 1) return "1 mês";
    return `${valor} meses`;
  };

  const getCheckoutUrl = () => {
    let url = `/checkout/${pedido!.id_pedido}`;

    if (pedido!.forma_pagamento === "PARCELADO") {
      url += "?method=CARTAO";
    }

    return url;
  };

  const getSelectedPaymentMethod = (): PaymentMethod => {
    if (latestPayment?.metodo) return latestPayment.metodo;
    if (pedido?.forma_pagamento === "PARCELADO") return "CARTAO";
    return "PIX";
  };

  const getMethodLabel = (method: PaymentMethod): string => {
    if (method === "PIX") return "Pagar via PIX";
    if (method === "BOLETO") return "Pagar boleto";
    return "Pagar com cartão";
  };

  const getMethodName = (method: PaymentMethod): string => {
    if (method === "PIX") return "PIX";
    if (method === "BOLETO") return "Boleto";
    return "Cartão de crédito";
  };

  const getPlanoNomeWhatsapp = (): string => {
    const forma = (pedido?.forma_pagamento || "").toUpperCase();
    if (forma === "MENSAL") return "Mensal";
    if (forma === "AVISTA") return "À Vista";
    if (forma === "PARCELADO") return "Parcelado no Cartão";
    return "Escolhido no pedido";
  };

  const getNomeCursoWhatsapp = (): string => {
    if (pedido?.grade_nome) return pedido.grade_nome;
    if (pedido?.cursos && pedido.cursos.length > 0) {
      if (pedido.cursos.length === 1) return pedido.cursos[0].nome;
      return `${pedido.cursos[0].nome} + ${pedido.cursos.length - 1} cursos`;
    }
    return "seu curso";
  };

  const getWhatsappAgendamentoLink = (): string => {
    const idUsuarioPedido = pedido?.id_usuario || idUsuario || 0;
    const mensagem = `Olá, realizei o pagamento do curso ${getNomeCursoWhatsapp()} pelo plano ${getPlanoNomeWhatsapp()} e gostaria de agendar minhas aulas! ID de usuário: ${idUsuarioPedido}. ID do pedido: ${pedido?.id_pedido || id}.`;
    return `https://wa.me/5535998421176?text=${encodeURIComponent(mensagem)}`;
  };

  const getPaymentStatusName = (status: LatestPaymentResponse["status"]) => {
    if (status === "APROVADO") return "Aprovado";
    if (status === "RECUSADO") return "Recusado";
    return "Pendente";
  };

  const getPaymentStatusClass = (status: LatestPaymentResponse["status"]) => {
    if (status === "APROVADO") return "approved";
    if (status === "RECUSADO") return "refused";
    return "pending";
  };

  const getPreferredCheckoutUrl = () => {
    const method = getSelectedPaymentMethod();
    const params = new URLSearchParams();
    params.set("method", method);
    if (method === "PIX") {
      params.set("resume", "1");
    }
    return `/checkout/${pedido!.id_pedido}?${params.toString()}`;
  };

  const canUseDevApproval = import.meta.env.DEV;

  const handleApproveForDev = async () => {
    if (!id || approvingDev) return;

    try {
      setApprovingDev(true);
      await api.post(`/pagamentos/pedido/${id}/aprovar-teste`);
      await refreshPedidoAndPayment();
    } catch (err) {
      if (axios.isAxiosError(err)) {
        const message =
          (err.response?.data?.error as string | undefined) ||
          "Nao foi possivel aprovar o pedido em modo desenvolvimento.";
        setError(message);
      } else {
        setError("Nao foi possivel aprovar o pedido em modo desenvolvimento.");
      }
    } finally {
      setApprovingDev(false);
    }
  };

  if (loading) {
    return (
      <>
        <Header />
        <main className="container order-detail-page">
          <p>Carregando pedido...</p>
        </main>
        <Footer />
      </>
    );
  }

  if (error) {
    return (
      <>
        <Header />
        <main className="container order-detail-page">{error}</main>
        <Footer />
      </>
    );
  }

  if (!pedido) {
    return (
      <>
        <Header />
        <main className="container order-detail-page">
          Pedido não encontrado.
        </main>
        <Footer />
      </>
    );
  }

  const valor = getValorExibicao();
  const parcelamento = getParcelamentoResumo();
  const horas = Number(pedido.horas_total ?? 0);
  const statusClass = pedido.status.toLowerCase();
  const selectedMethod = getSelectedPaymentMethod();
  const isBoletoMethod = selectedMethod === "BOLETO";
  const shouldOpenBoleto = isBoletoMethod && !!latestPayment?.link_boleto;

  return (
    <>
      <Header />
      <main className="container order-detail-page">
        <Link to="/meus-pedidos" className="order-detail-back">
          ← Voltar para meus pedidos
        </Link>

        <div className="order-detail-header">
          <h1>Pedido #{getOrderNumber()}</h1>
          <div className="order-detail-status-line">
            <span className={`order-detail-status-badge ${statusClass}`}>
              {pedido.status}
            </span>
          </div>
        </div>

        <div className="order-detail-info-section">
          <div className="order-detail-info-grid">
            <div className="order-detail-info-item">
              <span className="order-detail-info-label">Tipo</span>
              <span className="order-detail-info-value">
                {formatTipo(pedido.tipo)}
              </span>
            </div>

            {pedido.grade_nome && (
              <div className="order-detail-info-item">
                <span className="order-detail-info-label">Grade</span>
                {pedido.grade_slug ? (
                  <Link
                    to={`/grades/${pedido.grade_slug}`}
                    className="order-detail-info-value link">
                    {pedido.grade_nome}
                  </Link>
                ) : (
                  <span className="order-detail-info-value">
                    {pedido.grade_nome}
                  </span>
                )}
              </div>
            )}

            <div className="order-detail-info-item">
              <span className="order-detail-info-label">Modalidade</span>
              <span className="order-detail-info-value">
                {pedido.modalidade}
              </span>
            </div>

            <div className="order-detail-info-item">
              <span className="order-detail-info-label">Carga Horária</span>
              <span className="order-detail-info-value">{horas}h</span>
            </div>

            {pedido.meses_duracao && (
              <div className="order-detail-info-item">
                <span className="order-detail-info-label">Duração</span>
                <span className="order-detail-info-value">
                  {formatDuracao(String(pedido.meses_duracao))}
                </span>
              </div>
            )}

            {pedido.forma_pagamento && (
              <div className="order-detail-info-item">
                <span className="order-detail-info-label">
                  Forma de Pagamento
                </span>
                <span className="order-detail-info-value">
                  {formatFormaPagamento(pedido.forma_pagamento)}
                </span>
              </div>
            )}

            <div className="order-detail-info-item">
              <span className="order-detail-info-label">Valor Total</span>
              <span className="order-detail-info-value price">
                R$ {valor.toFixed(2)}
              </span>
            </div>

            {parcelamento && (
              <div className="order-detail-info-item">
                <span className="order-detail-info-label">Parcelamento</span>
                <span className="order-detail-info-value">
                  {parcelamento.parcelas}x de R${" "}
                  {parcelamento.valorParcela.toFixed(2)}
                </span>
              </div>
            )}

            <div className="order-detail-info-item">
              <span className="order-detail-info-label">Data do Pedido</span>
              <span className="order-detail-info-value">
                {new Date(pedido.criado_em).toLocaleDateString("pt-BR")}
              </span>
            </div>
          </div>
        </div>

        <div className="order-detail-payment-section">
          <h2>Resumo do pagamento</h2>
          {latestPayment ? (
            <>
              <div className="order-detail-payment-grid">
                <div className="order-detail-payment-item">
                  <span className="order-detail-info-label">Método escolhido</span>
                  <span className="order-detail-info-value">
                    {getMethodName(selectedMethod)}
                  </span>
                </div>

                <div className="order-detail-payment-item">
                  <span className="order-detail-info-label">Status do pagamento</span>
                  <span className="order-detail-info-value">
                    <span
                      className={`order-detail-payment-badge ${getPaymentStatusClass(latestPayment.status)}`}>
                      {getPaymentStatusName(latestPayment.status)}
                    </span>
                  </span>
                </div>

                {latestPayment.criado_em && (
                  <div className="order-detail-payment-item">
                    <span className="order-detail-info-label">Gerado em</span>
                    <span className="order-detail-info-value">
                      {new Date(latestPayment.criado_em).toLocaleString(
                        "pt-BR",
                      )}
                    </span>
                  </div>
                )}

                {selectedMethod === "PIX" && latestPayment.expiration_date && (
                  <div className="order-detail-payment-item">
                    <span className="order-detail-info-label">Expiração do PIX</span>
                    <span className="order-detail-info-value">
                      {latestPayment.is_pix_expired
                        ? "PIX expirado"
                        : new Date(latestPayment.expiration_date).toLocaleString(
                            "pt-BR",
                          )}
                    </span>
                  </div>
                )}
              </div>

              {latestPayment.status === "APROVADO" && (
                <div className="order-detail-access-release">
                  <p>
                    Seu pagamento foi aprovado. Verifique o e-mail cadastrado:
                    enviamos por lá o link para agendamento das aulas e
                    efetivação da matrícula.
                  </p>
                  <a
                    href={getWhatsappAgendamentoLink()}
                    target="_blank"
                    rel="noreferrer"
                    className="order-detail-btn order-detail-btn-whatsapp">
                    <FaWhatsapp /> Não recebi o e-mail, falar no WhatsApp
                  </a>
                </div>
              )}
            </>
          ) : (
            <p className="order-detail-payment-empty">
              Ainda não há pagamento gerado para este pedido.
            </p>
          )}
        </div>

        {pedido.cursos && pedido.cursos.length > 0 && (
          <div className="order-detail-section">
            <h2>📚 Cursos desta grade</h2>
            <ul className="order-detail-courses-list">
              {pedido.cursos.map((c) => (
                <li key={c.id_curso} className="order-detail-course-item">
                  <span className="order-detail-course-name">{c.nome}</span>
                  {c.carga_horaria != null && (
                    <span className="order-detail-course-hours">
                      {c.carga_horaria}h
                    </span>
                  )}
                </li>
              ))}
            </ul>
          </div>
        )}

        {pedido.status === "PENDENTE" && (
          <div className="order-detail-actions">
            {shouldOpenBoleto ? (
              <a
                href={latestPayment?.link_boleto || "#"}
                target="_blank"
                rel="noreferrer"
                className="order-detail-btn order-detail-btn-pay">
                {getMethodLabel(selectedMethod)}
              </a>
            ) : isBoletoMethod ? (
              <button
                className="order-detail-btn order-detail-btn-pay order-detail-btn-disabled"
                type="button"
                disabled>
                Boleto indisponível
              </button>
            ) : (
              <Link
                to={getPreferredCheckoutUrl()}
                className="order-detail-btn order-detail-btn-pay">
                {getMethodLabel(selectedMethod)}
              </Link>
            )}
            <Link
              to={getCheckoutUrl()}
              className="order-detail-btn order-detail-btn-change-method">
              Mudar forma de pagamento
            </Link>
            <button
              className="order-detail-btn order-detail-btn-whatsapp"
              onClick={() => {
                const message = `Olá! Gostaria de informações sobre o pedido #${getOrderNumber()}`;
                window.open(
                  `https://wa.me/5535998421176?text=${encodeURIComponent(message)}`,
                  "_blank",
                );
              }}>
              <FaWhatsapp /> Falar no WhatsApp
            </button>
            {canUseDevApproval && (
              <button
                type="button"
                className="order-detail-btn order-detail-btn-dev-approve"
                onClick={handleApproveForDev}
                disabled={approvingDev}>
                {approvingDev ? "Aprovando..." : "Aprovar pedido (dev)"}
              </button>
            )}
          </div>
        )}
      </main>
      <Footer />
    </>
  );
}
