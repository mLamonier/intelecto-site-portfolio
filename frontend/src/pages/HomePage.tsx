import { useEffect, useState } from "react";
import { useLocation } from "react-router-dom";
import type { Grade } from "../types/api";
import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import BannerCarousel from "../components/Home/BannerCarousel";
import GradesCarousel from "../components/Home/GradesCarousel";
import CTASection from "../components/Home/CTASection";
import StatsSection from "../components/Home/StatsSection";
import CategoriesSection from "../components/Home/CategoriesSection";
import TestimonialsSection from "../components/Home/TestimonialsSection";
import FAQSection from "../components/Home/FAQSection";
import { startLoading, stopLoading } from "../utils/globalLoader";
import { apiBaseUrl } from "../services/site";

const HOME_GRADES_LIMIT = 8;
const HOME_GRADES_CACHE_KEY = "home:grades:min:v1";
const HOME_GRADES_FETCH_TIMEOUT_MS = 3000;
const HOME_GRADES_BOOTSTRAP: Grade[] = [
  {
    id_grade: 27,
    nome: "Assistente Contábil",
    slug: "assistente-contabil",
    descricao_curta:
      "Formação completa para atuar na rotina contábil e administrativa, com domínio de planilhas, relatórios, finanças e gestão de pessoas.",
    imagem_card: "frontend/public/assets/grades/Assistente Contábil.jpg",
  },
  {
    id_grade: 29,
    nome: "Atendente de Farmácia",
    slug: "atendente-farmacia",
    descricao_curta:
      "Preparação prática para o balcão e o caixa: atendimento, rotinas de farmácia, operações do ponto de venda e organização de estoque.",
    imagem_card: "frontend/public/assets/grades/Atendente de Farmácia.jpg",
  },
  {
    id_grade: 31,
    nome: "Desenvolvedor de Jogos",
    slug: "desenvolvedor-jogos",
    descricao_curta:
      "Caminho completo para iniciar em games: criação com HTML5 e Unity, fundamentos de programação, banco de dados e modelagem 3D.",
    imagem_card: "frontend/public/assets/grades/Desenvolvedor de Jogos.jpg",
  },
  {
    id_grade: 32,
    nome: "Designer de Interiores",
    slug: "designer-interiores",
    descricao_curta:
      "Projetos e visualização 3D para interiores: Promob, SketchUp e 3ds Max para criar, modelar e apresentar ambientes com mais profissionalismo.",
    imagem_card: "frontend/public/assets/grades/Designer de Interiores.jpg",
  },
  {
    id_grade: 33,
    nome: "Designer Gráfico",
    slug: "designer-grafico",
    descricao_curta:
      "Formação completa em design e conteúdo: artes para redes sociais, identidade visual, edição de imagens, diagramação e criação de vídeos com motion.",
    imagem_card: "frontend/public/assets/grades/Designer Gráfico.jpg",
  },
  {
    id_grade: 34,
    nome: "Excel Avançado",
    slug: "excel-avancado",
    descricao_curta:
      "Do Excel ao Power BI: aprenda a organizar dados, usar funções avançadas e transformar planilhas em dashboards claros e profissionais.",
    imagem_card: "frontend/public/assets/grades/Excel Avançado.jpg",
  },
  {
    id_grade: 35,
    nome: "Gestor Empresarial",
    slug: "gestor-empresarial",
    descricao_curta:
      "Visão completa de gestão para o dia a dia: atendimento, liderança, finanças, logística, pessoas e ferramentas digitais para organizar e crescer resultados.",
    imagem_card: "frontend/public/assets/grades/Gestor Empresarial.jpg",
  },
  {
    id_grade: 30,
    nome: "Influencer Digital",
    slug: "influencer-digital",
    descricao_curta:
      "Aprenda a criar conteúdo que engaja e vende, com design no Canva, estratégias de marketing digital e fundamentos de propaganda e comportamento do consumidor.",
    imagem_card: "frontend/public/assets/grades/Influencer Digital.jpg",
  },
];

