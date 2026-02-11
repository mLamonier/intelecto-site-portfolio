import { Link } from "react-router-dom";
import { useEffect, useState, type ComponentType } from "react";
import "./CategoriesSection.css";
import type { CategoryHomepage } from "../../services/homepage";
import { homepageService } from "../../services/homepage";
import { resolveHomepageImage } from "../../utils/assetUrl";
import ResponsiveImage from "../Media/ResponsiveImage";
import { useDeferredClientEnhancement } from "../../hooks/useDeferredClientEnhancement";
import { loadSwiperRuntime } from "../../utils/swiperRuntime";

export default function CategoriesSection() {
  const [categories, setCategories] = useState<CategoryHomepage[]>([]);
  const [loading, setLoading] = useState(true);
  const [swiperRuntime, setSwiperRuntime] = useState<Awaited<
    ReturnType<typeof loadSwiperRuntime>
  > | null>(null);
  const { elementRef, shouldEnhance } = useDeferredClientEnhancement<HTMLElement>(
    {
      rootMargin: "200px",
      idleDelayMs: 3200,
    },
  );

  useEffect(() => {
    const fetchCategories = async () => {
      setLoading(true);
      const data = await homepageService.getCategoriesHomepage();
      if (data && data.length > 0) {
        setCategories(data);
      }
      setLoading(false);
    };
    fetchCategories();
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
        // Keep static categories grid fallback when Swiper runtime fails.
      });

    return () => {
      cancelled = true;
    };
  }, [shouldEnhance, swiperRuntime]);

  const duplicatedCategories = [
    ...categories,
    ...categories,
    ...categories,
    ...categories,
    ...categories,
  ];

  const shouldShowSwiper = Boolean(swiperRuntime && categories.length > 1);
  const SwiperComponent = swiperRuntime?.Swiper as
    | ComponentType<Record<string, unknown>>
    | undefined;
  const SwiperSlideComponent = swiperRuntime?.SwiperSlide as
    | ComponentType<Record<string, unknown>>
    | undefined;

  if (loading || !Array.isArray(categories) || categories.length === 0) {
    return (
      <section
        className="categories-section"
        style={{ minHeight: "300px" }}
        ref={elementRef}
      />
    );
  }

  return (
    <section className="categories-section" ref={elementRef}>
      <h2 className="categories-title">
        Encontre o curso ideal pela sua area de interesse
      </h2>
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
          className="categories-carousel-swiper">
          {duplicatedCategories.map((category, index) => {
            return (
              <SwiperSlideComponent key={`${category.id_categoria_homepage}-${index}`}>
                <Link
                  to={`/cursos?categoria=${category.id_categoria}`}
                  className="category-card"
                  draggable={false}>
                  <div className="category-image">
                    {category.imagem ? (
                      <ResponsiveImage
                        src={resolveHomepageImage(category.imagem)}
                        alt={category.nome}
                        sizes="(max-width: 768px) 45vw, 280px"
                        draggable={false}
                      />
                    ) : (
                      <div className="category-placeholder">Sem imagem</div>
                    )}
                  </div>
                  <div className="category-content">
                    <h3>{category.nome}</h3>
                    <p>{category.descricao || "Conheca os cursos desta categoria"}</p>
                  </div>
                </Link>
              </SwiperSlideComponent>
            );
          })}
        </SwiperComponent>
      ) : (
        <div className="categories-static-grid">
          {categories.map((category) => {
            return (
              <Link
                key={category.id_categoria_homepage}
                to={`/cursos?categoria=${category.id_categoria}`}
                className="category-card"
                draggable={false}>
                <div className="category-image">
                  {category.imagem ? (
                    <ResponsiveImage
                      src={resolveHomepageImage(category.imagem)}
                      alt={category.nome}
                      sizes="(max-width: 768px) 45vw, 280px"
                      draggable={false}
                    />
                  ) : (
                    <div className="category-placeholder">Sem imagem</div>
                  )}
                </div>
                <div className="category-content">
                  <h3>{category.nome}</h3>
                  <p>{category.descricao || "Conheca os cursos desta categoria"}</p>
                </div>
              </Link>
            );
          })}
        </div>
      )}
    </section>
  );
}
