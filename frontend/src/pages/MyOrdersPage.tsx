import { useEffect, useState } from "react";
import api from "../services/api";
import axios from "axios";
import { Link } from "react-router-dom";
import type { Pedido } from "../types/pedido";
import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import { FaEye } from "react-icons/fa";
import "./MyOrdersPage.css";
import { apiBaseUrl } from "../services/site";

interface PedidoResponse {
  data: Pedido[];
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

export default function MyOrdersPage() {
  const [pedidos, setPedidos] = useState<Pedido[]>([]);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalPedidos, setTotalPedidos] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [statusFilter, setStatusFilter] = useState<string>("");
  const [idUsuario, setIdUsuario] = useState<number | null>(null);

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
    async function load() {
      if (!idUsuario) return;
      try {
        setLoading(true);
        setError(null);
        const res = await api.get<PedidoResponse>("/pedidos", {
          params: {
            id_usuario: idUsuario,
            page,
            per_page: 5,
            status: statusFilter || undefined,
          },
        });
        setPedidos(res.data.data ?? []);
        setTotalPages(res.data.total_pages ?? 1);
        setTotalPedidos(res.data.total ?? 0);
      } catch {
        setError("Erro ao carregar pedidos.");
      } finally {
        setLoading(false);
      }
    }
    load();
  }, [page, statusFilter, idUsuario]);

  const canPrev = page > 1;
  const canNext = page < totalPages;

  const formatFormaPagamento = (forma: string | null | undefined) => {
    if (!forma) return "Não definido";
    if (forma === "AVISTA") return "À VISTA";
    if (forma === "MENSAL") return "Mensal";
    if (forma === "PARCELADO") return "Parcelado";
    return forma;
  };

  const formatDuracao = (meses: number | null) => {
    if (!meses) return "";
    return meses === 1 ? "1 mês" : `${meses} meses`;
  };

  const getOrderNumber = (index: number) => {
    return totalPedidos - ((page - 1) * 5 + index);
  };

  const getValorMensal = (pedido: Pedido) => {
    const valorMensal = pedido.valor_mensal ? Number(pedido.valor_mensal) : 0;
    const valorMatricula = pedido.valor_matricula
      ? Number(pedido.valor_matricula)
      : 0;

    if (pedido.forma_pagamento === "MENSAL" && valorMatricula > 0) {
      return valorMensal + valorMatricula;
    }

    return valorMensal || Number(pedido.valor_total ?? 0);
  };

  if (loading && pedidos.length === 0) {
    return (
      <>
        <Header />
        <main className="container orders-page">
          <p>Carregando pedidos...</p>
        </main>
        <Footer />
      </>
    );
  }

  if (!loading && pedidos.length === 0 && !statusFilter) {
    return (
      <>
        <Header />
        <main className="container orders-page">
          <div className="orders-empty">
            <p>📦 Você ainda não fez nenhum pedido.</p>
            <Link to="/cursos">Explorar cursos →</Link>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  if (error) {
    return (
      <>
        <Header />
        <main className="container orders-page">{error}</main>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main className="container orders-page">
        <div className="orders-header">
          <h1>Meus Pedidos</h1>
          <p>Acompanhe seu histórico de pedidos e compras</p>
        </div>

        <div className="orders-filter">
          <span>Filtrar por status:</span>
          <select
            value={statusFilter}
            onChange={(e) => {
              setPage(1);
              setStatusFilter(e.target.value);
            }}>
            <option value="">Todos os status</option>
            <option value="PENDENTE">Pendente</option>
            <option value="PAGO">Pago</option>
            <option value="CANCELADO">Cancelado</option>
          </select>
        </div>

        {pedidos.length === 0 ? (
          <div className="orders-empty">
            <p>📦 Nenhum pedido encontrado com este filtro.</p>
          </div>
        ) : (
          <div className="orders-list">
            {pedidos.map((p, index) => {
              const horas = Number(p.horas_total ?? 0);
              const meses = p.meses_duracao ?? null;
              const titulo =
                p.tipo === "GRADE"
                  ? p.grade_nome || "Grade"
                  : "Grade personalizada";

              const valorAvista =
                p.valor_avista != null ? Number(p.valor_avista) : null;
              const valorTotal = Number(p.valor_total ?? 0);
              const valorMensalComMatricula = getValorMensal(p);

              let textoValor = "";

              if (p.forma_pagamento === "MENSAL") {
                textoValor = `R$ ${valorMensalComMatricula.toFixed(2)}`;
              } else if (
                p.forma_pagamento === "AVISTA" &&
                valorAvista !== null
              ) {
                textoValor = `R$ ${valorAvista.toFixed(2)}`;
              } else if (
                p.forma_pagamento === "PARCELADO" &&
                valorAvista !== null
              ) {
                textoValor = `R$ ${valorAvista.toFixed(2)}`;
              } else {
                textoValor = `R$ ${valorTotal.toFixed(2)}`;
              }

              const statusClass = p.status.toLowerCase();
              const orderNumber = getOrderNumber(index);
              const duracaoFormatada = formatDuracao(meses);

              return (
                <article key={p.id_pedido} className="order-card">
                  <div className="order-header">
                    <div className="order-title-section">
                      <h2>Pedido #{orderNumber}</h2>
                      <p>
                        {titulo} · <strong>{p.modalidade}</strong>
                      </p>
                    </div>
                    <span className={`order-status-badge ${statusClass}`}>
                      {p.status}
                    </span>
                  </div>

                  <div className="order-info-grid">
                    <div className="order-info-item">
                      <span className="order-info-label">Duração</span>
                      <span className="order-info-value">
                        {horas}h{duracaoFormatada && ` · ${duracaoFormatada}`}
                      </span>
                    </div>
                    <div className="order-info-item">
                      <span className="order-info-label">Valor</span>
                      <span className="order-info-value price">
                        {textoValor}
                      </span>
                    </div>
                    <div className="order-info-item">
                      <span className="order-info-label">Pagamento</span>
                      <span className="order-info-value">
                        {formatFormaPagamento(p.forma_pagamento)}
                      </span>
                    </div>
                    <div className="order-info-item">
                      <span className="order-info-label">Data</span>
                      <span className="order-info-value">
                        {new Date(p.criado_em).toLocaleDateString("pt-BR")}
                      </span>
                    </div>
                  </div>

                  <div className="order-actions">
                    <Link
                      to={`/meus-pedidos/${p.id_pedido}`}
                      className="order-btn order-btn-details">
                      <FaEye /> Ver Detalhes
                    </Link>
                  </div>
                </article>
              );
            })}
          </div>
        )}

        {totalPages > 1 && (
          <div className="orders-pagination">
            <button
              onClick={() => canPrev && setPage(page - 1)}
              disabled={!canPrev}>
              ← Anterior
            </button>
            <span>
              Página <span className="current">{page}</span> de{" "}
              <span className="current">{totalPages}</span>
            </span>
            <button
              onClick={() => canNext && setPage(page + 1)}
              disabled={!canNext}>
              Próxima →
            </button>
          </div>
        )}
      </main>
      <Footer />
    </>
  );
}
