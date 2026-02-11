import { useEffect, useState, type ComponentType } from "react";
import "./BannerCarousel.css";
import type { Banner } from "./../../services/homepage";
import { homepageService } from "./../../services/homepage";
import ResponsiveImage from "../Media/ResponsiveImage";
import { useDeferredClientEnhancement } from "../../hooks/useDeferredClientEnhancement";
import { loadSwiperRuntime } from "../../utils/swiperRuntime";

export default function BannerCarousel() {
  const [banners, setBanners] = useState<Banner[]>([]);
  const [loading, setLoading] = useState(true);
  const [swiperRuntime, setSwiperRuntime] = useState<Awaited<
    ReturnType<typeof loadSwiperRuntime>
  > | null>(null);
  const { elementRef, shouldEnhance } = useDeferredClientEnhancement<HTMLElement>(
    {
      rootMargin: "0px",
      idleDelayMs: 5200,
    },
  );
  const safeBanners = Array.isArray(banners) ? banners : [];

  
  useEffect(() => {
    const fetchBanners = async () => {
      setLoading(true);
      const data = await homepageService.getBanners();
      if (data && data.length > 0) {
        setBanners(data);
      }
      setLoading(false);
    };
    fetchBanners();
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
        // Keep static banner fallback when Swiper runtime fails.
      });

    return () => {
      cancelled = true;
    };
  }, [shouldEnhance, swiperRuntime]);

  if (loading || safeBanners.length === 0) {
    return (
      <section
        className="banner-carousel banner-carousel-placeholder"
        ref={elementRef}
      />
    );
  }

  const firstBanner = safeBanners[0];
  const shouldShowSwiper = Boolean(swiperRuntime && safeBanners.length > 1);
  const SwiperComponent = swiperRuntime?.Swiper as
    | ComponentType<Record<string, unknown>>
    | undefined;
  const SwiperSlideComponent = swiperRuntime?.SwiperSlide as
    | ComponentType<Record<string, unknown>>
    | undefined;

  const renderBannerImage = (banner: Banner, isFirst: boolean) => {
    const desktopImage = banner.imagem || "";
    const mobileImage = banner.imagem_mobile || desktopImage;
    const imageContent = (
      <ResponsiveImage
        src={desktopImage}
        mobileSrc={mobileImage}
        mobileMedia="(max-width: 768px)"
        alt={banner.titulo || `Banner ${banner.id_banner}`}
        sizes="100vw"
        loading={isFirst ? "eager" : "lazy"}
        priority={isFirst}
      />
    );

    if (!banner.link) {
      return imageContent;
    }

    return (
      <a
        href={banner.link}
        target="_blank"
        rel="noopener noreferrer"
        style={{ display: "block", width: "100%", height: "100%" }}>
        {imageContent}
      </a>
    );
  };

  return (
    <section className="banner-carousel" ref={elementRef}>
      {shouldShowSwiper && SwiperComponent && SwiperSlideComponent ? (
        <>
          <SwiperComponent
            modules={[
              swiperRuntime?.modules.Navigation,
              swiperRuntime?.modules.Pagination,
              swiperRuntime?.modules.Autoplay,
            ]}
            navigation={{
              prevEl: ".banner-custom-prev",
              nextEl: ".banner-custom-next",
            }}
            pagination={{ clickable: true }}
            autoplay={{
              delay: 10000,
              disableOnInteraction: false,
            }}
            loop={true}
            className="banner-swiper">
            {safeBanners.map((banner, index) => (
              <SwiperSlideComponent key={banner.id_banner}>
                {renderBannerImage(banner, index === 0)}
              </SwiperSlideComponent>
            ))}
          </SwiperComponent>
          <button className="banner-custom-prev" aria-label="Anterior">
            {"<"}
          </button>
          <button className="banner-custom-next" aria-label="Pr\u00f3ximo">
            {">"}
          </button>
        </>
      ) : (
        <div className="banner-static">{renderBannerImage(firstBanner, true)}</div>
      )}
    </section>
  );
}
