import { useEffect, useState } from "react";
import api from "../services/api";
import type { Grade } from "../types/api";
import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import { Link, useSearchParams } from "react-router-dom";
import { resolveAssetUrl } from "../utils/assetUrl";
import "./CoursesPage.css";

export default function CoursesPage() {
  const [grades, setGrades] = useState<Grade[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchParams] = useSearchParams();
  const categoriaParam = searchParams.get("categoria");
  const [categoria, setCategoria] = useState<{
    nome: string;
    descricao: string;
  } | null>(null);

  useEffect(() => {
    async function loadGrades() {
      try {
        const params: Record<string, string | number> = { ativo: 1 };
        if (categoriaParam) params.categoria = categoriaParam;

        const response = await api.get("/grades", {
          params,
        });
        const rawPayload = response.data as unknown;
        const parsedPayload =
          typeof rawPayload === "string"
            ? JSON.parse(rawPayload.replace(/^\uFEFF/, "").trim())
            : rawPayload;
        const payload = parsedPayload as Grade[] | { data?: Grade[] };
        const gradesData = Array.isArray(payload)
          ? payload
          : Array.isArray(payload?.data)
            ? payload.data
            : [];
        const activeGrades = gradesData.filter((grade) => {
          const ativo = (
            grade as Grade & { ativo?: number | string | boolean | null }
          ).ativo;
          return (
            ativo === undefined ||
            ativo === null ||
            ativo === 1 ||
            ativo === "1" ||
            ativo === true
          );
        });
        setGrades(activeGrades);
      } catch {
        setError("Erro ao carregar grades");
      } finally {
        setLoading(false);
      }
    }

    loadGrades();
  }, [categoriaParam]);

  useEffect(() => {
    async function loadCategoria() {
      if (!categoriaParam) {
        setCategoria(null);
        return;
      }

      try {
        const response = await api.get(`/categorias/${categoriaParam}`);
        const rawPayload = response.data as unknown;
        const parsedPayload =
          typeof rawPayload === "string"
            ? JSON.parse(rawPayload.replace(/^\uFEFF/, "").trim())
            : rawPayload;
        const data = parsedPayload as { nome?: string; descricao?: string };
        setCategoria({
          nome: data.nome ?? "",
          descricao: data.descricao || "Conheça os cursos desta categoria",
        });
      } catch {
        setCategoria(null);
      }
    }

    loadCategoria();
  }, [categoriaParam]);

  if (loading) {
    return (
      <>
        <Header />
        <main
          className="container"
          style={{ padding: "60px 20px", textAlign: "center" }}>
          Carregando cursos...
        </main>
        <Footer />
      </>
    );
  }

  if (error) {
    return (
      <>
        <Header />
        <main
          className="container"
          style={{ padding: "60px 20px", textAlign: "center" }}>
          {error}
        </main>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main className="courses-page">
        <div className="courses-hero">
          <div className="container">
            <h1>
              {categoria ? `Cursos na área de ${categoria.nome}` : "Cursos"}
            </h1>
            <p>
              {categoria
                ? categoria.descricao
                : "Conheça toda a nossa metodologia disponível para você"}
            </p>
          </div>
        </div>

        <section className="courses-section">
          <div className="container">
            <div className="courses-grid">
              {grades.length === 0 && (
                <p style={{ gridColumn: "1 / -1", textAlign: "center" }}>
                  Nenhum curso encontrado para esta categoria.
                </p>
              )}
              {grades.map((grade) => (
                <Link
                  to={`/grades/${grade.slug || grade.id_grade}`}
                  key={grade.id_grade}
                  className="course-card-link">
                  <div className="course-card">
                    <div className="course-image">
                      <img
                        src={
                          grade.imagem_card
                            ? resolveAssetUrl(grade.imagem_card)
                            : `/assets/grades/${grade.nome}.jpg`
                        }
                        alt={grade.nome}
                        onError={(e) => {
                          (e.currentTarget as HTMLImageElement).src =
                            `/assets/grades/Operador de Micro.jpg`;
                        }}
                      />
                    </div>
                    <div className="course-content">
                      <h3 className="courses-page-title">{grade.nome}</h3>
                      {grade.descricao_curta && (
                        <p className="courses-page-description">
                          {grade.descricao_curta}
                        </p>
                      )}
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </>
  );
}