function normalizeGradesPayload(payload: unknown): Grade[] {
  if (typeof payload === "string") {
    try {
      const trimmed = payload.replace(/^\uFEFF/, "").trim();
      const parsed = JSON.parse(trimmed) as unknown;
      return normalizeGradesPayload(parsed);
    } catch {
      return [];
    }
  }

  if (Array.isArray(payload)) {
    return payload as Grade[];
  }

  if (
    payload &&
    typeof payload === "object" &&
    Array.isArray((payload as { data?: unknown }).data)
  ) {
    return (payload as { data: Grade[] }).data;
  }

  return [];
}

function areEquivalentHomeGrades(current: Grade[], incoming: Grade[]): boolean {
  if (!Array.isArray(current) || !Array.isArray(incoming)) {
    return false;
  }

  if (current.length === 0 || incoming.length === 0) {
    return false;
  }

  if (current.length !== incoming.length) {
    return false;
  }

  const compareCount = Math.min(
    HOME_GRADES_LIMIT,
    current.length,
    incoming.length,
  );

  for (let index = 0; index < compareCount; index += 1) {
    const currentGrade = current[index];
    const incomingGrade = incoming[index];

    if (!currentGrade || !incomingGrade) {
      return false;
    }

    if (currentGrade.id_grade !== incomingGrade.id_grade) {
      return false;
    }

    if ((currentGrade.slug ?? "") !== (incomingGrade.slug ?? "")) {
      return false;
    }

    if ((currentGrade.imagem_card ?? "") !== (incomingGrade.imagem_card ?? "")) {
      return false;
    }

    if ((currentGrade.nome ?? "") !== (incomingGrade.nome ?? "")) {
      return false;
    }

    if (
      (currentGrade.descricao_curta ?? "") !==
      (incomingGrade.descricao_curta ?? "")
    ) {
      return false;
    }
  }

  return true;
}

async function fetchGradesPayload(
  path: string,
  signal?: AbortSignal,
  timeoutMs = 5000,
): Promise<unknown> {
  const timeoutController = new AbortController();
  const timeoutId = window.setTimeout(() => {
    timeoutController.abort("timeout");
  }, timeoutMs);

  const onAbort = () => {
    timeoutController.abort(signal?.reason);
  };

  if (signal) {
    if (signal.aborted) {
      onAbort();
    } else {
      signal.addEventListener("abort", onAbort, { once: true });
    }
  }

  try {
    const response = await fetch(`${apiBaseUrl()}${path}`, {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
      signal: timeoutController.signal,
    });

    if (!response.ok && response.status !== 304) {
      throw new Error(`Falha ao carregar grades: ${response.status}`);
    }

    const text = await response.text();
    if (!text) {
      return [];
    }

    try {
      return JSON.parse(text) as unknown;
    } catch {
      return text;
    }
  } finally {
    window.clearTimeout(timeoutId);
    if (signal) {
      signal.removeEventListener("abort", onAbort);
    }
  }
}

