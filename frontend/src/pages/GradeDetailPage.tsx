import { Suspense, lazy, useEffect, useState } from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import { Clock, BookOpen, Check } from "lucide-react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Autoplay } from "swiper/modules";

import "swiper/css";
import {
  FaRegFileAlt,
  FaCreditCard,
  FaDollarSign,
  FaWhatsapp,
} from "react-icons/fa";
import api from "../services/api";
import axios from "axios";
import type { Grade } from "../types/api";
import "./GradeDetailPage.css";
import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import VideoModal from "../components/Modal/VideoModal";
import { resolveAssetUrl } from "../utils/assetUrl";
import ClienteForm from "../components/ClienteForm/ClienteForm";
import type { ClienteData } from "../components/ClienteForm/ClienteForm";
import { apiBaseUrl } from "../services/site";

const PDFModal = lazy(() => import("../components/Modal/PDFModal"));

type ModalidadeType = "PRESENCIAL" | "EAD" | null;
type PlanoType = "MENSAL" | "AVISTA" | "PARCELADO" | null;
type LoggedUser = {
  id?: number | null;
  id_usuario?: number | null;
  nome?: string;
  email?: string;
};

export default function GradeDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const [grade, setGrade] = useState<Grade | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [modalidade, setModalidade] = useState<ModalidadeType>(null);
  const [planoSelecionado, setPlanoSelecionado] = useState<PlanoType>(null);
  const [showClienteForm, setShowClienteForm] = useState(false);
  const [clienteLoading, setClienteLoading] = useState(false);
  const [clienteError, setClienteError] = useState<string | null>(null);
  const [usuarioLogado, setUsuarioLogado] = useState<LoggedUser | null>(null);
  const [relatedGrades, setRelatedGrades] = useState<Grade[]>([]);

  const [videoModalOpen, setVideoModalOpen] = useState(false);
  const [pdfModalOpen, setPdfModalOpen] = useState(false);
  const [currentVideoUrl, setCurrentVideoUrl] = useState("");
  const [currentPdfUrl, setCurrentPdfUrl] = useState("");
  const [currentModalTitle, setCurrentModalTitle] = useState("");

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
    if (!slug) return;

    async function loadGrade() {
      try {
        setLoading(true);
        setError(null);
        const response = await api.get(`/grades/${slug}`);
        const rawPayload = response.data as unknown;
        const parsedPayload =
          typeof rawPayload === "string"
            ? JSON.parse(rawPayload.replace(/^\uFEFF/, "").trim())
            : rawPayload;
        setGrade(parsedPayload as Grade);
      } catch {
        setError("Erro ao carregar grade.");
      } finally {
        setLoading(false);
      }
    }

    loadGrade();
  }, [slug]);

  useEffect(() => {
    if (!slug) return;

    async function loadRelatedGrades() {
      try {
        const response = await api.get("/grades");
        const rawPayload = response.data as unknown;
        const parsedPayload =
          typeof rawPayload === "string"
            ? JSON.parse(rawPayload.replace(/^\uFEFF/, "").trim())
            : rawPayload;
        const payload = parsedPayload as
          | Grade[]
          | { data?: Grade[]; success?: boolean };
        const allGrades = Array.isArray(payload)
          ? payload
          : Array.isArray(payload?.data)
            ? payload.data
            : [];

        const filteredGrades = allGrades.filter(
          (item) =>
            item.slug &&
            item.slug !== slug &&
            item.id_grade !== grade?.id_grade,
        );

        const shuffled = [...filteredGrades].sort(() => Math.random() - 0.5);
        setRelatedGrades(shuffled.slice(0, 12));
      } catch {
        setRelatedGrades([]);
      }
    }

    loadRelatedGrades();
  }, [slug, grade?.id_grade]);

  const formatarReal = (valor: number): string => {
    return valor.toLocaleString("pt-BR", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  };

  const renderPricing = () => {
    if (!modalidade || !grade) return null;

    const mensalidadeBaseRaw =
      modalidade === "PRESENCIAL"
        ? grade.valor_mensal_presencial
        : grade.valor_mensal_ead;

    const mensalidadeBase = mensalidadeBaseRaw
      ? parseFloat(String(mensalidadeBaseRaw))
      : 0;

    const matriculaValorRaw =
      grade.valor_matricula ?? grade.matricula_valor ?? null;
    const matriculaValor = matriculaValorRaw
      ? parseFloat(String(matriculaValorRaw))
      : 0;

    const meses = grade.meses_duracao ?? 0;

    if (!mensalidadeBase || meses <= 0) {
      return (
        <div className="pricing-section">
          <p className="text-gray-600">
            Entre em contato para consultar valores.
          </p>
        </div>
      );
    }

    const descontoParceladoRaw =
      (grade as Grade & { desconto_parcelado?: number }).desconto_parcelado ??
      5;
    const descontoAvistaRaw =
      (grade as Grade & { desconto_avista?: number }).desconto_avista ?? 10;
    const descontoParcelado = parseFloat(String(descontoParceladoRaw));
    const descontoAvista = parseFloat(String(descontoAvistaRaw));

    const mensalidadeMensal = mensalidadeBase;

    const valorTotalMensal = mensalidadeMensal * meses + matriculaValor;

    const totalBaseSemDesconto = mensalidadeBase * meses;

    const mensalidadeAvista = mensalidadeBase * (1 - descontoAvista / 100);
    const valorTotalAvista = mensalidadeAvista * meses;

    const mensalidadeParcelado =
      mensalidadeBase * (1 - descontoParcelado / 100);
    const valorTotalParcelado = mensalidadeParcelado * meses;
    const parcelasMax = meses;
    const valorParcela = valorTotalParcelado / parcelasMax;

    const economiaAvista = Math.max(totalBaseSemDesconto - valorTotalAvista, 0);

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
            {matriculaValor > 0 && (
              <p className="sub-price">
                + R$ {formatarReal(matriculaValor)} de matrícula
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
              Duração: {meses} meses
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
              Desconto de {descontoAvista.toFixed(0)}% (economia R${" "}
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
              Sem matrícula
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
              *Desconto promocional de {descontoParcelado.toFixed(0)}%
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
              Sem matrícula
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

  const handleWhatsApp = () => {
    if (!grade) return;
    const message = `Olá! Tenho interesse na grade: ${grade.nome}`;
    window.open(
      `https://wa.me/5535998421176?text=${encodeURIComponent(message)}`,
      "_blank",
    );
  };

  const planoLabelMap: Record<NonNullable<PlanoType>, string> = {
    MENSAL: "Plano Mensal",
    AVISTA: "Plano À Vista",
    PARCELADO: "Plano Parcelado no Cartão",
  };

  const handleComprarAgora = async () => {
    if (!grade || !planoSelecionado) {
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
      await handleClienteFormSubmit(clienteData);
      return;
    }

    setShowClienteForm(true);
  };

  const handleClienteFormSubmit = async (clienteData: ClienteData) => {
    if (!grade || !modalidade || !planoSelecionado || clienteLoading) return;

    setClienteError(null);

    try {
      setClienteLoading(true);

      let idUsuario = usuarioLogado?.id || usuarioLogado?.id_usuario || null;

      if (!idUsuario) {
        const cpfLimpo = (clienteData.cpf || "").replace(/\D/g, "");

        await api.post("/usuarios/verificar", {
          email: clienteData.email,
          cpf: cpfLimpo,
        });

        const usuarioResponse = await api.post("/usuarios/primeiro-acesso", {
          nome: clienteData.nome,
          email: clienteData.email,
          telefone: clienteData.telefone,
          cpf: cpfLimpo,
        });

        idUsuario =
          usuarioResponse.data?.id_usuario || usuarioResponse.data?.id;

        if (!idUsuario) {
          throw new Error("Não foi possível criar o usuário.");
        }
      }

      const meses = grade.meses_duracao ?? 0;
      const mensalidadeBaseRaw =
        modalidade === "PRESENCIAL"
          ? grade.valor_mensal_presencial
          : grade.valor_mensal_ead;
      const mensalidadeBase = mensalidadeBaseRaw
        ? parseFloat(String(mensalidadeBaseRaw))
        : 0;
      const matriculaValorRaw =
        grade.valor_matricula ?? grade.matricula_valor ?? null;
      const matriculaValor = matriculaValorRaw
        ? parseFloat(String(matriculaValorRaw))
        : 0;
      const descontoParceladoRaw =
        (grade as Grade & { desconto_parcelado?: number }).desconto_parcelado ??
        5;
      const descontoAvistaRaw =
        (grade as Grade & { desconto_avista?: number }).desconto_avista ?? 10;
      const descontoParcelado = parseFloat(String(descontoParceladoRaw));
      const descontoAvista = parseFloat(String(descontoAvistaRaw));

      const mensalidadeMensal = mensalidadeBase;
      const mensalidadeAvista = mensalidadeBase * (1 - descontoAvista / 100);
      const valorTotalAvista = mensalidadeAvista * meses;
      const mensalidadeParcelado =
        mensalidadeBase * (1 - descontoParcelado / 100);
      const valorTotalParcelado = mensalidadeParcelado * meses;

      const cargaHorariaTotal =
        grade.cursos?.reduce((total, curso) => {
          const horas = curso.horas || curso.carga_horaria || 0;
          return (
            total + (typeof horas === "string" ? parseFloat(horas) : horas)
          );
        }, 0) ?? 0;

      const pedidoData: Record<string, unknown> & {
        tipo: string;
        id_grade: number;
        grade_nome: string;
        modalidade: Exclude<ModalidadeType, null>;
        forma_pagamento: Exclude<PlanoType, null>;
        meses_duracao: number;
        horas_total: number;
        valor_matricula: number;
        cliente: ClienteData;
        id_usuario: number;
      } = {
        tipo: "GRADE",
        id_grade: grade.id_grade,
        grade_nome: grade.nome,
        modalidade,
        forma_pagamento: planoSelecionado,
        meses_duracao: meses,
        horas_total: cargaHorariaTotal,
        valor_matricula: matriculaValor,
        cliente: clienteData,
        id_usuario: idUsuario,
      };

      if (planoSelecionado === "MENSAL") {
        pedidoData.valor_total = mensalidadeMensal + matriculaValor;
        pedidoData.valor_mensal = mensalidadeMensal;
      } else if (planoSelecionado === "AVISTA") {
        pedidoData.valor_total = valorTotalAvista;
        pedidoData.valor_avista = valorTotalAvista;
      } else if (planoSelecionado === "PARCELADO") {
        pedidoData.valor_total = valorTotalParcelado;
        pedidoData.valor_mensal = mensalidadeParcelado;
      }

      localStorage.setItem("pedido_temp", JSON.stringify(pedidoData));

      setShowClienteForm(false);
      navigate("/checkout/temp");
    } catch (err: unknown) {
      const errorData = err as {
        response?: {
          status?: number;
          data?: { erro?: string; mensagem?: string };
        };
        message?: string;
      };
      if (errorData.response?.status === 409) {
        const msg = "E-mail ou CPF já cadastrados. Favor faça o login.";
        setClienteError(msg);
        alert(msg);
        return;
      }

      const msg =
        errorData.response?.data?.erro ||
        errorData.response?.data?.mensagem ||
        errorData.message ||
        "Erro ao preparar seu pedido.";
      setClienteError(msg);
      alert(msg);
    } finally {
      setClienteLoading(false);
    }
  };

  if (loading) {
    return (
      <>
        <Header />
        <main className="container page-grade-detail">Carregando grade...</main>
        <Footer />
      </>
    );
  }

  if (error) {
    return (
      <>
        <Header />
        <main className="container page-grade-detail">{error}</main>
        <Footer />
      </>
    );
  }

  if (!grade) {
    return (
      <>
        <Header />
        <main className="container page-grade-detail">
          Grade não encontrada.
        </main>
        <Footer />
      </>
    );
  }

  const meses = grade.meses_duracao ?? null;
  const quantidadeCursos = grade.cursos?.length ?? 0;
  const duplicatedRelatedGrades = Array.from(
    { length: 5 },
    () => relatedGrades,
  ).flat();

  // Calcular carga horária total
  const cargaHorariaTotal =
    grade.cursos?.reduce((total, curso) => {
      const horas = curso.horas || curso.carga_horaria || 0;
      return total + (typeof horas === "string" ? parseFloat(horas) : horas);
    }, 0) ?? 0;

  return (
    <>
      <Header />
      <main className="container page-grade-detail">
        {}
        {grade.imagem_detalhe && (
          <div className="grade-hero">
            <img src={resolveAssetUrl(grade.imagem_detalhe)} alt={grade.nome} />
          </div>
        )}

        {/* Nome do curso */}
        <h1 className="page-title">{grade.nome}</h1>

        {}
        <div className="grade-info">
          {cargaHorariaTotal > 0 && (
            <div className="info-item">
              <Clock size={24} className="info-icon" />
              <span>
                <strong>Carga Horária Total:</strong> {cargaHorariaTotal} horas
              </span>
            </div>
          )}
          {meses && (
            <div className="info-item">
              <Clock size={24} className="info-icon" />
              <span>
                <strong>Duração:</strong> {meses} meses
              </span>
            </div>
          )}
          {quantidadeCursos > 0 && (
            <div className="info-item">
              <BookOpen size={24} className="info-icon" />
              <span>
                <strong>Módulos:</strong> {quantidadeCursos}
              </span>
            </div>
          )}
        </div>

        {}
        {grade.descricao_longa_md && (
          <section
            className="grade-description"
            dangerouslySetInnerHTML={{ __html: grade.descricao_longa_md }}
          />
        )}

        {}
        {grade.cursos && grade.cursos.length > 0 && (
          <section className="grade-table-section">
            {}
            <div className="table-wrapper desktop-only">
              <table className="grade-table">
                <thead>
                  <tr>
                    <th>Módulo</th>
                    <th>Carga Horária</th>
                    <th>Aula Demonstrativa</th>
                    <th>Conteúdo Programático</th>
                  </tr>
                </thead>
                <tbody>
                  {grade.cursos.map((curso) => (
                    <tr key={curso.id_curso}>
                      <td>{curso.nome}</td>
                      <td>
                        {curso.horas || curso.carga_horaria
                          ? `${curso.horas || curso.carga_horaria}h`
                          : "-"}
                      </td>
                      <td>
                        {curso.link_aula_demo ? (
                          <button
                            onClick={() => {
                              setCurrentVideoUrl(curso.link_aula_demo!);
                              setCurrentModalTitle(
                                `Aula Demonstrativa - ${curso.nome}`,
                              );
                              setVideoModalOpen(true);
                            }}
                            className="btn-assistir">
                            Assistir
                          </button>
                        ) : (
                          "-"
                        )}
                      </td>
                      <td>
                        {curso.pdf_conteudo ? (
                          <button
                            onClick={() => {
                              setCurrentPdfUrl(curso.pdf_conteudo!);
                              setCurrentModalTitle(
                                `Conteúdo Programático - ${curso.nome}`,
                              );
                              setPdfModalOpen(true);
                            }}
                            className="btn-ver">
                            Ver
                          </button>
                        ) : (
                          "-"
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {}
            <div className="grade-cards mobile-only">
              {grade.cursos.map((curso) => (
                <div key={curso.id_curso} className="grade-course-card">
                  <div className="grade-course-card-header">
                    <h4 className="grade-course-name">{curso.nome}</h4>
                    <span className="grade-course-hours">
                      {curso.horas || curso.carga_horaria
                        ? `${curso.horas || curso.carga_horaria}h`
                        : "-"}
                    </span>
                  </div>
                  <div className="grade-course-card-actions">
                    <div className="grade-action-item">
                      <span className="grade-action-label">
                        Aula Demonstrativa
                      </span>
                      {curso.link_aula_demo ? (
                        <button
                          onClick={() => {
                            setCurrentVideoUrl(curso.link_aula_demo!);
                            setCurrentModalTitle(
                              `Aula Demonstrativa - ${curso.nome}`,
                            );
                            setVideoModalOpen(true);
                          }}
                          className="btn-assistir">
                          Assistir
                        </button>
                      ) : (
                        <span className="no-content">-</span>
                      )}
                    </div>
                    <div className="grade-action-item">
                      <span className="grade-action-label">
                        Conteúdo Programático
                      </span>
                      {curso.pdf_conteudo ? (
                        <button
                          onClick={() => {
                            setCurrentPdfUrl(curso.pdf_conteudo!);
                            setCurrentModalTitle(
                              `Conteúdo Programático - ${curso.nome}`,
                            );
                            setPdfModalOpen(true);
                          }}
                          className="btn-ver">
                          Ver
                        </button>
                      ) : (
                        <span className="no-content">-</span>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Como você quer estudar? */}
        <section className="study-mode-section">
          <h2 className="section-title">Como você quer estudar?</h2>
          <div className="study-mode-buttons">
            <button
              className={`mode-btn ${
                modalidade === "PRESENCIAL" ? "active" : ""
              }`}
              onClick={() => {
                setModalidade("PRESENCIAL");
                setPlanoSelecionado(null);
              }}>
              Presencial
            </button>
            <button
              className={`mode-btn ${modalidade === "EAD" ? "active" : ""}`}
              onClick={() => {
                setModalidade("EAD");
                setPlanoSelecionado(null);
              }}>
              EAD
            </button>
          </div>

          {/* Preços (aparecem apenas após seleção) */}
          {renderPricing()}
        </section>

        {}
        {modalidade && (
          <div className="action-buttons">
            <button
              className="buy-btn"
              disabled={!planoSelecionado}
              onClick={handleComprarAgora}>
              {planoSelecionado
                ? `Prosseguir — ${planoLabelMap[planoSelecionado]}`
                : "Selecione uma grade"}
            </button>
            <button className="whatsapp-action-btn" onClick={handleWhatsApp}>
              <FaWhatsapp /> Estou com dúvidas
            </button>
          </div>
        )}

        <section className="related-grades-section">
          <h2 className="section-title">Conheça outros cursos</h2>
          {relatedGrades.length > 0 ? (
            <Swiper
              modules={[Autoplay]}
              spaceBetween={20}
              slidesPerView="auto"
              loop={true}
              autoplay={{
                delay: 0,
                disableOnInteraction: false,
              }}
              speed={3000}
              freeMode={true}
              className="related-grades-swiper">
              {duplicatedRelatedGrades.map((item, index) => {
                const image = item.imagem_card
                  ? resolveAssetUrl(item.imagem_card)
                  : `/assets/grades/${item.nome.trim()}.jpg`;

                return (
                  <SwiperSlide key={`${item.id_grade}-${index}`}>
                    <Link
                      to={`/grades/${item.slug}`}
                      className="related-grade-card"
                      draggable={false}>
                      <div className="related-grade-thumb">
                        <img src={image} alt={item.nome} draggable={false} />
                      </div>
                      <div className="related-grade-info">
                        <h3 className="related-grade-title">{item.nome}</h3>
                        <p className="related-grade-desc">
                          {item.descricao_curta || `Aprenda sobre ${item.nome}`}
                        </p>
                      </div>
                    </Link>
                  </SwiperSlide>
                );
              })}
            </Swiper>
          ) : (
            <p className="related-empty">
              No momento, nao ha outros cursos para exibir.
            </p>
          )}
        </section>

        <section className="custom-grade-cta-section">
          <h2 className="section-title">
            Não encontrou o que procurava? Monte sua própria grade de curso!
          </h2>
          <button
            className="custom-grade-cta-btn"
            onClick={() => navigate("/monte-sua-grade")}>
            Montar minha grade
          </button>
        </section>
      </main>
      <Footer />

      {/* Modais */}
      <VideoModal
        isOpen={videoModalOpen}
        onClose={() => setVideoModalOpen(false)}
        videoUrl={currentVideoUrl}
        title={currentModalTitle}
      />
      {pdfModalOpen && (
        <Suspense fallback={null}>
          <PDFModal
            isOpen={pdfModalOpen}
            onClose={() => setPdfModalOpen(false)}
            pdfUrl={currentPdfUrl}
            title={currentModalTitle}
          />
        </Suspense>
      )}

      {/* Formulário de Cliente */}
      {showClienteForm && (
        <ClienteForm
          onSubmit={handleClienteFormSubmit}
          onCancel={() => {
            setClienteError(null);
            setShowClienteForm(false);
          }}
          loading={clienteLoading}
          errorMessage={clienteError}
        />
      )}
    </>
  );
}
