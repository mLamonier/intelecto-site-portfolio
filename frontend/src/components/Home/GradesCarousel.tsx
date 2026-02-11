import {
  memo,
  useEffect,
  useMemo,
  useState,
  type ComponentType,
} from "react";
import type { Grade } from "../../types/api";
import "./GradesCarousel.css";
import { Link } from "react-router-dom";
import { resolveAssetUrl } from "../../utils/assetUrl";
import ResponsiveImage from "../Media/ResponsiveImage";
import { useDeferredClientEnhancement } from "../../hooks/useDeferredClientEnhancement";
import { loadSwiperRuntime } from "../../utils/swiperRuntime";

interface GradesCarouselProps {
  grades: Grade[];
  loading?: boolean;
  error?: string | null;
}

interface GradeCardData {
  id_grade: number;
  nome: string;
  slug: string;
  descricao_curta: string;
  imageSrc: string;
}

const INITIAL_VISIBLE_CARD_COUNT = 4;
const FULL_VISIBLE_CARD_COUNT = 8;
const PLACEHOLDER_CARD_COUNT = FULL_VISIBLE_CARD_COUNT;
const PRIORITY_IMAGE_COUNT = 2;

const placeholderCards: GradeCardData[] = Array.from(
  { length: PLACEHOLDER_CARD_COUNT },
  (_, index) => ({
    id_grade: -(index + 1),
    nome: "Curso em destaque",
    slug: "",
    descricao_curta: "Conteudo sendo carregado...",
    imageSrc: "",
  }),
);

function toGradeCardData(grade: Grade): GradeCardData {
  const fallbackImage = `/assets/grades/${grade.nome.trim()}.jpg`;

  return {
    id_grade: grade.id_grade,
    nome: grade.nome,
    slug: grade.slug || "",
    descricao_curta: grade.descricao_curta || `Aprenda sobre ${grade.nome}`,
    imageSrc: grade.imagem_card ? resolveAssetUrl(grade.imagem_card) : fallbackImage,
  };
}

const GradeCard = memo(function GradeCard({
  grade,
  prioritizeImage,
  placeholder = false,
}: {
  grade: GradeCardData;
  prioritizeImage: boolean;
  placeholder?: boolean;
}) {
  if (placeholder) {
    return (
      <div className="grade-card grade-card-placeholder" aria-hidden="true">
        <div className="thumb thumb-skeleton" />
        <div className="info">
          <h3 className="grade-title">{grade.nome}</h3>
          <p className="grade-desc">{grade.descricao_curta}</p>
        </div>
      </div>
    );
  }

  return (
    <Link to={`/grades/${grade.slug}`} className="grade-card" draggable={false}>
      <div className="thumb">
        <ResponsiveImage
          src={grade.imageSrc}
          alt={grade.nome}
          sizes="(max-width: 768px) 50vw, 300px"
          loading={prioritizeImage ? "eager" : "lazy"}
          priority={prioritizeImage}
          draggable={false}
        />
      </div>
      <div className="info">
        <h3 className="grade-title">{grade.nome}</h3>
        <p className="grade-desc">{grade.descricao_curta}</p>
      </div>
    </Link>
  );
});

export default function GradesCarousel({
  grades,
  loading = false,
  error = null,
}: GradesCarouselProps) {
  const [showAllCriticalCards, setShowAllCriticalCards] = useState(false);
  const [swiperRuntime, setSwiperRuntime] = useState<Awaited<
    ReturnType<typeof loadSwiperRuntime>
  > | null>(null);
  const safeGrades = Array.isArray(grades) ? grades : [];
  const { elementRef, shouldEnhance } = useDeferredClientEnhancement<HTMLElement>(
    {
      rootMargin: "0px",
      idleDelayMs: 6500,
    },
  );

  useEffect(() => {
    let timeoutId: number | null = null;
    const frameId = window.requestAnimationFrame(() => {
      timeoutId = window.setTimeout(() => {
        setShowAllCriticalCards(true);
      }, 0);
    });

    return () => {
      window.cancelAnimationFrame(frameId);
      if (timeoutId !== null) {
        window.clearTimeout(timeoutId);
      }
    };
  }, []);

  useEffect(() => {
    if (!shouldEnhance || swiperRuntime) {
      return;
    }

    let cancelled = false;

    loadSwiperRuntime()
      .then((runtime) => {
        if (!cancelled) {
          setSwiperRuntime(runtime);
        }
      })
      .catch(() => {
        // Keep static grid when Swiper runtime fails.
      });

    return () => {
      cancelled = true;
    };
  }, [shouldEnhance, swiperRuntime]);

  const gradesData = useMemo(
    () => safeGrades.map((item) => toGradeCardData(item)),
    [safeGrades],
  );

  const swiperCards = useMemo(
    () => gradesData.slice(0, FULL_VISIBLE_CARD_COUNT),
    [gradesData],
  );

  const visibleCardCount = showAllCriticalCards
    ? FULL_VISIBLE_CARD_COUNT
    : INITIAL_VISIBLE_CARD_COUNT;

  const staticGridCards = useMemo(
    () =>
      gradesData.length > 0
        ? gradesData.slice(0, visibleCardCount)
        : placeholderCards.slice(0, visibleCardCount),
    [gradesData, visibleCardCount],
  );

  const hasData = gradesData.length > 0;
  const shouldShowSwiper = Boolean(swiperRuntime && swiperCards.length > 1);
  const SwiperComponent = swiperRuntime?.Swiper as
    | ComponentType<Record<string, unknown>>
    | undefined;
  const SwiperSlideComponent = swiperRuntime?.SwiperSlide as
    | ComponentType<Record<string, unknown>>
    | undefined;

  return (
    <section
      className={`grades-carousel${showAllCriticalCards ? " grades-carousel-enhanced" : ""}`}
      ref={elementRef}>
      <div className="container">
        <h2 className="section-title">Cursos Mais Vendidos</h2>
        {error && !hasData ? (
          <p className="grades-carousel-error">{error}</p>
        ) : null}

        {shouldShowSwiper && SwiperComponent && SwiperSlideComponent ? (
          <SwiperComponent
            modules={[swiperRuntime?.modules.Autoplay]}
            spaceBetween={20}
            slidesPerView="auto"
            loop={true}
            autoplay={{
              delay: 0,
              disableOnInteraction: false,
            }}
            speed={3000}
            freeMode={true}
            className="carousel-container-swiper">
            {swiperCards.map((grade, index) => {
              const prioritizeImage = index < PRIORITY_IMAGE_COUNT;

              return (
                <SwiperSlideComponent key={grade.id_grade}>
                  <GradeCard grade={grade} prioritizeImage={prioritizeImage} />
                </SwiperSlideComponent>
              );
            })}
          </SwiperComponent>
        ) : (
          <div className="carousel-static-grid" aria-busy={loading}>
            {staticGridCards.map((grade, index) => {
              const prioritizeImage = index < PRIORITY_IMAGE_COUNT;

              return (
                <div className="carousel-static-item" key={grade.id_grade}>
                  <GradeCard
                    grade={grade}
                    prioritizeImage={prioritizeImage}
                    placeholder={!hasData}
                  />
                </div>
              );
            })}
          </div>
        )}
      </div>
    </section>
  );
}