export default function HomePage() {
  const [grades, setGrades] = useState<Grade[]>(HOME_GRADES_BOOTSTRAP);
  const [loadingGrades, setLoadingGrades] = useState(false);
  const [gradesError, setGradesError] = useState<string | null>(null);
  const [showDeferredSections, setShowDeferredSections] = useState(false);
  const location = useLocation();

  useEffect(() => {
    const abortController = new AbortController();
    let isCancelled = false;
    let revalidateIdleCallbackId: number | null = null;
    let revalidateTimeoutId: number | null = null;

    const cachedGrades = (() => {
      try {
        const raw = window.localStorage.getItem(HOME_GRADES_CACHE_KEY);
        if (!raw) return [];
        return normalizeGradesPayload(JSON.parse(raw) as unknown).slice(
          0,
          HOME_GRADES_LIMIT,
        );
      } catch {
        return [];
      }
    })();

    if (cachedGrades.length > 0) {
      setGrades(cachedGrades);
      setLoadingGrades(false);
    }

    const loadHomeGrades = async () => {
      try {
        const homePayload = await fetchGradesPayload(
          `/grades?home=1&limit=${HOME_GRADES_LIMIT}`,
          abortController.signal,
          HOME_GRADES_FETCH_TIMEOUT_MS,
        );
        const homeGrades = normalizeGradesPayload(homePayload).slice(
          0,
          HOME_GRADES_LIMIT,
        );

        if (!isCancelled && homeGrades.length > 0) {
          setGrades((currentGrades) => {
            if (areEquivalentHomeGrades(currentGrades, homeGrades)) {
              return currentGrades;
            }

            return homeGrades;
          });
          setGradesError(null);

          window.localStorage.setItem(
            HOME_GRADES_CACHE_KEY,
            JSON.stringify(homeGrades),
          );
        }
      } catch {
        if (
          !isCancelled &&
          cachedGrades.length === 0 &&
          HOME_GRADES_BOOTSTRAP.length === 0
        ) {
          setGradesError("Erro ao carregar grades");
        }
      } finally {
        if (!isCancelled) {
          setLoadingGrades(false);
        }
      }
    };

    const revalidateAllGrades = async () => {
      try {
        const allPayload = await fetchGradesPayload(
          "/grades?ativo=1&per_page=24",
          abortController.signal,
        );
        const allGrades = normalizeGradesPayload(allPayload);
        if (!isCancelled && allGrades.length > 0) {
          setGrades(allGrades);
        }
      } catch {
        // Keep UI with already available grades.
      }
    };

    const scheduleBackgroundRevalidation = () => {
      const run = () => {
        void revalidateAllGrades();
      };

      revalidateTimeoutId = window.setTimeout(() => {
        if (typeof window.requestIdleCallback === "function") {
          revalidateIdleCallbackId = window.requestIdleCallback(run, {
            timeout: 4000,
          });
          return;
        }
        run();
      }, 5000);
    };

    void loadHomeGrades().finally(scheduleBackgroundRevalidation);

    return () => {
      isCancelled = true;
      abortController.abort();
      if (revalidateTimeoutId !== null) {
        window.clearTimeout(revalidateTimeoutId);
      }
      if (
        revalidateIdleCallbackId !== null &&
        typeof window.cancelIdleCallback === "function"
      ) {
        window.cancelIdleCallback(revalidateIdleCallbackId);
      }
    };
  }, []);

  useEffect(() => {
    let timeoutId: number | null = null;
    let idleCallbackId: number | null = null;

    const revealSections = () => {
      setShowDeferredSections(true);
    };

    timeoutId = window.setTimeout(() => {
      if (typeof window.requestIdleCallback === "function") {
        idleCallbackId = window.requestIdleCallback(revealSections, {
          timeout: 4000,
        });
        return;
      }
      revealSections();
    }, 2200);

    return () => {
      if (timeoutId !== null) {
        window.clearTimeout(timeoutId);
      }
      if (
        idleCallbackId !== null &&
        typeof window.cancelIdleCallback === "function"
      ) {
        window.cancelIdleCallback(idleCallbackId);
      }
    };
  }, []);

  const content = (
    <>
      <BannerCarousel />
      <GradesCarousel
        grades={grades}
        loading={loadingGrades}
        error={gradesError}
      />
      <CTASection />
      {showDeferredSections ? (
        <>
          <StatsSection />
          <CategoriesSection />
          <TestimonialsSection />
          <FAQSection />
        </>
      ) : null}
    </>
  );

  // Rola suavemente até uma âncora (#duvidas, #depoimentos etc.)
  useEffect(() => {
    if (loadingGrades) return;

    const hash = location.hash.replace("#", "");
    if (!hash) return;

    const scrollToHash = () => {
      const target = document.getElementById(hash);
      if (target) {
        startLoading();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
        setTimeout(stopLoading, 200);
      }
    };

    requestAnimationFrame(() => {
      setTimeout(scrollToHash, 50);
    });
  }, [location.hash, loadingGrades]);

  return (
    <>
      <Header />
      {content}
      <Footer />
    </>
  );
}
