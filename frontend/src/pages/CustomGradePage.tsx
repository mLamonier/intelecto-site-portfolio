import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../services/api";
import axios from "axios";
import type { CursoPersonalizavel } from "../types/api";
import { DragDropContext, Droppable, Draggable } from "@hello-pangea/dnd";
import type { DropResult } from "@hello-pangea/dnd";
import { Clock, Trash2, Plus, Check, FileText, X } from "lucide-react";
import {
  FaRegFileAlt,
  FaCreditCard,
  FaDollarSign,
  FaWhatsapp,
} from "react-icons/fa";
import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import ClienteForm from "../components/ClienteForm/ClienteForm";
import type { ClienteData } from "../components/ClienteForm/ClienteForm";
import { ToastContainer } from "../components/Toast/ToastContainer";
import { useToast } from "../hooks/useToast";
import "./CustomGradePage.css";
import "../components/Modal/Modal.css";
import { apiBaseUrl } from "../services/site";

interface ConfigItem {
  chave: string;
  valor: string;
}

type Modalidade = "PRESENCIAL" | "EAD";
type PlanoType = "MENSAL" | "AVISTA" | "PARCELADO" | null;
type LoggedUser = {
  id?: number | null;
  id_usuario?: number | null;
  nome?: string;
  email?: string;
};

