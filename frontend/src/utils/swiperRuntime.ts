import type { ComponentType } from "react";

interface SwiperRuntime {
  Swiper: ComponentType<Record<string, unknown>>;
  SwiperSlide: ComponentType<Record<string, unknown>>;
  modules: {
    Autoplay: unknown;
    Navigation: unknown;
    Pagination: unknown;
  };
}

let runtimePromise: Promise<SwiperRuntime> | null = null;

export function loadSwiperRuntime(): Promise<SwiperRuntime> {
  if (!runtimePromise) {
    runtimePromise = Promise.all([
      import("swiper/react"),
      import("swiper/modules"),
      import("swiper/css"),
    ]).then(([swiperReact, swiperModules]) => ({
      Swiper: swiperReact.Swiper as ComponentType<Record<string, unknown>>,
      SwiperSlide: swiperReact.SwiperSlide as ComponentType<
        Record<string, unknown>
      >,
      modules: {
        Autoplay: swiperModules.Autoplay,
        Navigation: swiperModules.Navigation,
        Pagination: swiperModules.Pagination,
      },
    }));
  }

  return runtimePromise;
}
