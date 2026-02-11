import api from "./api";

export interface Banner {
  id_banner: number;
  imagem: string;
  imagem_mobile?: string | null;
  titulo?: string;
  link?: string;
  ordem: number;
  ativo: number;
}

export interface GradeCarousel {
  id: number;
  id_grade: number;
  nome: string;
  preco: string;
  ordem: number;
  descricao_curta?: string;
  imagem_card?: string;
}

export interface Stat {
  id_stat: number;
  tipo: string;
  valor: number;
  label: string;
  icone: string;
  ordem: number;
  imagem?: string;
}

export interface CategoryHomepage {
  id_categoria_homepage: number;
  id_categoria: number;
  nome: string;
  descricao: string;
  imagem: string | null;
  ordem: number;
}

export interface Testimonial {
  id_testimonial: number;
  foto?: string | null;
  thumbnail?: string | null;
  poster?: string | null;
  nome_aluno?: string;
  curso?: string;
  mensagem?: string;
  ordem: number;
  ativo?: number;
}

export interface FAQ {
  id_faq: number;
  pergunta: string;
  resposta: string;
  ordem: number;
}


const normalizeList = <T,>(
  payload: unknown,
): T[] => {
  if (typeof payload === "string") {
    try {
      const trimmed = payload.replace(/^\uFEFF/, "").trim();
      const parsed = JSON.parse(trimmed) as unknown;
      return normalizeList<T>(parsed);
    } catch {
      return [];
    }
  }
  if (Array.isArray(payload)) return payload as T[];
  if (
    payload &&
    typeof payload === "object" &&
    Array.isArray((payload as { data?: unknown }).data)
  ) {
    return (payload as { data: T[] }).data;
  }
  return [];
};

export const homepageService = {
  getBanners: async (): Promise<Banner[]> => {
    try {
      const response = await api.get("/homepage/banners");
      return normalizeList<Banner>(response.data);
    } catch {
      return [];
    }
  },
  getGradesCarousel: async (): Promise<GradeCarousel[]> => {
    try {
      const response = await api.get("/homepage/grades-carousel");
      return normalizeList<GradeCarousel>(response.data);
    } catch {
      return [];
    }
  },
  getStats: async (): Promise<Stat[]> => {
    try {
      const response = await api.get("/homepage/stats");
      return normalizeList<Stat>(response.data);
    } catch {
      return [];
    }
  },
  getCategoriesHomepage: async (): Promise<CategoryHomepage[]> => {
    try {
      const response = await api.get("/homepage/categories");
      return normalizeList<CategoryHomepage>(response.data);
    } catch {
      return [];
    }
  },
  getTestimonials: async (): Promise<Testimonial[]> => {
    try {
      const response = await api.get("/homepage/testimonials");
      return normalizeList<Testimonial>(response.data);
    } catch {
      return [];
    }
  },
  getFAQ: async (): Promise<FAQ[]> => {
    try {
      const response = await api.get("/homepage/faq");
      return normalizeList<FAQ>(response.data);
    } catch {
      return [];
    }
  },
};