export default function CustomGradePage() {
  const navigate = useNavigate();
  const [cursos, setCursos] = useState<CursoPersonalizavel[]>([]);
  const [selecionados, setSelecionados] = useState<CursoPersonalizavel[]>([]);
  const [modalidade, setModalidade] = useState<Modalidade>("PRESENCIAL");
  const [planoSelecionado, setPlanoSelecionado] = useState<PlanoType>(null);
  const [valorMensalPresencial, setValorMensalPresencial] = useState<number>(0);
  const [valorMensalEad, setValorMensalEad] = useState<number>(0);
  const [valorMatricula, setValorMatricula] = useState<number>(0);
  const [loading, setLoading] = useState(true);
  const [showClienteForm, setShowClienteForm] = useState(false);
  const [usuarioLogado, setUsuarioLogado] = useState<LoggedUser | null>(null);
  const [buscaCurso, setBuscaCurso] = useState("");
  const [cursoModal, setCursoModal] = useState<CursoPersonalizavel | null>(null);
  const { toasts, removeToast, success } = useToast();

  useEffect(() => {
    const checkAuth = async () => {
      try {
        const response = await axios.get(`${apiBaseUrl()}/auth-check.php`, {
          withCredentials: true,
        });

        if (response.data.logado) {
          setUsuarioLogado(response.data.usuario);
        }
      } catch {
        void 0;
      }
    };

    checkAuth();
  }, []);

  useEffect(() => {
    async function loadData() {
      try {
        const [cursosRes, configRes] = await Promise.all([
          api.get<CursoPersonalizavel[]>("/cursos-personalizados").catch(() => {
            return { data: [] };
          }),
          api.get<ConfigItem[]>("/configuracoes").catch(() => {
            return { data: [] };
          }),
        ]);

        const rawCursosPayload = cursosRes.data as unknown;
        const parsedCursosPayload =
          typeof rawCursosPayload === "string"
            ? JSON.parse(rawCursosPayload.replace(/^\uFEFF/, "").trim())
            : rawCursosPayload;
        const cursosPayload = parsedCursosPayload as
          | CursoPersonalizavel[]
          | {
              data?: CursoPersonalizavel[];
            };
        const cursosData = Array.isArray(cursosPayload)
          ? cursosPayload
          : Array.isArray(cursosPayload?.data)
            ? cursosPayload.data
            : [];
        setCursos(cursosData);

        const rawConfigPayload = configRes.data as unknown;
        const parsedConfigPayload =
          typeof rawConfigPayload === "string"
            ? JSON.parse(rawConfigPayload.replace(/^\uFEFF/, "").trim())
            : rawConfigPayload;
        const configPayload = parsedConfigPayload as
          | ConfigItem[]
          | {
              data?: ConfigItem[];
            };
        const configData = Array.isArray(configPayload)
          ? configPayload
          : Array.isArray(configPayload?.data)
            ? configPayload.data
            : [];

        let presencial = 200;
        let ead = 180;
        let matricula = 0;

        configData.forEach((item) => {
          if (item.chave === "VALOR_MENSAL_PRESENCIAL_PADRAO") {
            presencial = Number(item.valor) || 200;
          }
          if (item.chave === "VALOR_MENSAL_EAD_PADRAO") {
            ead = Number(item.valor) || 180;
          }
          if (item.chave === "VALOR_MATRICULA_PADRAO") {
            matricula = Number(item.valor) || 0;
          }
        });

        setValorMensalPresencial(presencial);
        setValorMensalEad(ead);
        setValorMatricula(matricula);
      } catch {
        setValorMensalPresencial(200);
        setValorMensalEad(180);
        setValorMatricula(0);
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, []);

  function onDragEnd(result: DropResult) {
    if (!result.destination) return;

    const fromIndex = result.source.index;
    const toIndex = result.destination.index;

    setSelecionados((prev) => {
      const novaLista = [...prev];
      const [removido] = novaLista.splice(fromIndex, 1);
      novaLista.splice(toIndex, 0, removido);
      return novaLista;
    });
  }

  const horasTotal = selecionados.reduce(
    (total, curso) => total + curso.horas,
    0,
  );

  const calcularDuracaoMeses = (horas: number): number => {
    if (horas === 0) return 0;
    const duracao = horas / 16;
    const arredondado = Math.ceil(duracao);
    return arredondado + 1;
  };

  const duracaoMeses = calcularDuracaoMeses(horasTotal);
  const valorMensalAtual =
    modalidade === "PRESENCIAL" ? valorMensalPresencial : valorMensalEad;
  const valorBase = duracaoMeses * valorMensalAtual;

  const mensalidadeMensal = valorMensalAtual;
  const valorTotalMensal = mensalidadeMensal * duracaoMeses + valorMatricula;
  const descontoAvistaValor = 10;
  const descontoParceladoValor = 5;
  const totalBaseSemDesconto = valorBase;
  const mensalidadeAvista = valorMensalAtual * (1 - descontoAvistaValor / 100);
  const valorTotalAvista = mensalidadeAvista * duracaoMeses;
  const mensalidadeParcelado =
    valorMensalAtual * (1 - descontoParceladoValor / 100);
  const valorTotalParcelado = mensalidadeParcelado * duracaoMeses;
  const parcelasMax = duracaoMeses;
  const valorParcela = duracaoMeses > 0 ? valorTotalParcelado / parcelasMax : 0;
  const economiaAvista = Math.max(totalBaseSemDesconto - valorTotalAvista, 0);

  const formatarReal = (valor: number): string => {
    return valor.toLocaleString("pt-BR", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  };

  const pluralizarMes = (quantidade: number): string => {
    return quantidade === 1 ? "mês" : "meses";
  };

  function adicionarCurso(curso: CursoPersonalizavel) {
    const jaExiste = selecionados.some((c) => c.id_curso === curso.id_curso);
    if (jaExiste) return;

    setSelecionados((prev) => [...prev, curso]);
    success("Curso adicionado", `"${curso.nome}" foi adicionado a sua grade.`);
  }

  function removerCurso(id_curso: number) {
    setSelecionados((prev) => prev.filter((c) => c.id_curso !== id_curso));
  }
  const openCursoModal = (curso: CursoPersonalizavel) => setCursoModal(curso);
  const closeCursoModal = () => setCursoModal(null);

  const cursosDisponiveis = cursos.filter(
    (curso) =>
      curso.pode_montar_grade &&
      !selecionados.some((sel) => sel.id_curso === curso.id_curso),
  );
  const buscaNormalizada = buscaCurso.trim().toLowerCase();
  const cursosDisponiveisFiltrados = cursosDisponiveis.filter((curso) =>
    curso.nome.toLowerCase().includes(buscaNormalizada),
  );

  const renderPricing = () => {
    if (horasTotal === 0) {
      return (
        <div className="pricing-section">
          <p className="text-gray-600">
            Selecione cursos para ver os planos de pagamento.
          </p>
        </div>
      );
    }

    return (
      <div className="pricing-section">
        {}
        <label
          className={`plan-card ${
            planoSelecionado === "MENSAL" ? "active" : ""
          }`}>
          <input
            type="radio"
            name="plano"
            value="MENSAL"
            checked={planoSelecionado === "MENSAL"}
            onChange={() => setPlanoSelecionado("MENSAL")}
            style={{ display: "none" }}
          />
          <div className="plan-header">
            <h3>
              <FaRegFileAlt
                size={20}
                style={{ display: "inline", marginRight: "8px" }}
              />
              PLANO MENSAL
            </h3>
          </div>
          <div className="plan-price">
            <p className="main-price">
              R$ {formatarReal(mensalidadeMensal)}/mês
            </p>
            {valorMatricula > 0 && (
              <p className="sub-price">
                + R$ {formatarReal(valorMatricula)} de matrícula
              </p>
            )}
            <p className="sub-price">
              Total: R$ {formatarReal(valorTotalMensal)}
            </p>
          </div>
          <ul className="plan-benefits">
            <li>
              <Check size={20} className="check-icon" />2 aulas por semana (4h
              semanais)
            </li>
            <li>
              <Check size={20} className="check-icon" />
              Duração: {duracaoMeses} meses
            </li>
            <li>
              <Check size={20} className="check-icon" />
              Flexibilidade de gastos mensais
            </li>
            <li>
              <Check size={20} className="check-icon" />
              Ideal para quem prefere pagar mensalmente
            </li>
          </ul>
        </label>

        {/* PLANO À VISTA */}
        <label
          className={`plan-card ${
            planoSelecionado === "AVISTA" ? "active" : ""
          }`}>
          <input
            type="radio"
            name="plano"
            value="AVISTA"
            checked={planoSelecionado === "AVISTA"}
            onChange={() => setPlanoSelecionado("AVISTA")}
            style={{ display: "none" }}
          />
          <div className="plan-header">
            <h3>
              <FaDollarSign
                size={20}
                style={{ display: "inline", marginRight: "8px" }}
              />
              PAGAMENTO À VISTA
            </h3>
            <span className="plan-badge benefit">Melhor custo-benefício</span>
          </div>
          <div className="plan-price">
            <p
              className="original-price"
              style={{
                textDecoration: "line-through",
                color: "#999",
                fontSize: "0.9rem",
                marginBottom: "4px",
              }}>
              De: R$ {formatarReal(totalBaseSemDesconto)}
            </p>
            <p className="main-price">
              Por: R$ {formatarReal(valorTotalAvista)}
            </p>
            <p className="sub-price">
              Desconto de {descontoAvistaValor.toFixed(0)}% (economia R${" "}
              {formatarReal(economiaAvista)})
            </p>
          </div>
          <ul className="plan-benefits">
            <li>
              <Check size={20} className="check-icon" />
              Aulas ILIMITADAS por semana
            </li>
            <li>
              <Check size={20} className="check-icon" />
              Termine no seu ritmo (até 50% mais rápido)
            </li>
            <li>
              <Check size={20} className="check-icon" />
              Melhor custo-benefício total
            </li>
          </ul>
        </label>

        {/* PLANO PARCELADO */}
        <label
          className={`plan-card ${
            planoSelecionado === "PARCELADO" ? "active" : ""
          }`}>
          <input
            type="radio"
            name="plano"
            value="PARCELADO"
            checked={planoSelecionado === "PARCELADO"}
            onChange={() => setPlanoSelecionado("PARCELADO")}
            style={{ display: "none" }}
          />
          <div className="plan-header">
            <h3>
              <FaCreditCard
                size={20}
                style={{ display: "inline", marginRight: "8px" }}
              />
              PARCELADO NO CARTÃO
            </h3>
            <span className="plan-badge best">Melhor valor</span>
          </div>
          <div className="plan-price">
            <p className="main-price">
              {parcelasMax}x de R$ {formatarReal(valorParcela)}
            </p>
            <p className="sub-price">
              Total: R$ {formatarReal(valorTotalParcelado)}
            </p>
            <p className="tax-info">
              *Desconto promocional de {descontoParceladoValor.toFixed(0)}%
            </p>
          </div>
          <ul className="plan-benefits">
            <li>
              <Check size={20} className="check-icon" />3 aulas por semana (6h
              semanais)
            </li>
            <li>
              <Check size={20} className="check-icon" />
              Termine em 25% mais rápido!
            </li>
            <li>
              <Check size={20} className="check-icon" />
              Parcele em até {parcelasMax}x sem juros
            </li>
          </ul>
        </label>
      </div>
    );
  };

  async function handleFinalizarGradePersonalizada() {
    if (selecionados.length === 0) {
      alert("Selecione ao menos um curso.");
      return;
    }

    if (!planoSelecionado) {
      alert("Selecione um plano de pagamento.");
      return;
    }

    if (usuarioLogado) {
      const clienteData: ClienteData = {
        nome: usuarioLogado.nome || "",
        email: usuarioLogado.email || "",
        telefone: "",
        cpf: "",
      };
      handleClienteFormSubmit(clienteData);
      return;
    }

    setShowClienteForm(true);
  }

  const handleClienteFormSubmit = (clienteData: ClienteData) => {
    if (!planoSelecionado) return;
    const idUsuarioLogado =
      usuarioLogado?.id || usuarioLogado?.id_usuario || null;

    const pedidoData: Record<string, unknown> & {
      tipo: string;
      modalidade: Modalidade;
      forma_pagamento: NonNullable<PlanoType>;
      horas_total: number;
      meses_duracao: number;
      valor_matricula: number;
      cursos: Array<{ id_curso: number; horas: number }>;
      cliente: ClienteData;
      id_usuario: number | null;
    } = {
      tipo: "PERSONALIZADA",
      modalidade,
      forma_pagamento: planoSelecionado,
      horas_total: horasTotal,
      meses_duracao: duracaoMeses,
      valor_matricula: valorMatricula,
      cursos: selecionados.map((curso) => ({
        id_curso: curso.id_curso,
        horas: curso.horas,
      })),
      cliente: clienteData,
      id_usuario: idUsuarioLogado,
    };

    if (planoSelecionado === "MENSAL") {
      pedidoData.valor_total = mensalidadeMensal + valorMatricula;
      pedidoData.valor_mensal = mensalidadeMensal;
    } else if (planoSelecionado === "AVISTA") {
      pedidoData.valor_total = valorTotalAvista;
      pedidoData.valor_avista = valorTotalAvista;
    } else if (planoSelecionado === "PARCELADO") {
      pedidoData.valor_total = valorTotalParcelado;
      pedidoData.valor_mensal = mensalidadeParcelado;
    }

    localStorage.setItem("pedido_temp", JSON.stringify(pedidoData));

    navigate("/checkout/temp");
  };
  const planoLabelMap: Record<NonNullable<PlanoType>, string> = {
    MENSAL: "Plano Mensal",
    AVISTA: "Plano À Vista",
    PARCELADO: "Plano Parcelado no Cartão",
  };

  const handleWhatsApp = () => {
    const message = "Olá! Tenho interesse em montar uma grade personalizada";
    window.open(
      `https://wa.me/5535998421176?text=${encodeURIComponent(message)}`,
      "_blank",
    );
  };

  if (loading) {
    return (
      <>
        <Header />
        <main className="page-custom-grade">
          <div className="container">
            <p className="text-center">Carregando cursos...</p>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main className="page-custom-grade">
        <div className="custom-grade-container">
          <div className="custom-grade-header">
            <h1>Monte sua Grade Personalizada</h1>
            <p className="subtitle">
              Escolha os cursos que mais combinam com você e personalize sua
              formação
            </p>
          </div>

          <div className="custom-grade-content">
            {/* Sidebar com cursos disponíveis */}
            <aside className="courses-sidebar">
              <div className="sidebar-header">
                <h2>Cursos Disponíveis</h2>
                <span className="course-count">{cursosDisponiveisFiltrados.length}</span>
              </div>

              <input
                type="text"
                className="course-search-input"
                value={buscaCurso}
                onChange={(e) => setBuscaCurso(e.target.value)}
                placeholder="Filtrar curso por nome"
                aria-label="Filtrar cursos disponíveis"
              />
              <div className="courses-list">
                {cursosDisponiveis.length === 0 ? (
                  <p className="no-courses">
                    Todos os cursos foram adicionados!
                  </p>
                ) : cursosDisponiveisFiltrados.length === 0 ? (
                  <p className="no-courses">
                    Nenhum curso encontrado para esse nome.
                  </p>
                ) : (
                  cursosDisponiveisFiltrados.map((curso) => (
                    <div key={curso.id_curso} className="course-item">
                      <div className="course-item-info">
                        <h3>{curso.nome}</h3>
                        <div className="course-item-meta">
                          <Clock size={14} />
                          <span>{curso.horas}h</span>
                        </div>
                        <p className="course-description">
                          {curso.descricao_curta}
                        </p>
                        <button
                          type="button"
                          className="course-more-link"
                          onClick={() => openCursoModal(curso)}>
                          Saiba mais
                        </button>
                      </div>
                      <button
                        onClick={() => adicionarCurso(curso)}
                        className="btn-add-course"
                        title="Adicionar curso">
                        <Plus size={18} />
                      </button>
                    </div>
                  ))
                )}
              </div>
            </aside>

            {}
            <div className="main-content">
              {}
              <section className="study-mode-section">
                <h2>Escolha a Modalidade</h2>
                <div className="study-mode-buttons">
                  <button
                    className={`mode-btn ${
                      modalidade === "PRESENCIAL" ? "active" : ""
                    }`}
                    onClick={() => setModalidade("PRESENCIAL")}>
                    Presencial
                    <br />
                    <small>R$ {formatarReal(valorMensalPresencial)}/mês</small>
                  </button>
                  <button
                    className={`mode-btn ${
                      modalidade === "EAD" ? "active" : ""
                    }`}
                    onClick={() => setModalidade("EAD")}>
                    EAD
                    <br />
                    <small>R$ {formatarReal(valorMensalEad)}/mês</small>
                  </button>
                </div>
              </section>

              {}
              <section className="selected-courses-section">
                <div className="section-header">
                  <h2>Sua Grade Personalizada</h2>
                  {selecionados.length > 0 && (
                    <span className="courses-count-badge">
                      {selecionados.length} curso(s)
                    </span>
                  )}
                </div>
                <p className="section-note">
                  Você pode reordenar a grade clicando e segurando em cima dela.
                </p>

                {selecionados.length === 0 ? (
                  <div className="empty-state">
                    <FileText size={48} />
                    <p>Nenhum curso selecionado</p>
                    <small>Adicione cursos do menu ao lado para começar</small>
                  </div>
                ) : (
                  <DragDropContext onDragEnd={onDragEnd}>
                    <Droppable droppableId="gradeSelecionada">
                      {(provided, snapshot) => (
                        <ul
                          ref={provided.innerRef}
                          {...provided.droppableProps}
                          className={`selected-courses-list ${
                            snapshot.isDraggingOver ? "dragging-over" : ""
                          }`}>
                          {selecionados.map((curso, index) => (
                            <Draggable
                              key={curso.id_curso}
                              draggableId={String(curso.id_curso)}
                              index={index}>
                              {(dragProvided, dragSnapshot) => (
                                <li
                                  ref={dragProvided.innerRef}
                                  {...dragProvided.draggableProps}
                                  {...dragProvided.dragHandleProps}
                                  className={`selected-course-item ${
                                    dragSnapshot.isDragging ? "dragging" : ""
                                  }`}
                                  style={dragProvided.draggableProps.style}>
                                  <div className="drag-handle">::</div>
                                  <div className="course-details">
                                    <h3>{curso.nome}</h3>
                                    <div className="course-meta">
                                      <span className="hours">
                                        <Clock size={14} /> {curso.horas}h
                                      </span>
                                    </div>
                                  </div>
                                  <button
                                    onClick={() => removerCurso(curso.id_curso)}
                                    className="btn-remove"
                                    title="Remover curso">
                                    <Trash2 size={16} />
                                  </button>
                                </li>
                              )}
                            </Draggable>
                          ))}
                          {provided.placeholder}
                        </ul>
                      )}
                    </Droppable>
                  </DragDropContext>
                )}
              </section>

              {/* Resumo */}
              {selecionados.length > 0 && (
                <section className="summary-section">
                  <div className="summary-box">
                    <div className="summary-item">
                      <span>Total de Horas:</span>
                      <strong>{horasTotal}h</strong>
                    </div>
                    <div className="summary-item">
                      <span>Duração:</span>
                      <strong>
                        {duracaoMeses} {pluralizarMes(duracaoMeses)}
                      </strong>
                    </div>
                    <div className="summary-item highlight">
                      <span>
                        Valor Base ({duracaoMeses}x R${" "}
                        {formatarReal(valorMensalAtual)}):
                      </span>
                      <strong>R$ {formatarReal(valorBase)}</strong>
                    </div>
                  </div>
                </section>
              )}

              {}
              {selecionados.length > 0 && (
                <section className="pricing-section-wrapper">
                  <h2>Planos de Pagamento</h2>
                  {renderPricing()}
                </section>
              )}

              {}
              {selecionados.length > 0 && planoSelecionado && (
                <div className="action-buttons">
                  <button
                    onClick={handleFinalizarGradePersonalizada}
                    className="buy-btn">
                    {`Finalizar e Enviar — ${planoLabelMap[planoSelecionado]}`}
                  </button>
                  <button
                    className="whatsapp-action-btn"
                    onClick={handleWhatsApp}>
                    <FaWhatsapp /> Estou com dúvidas
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </main>
      <Footer />

      {}
      {cursoModal && (
        <div className="modal-overlay" onClick={closeCursoModal}>
          <div
            className="modal-content course-modal"
            onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{cursoModal.nome}</h3>
              <button
                className="modal-close"
                onClick={closeCursoModal}
                aria-label="Fechar modal">
                <X size={20} />
              </button>
            </div>
            <div className="modal-body">
              <div className="course-modal-content">
                <div className="course-modal-meta">
                  <Clock size={16} />
                  <span>{cursoModal.horas}h</span>
                </div>
                <div className="course-modal-description">
                  {(cursoModal.descricao_longa_md &&
                    cursoModal.descricao_longa_md.trim()) ||
                    cursoModal.descricao_curta}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
      {}
      {showClienteForm && (
        <ClienteForm
          onSubmit={handleClienteFormSubmit}
          onCancel={() => setShowClienteForm(false)}
        />
      )}
      <ToastContainer toasts={toasts} onRemove={removeToast} />
    </>
  );
}
