export interface GradeCurso {
  ordem: number;
  id_curso: number;
  nome: string;
  slug: string;
  horas?: number | null; 
  carga_horaria?: number | null; 
  pdf_conteudo?: string | null;
  link_aula_demo?: string | null;
}

export interface Grade {
  id_grade: number;
  nome: string;
  slug: string;
  id_categoria?: number | null;
  categoria?: number | null; 
  descricao_curta?: string | null;
  descricao_longa_md?: string | null;
  meses_duracao?: number | null;
  carga_horaria_total?: number | null;

  
  imagem_card?: string | null;
  imagem_detalhe?: string | null;

  
  tipo_venda?: "MENSAL" | "AVISTA_PARCELADO" | null;
  vende_mensal?: number | boolean | null;

  
  preco?: number | null;
  ordem?: number | null;

  
  preco_avista?: number | null;
  parcelas_maximas?: number | null;
  mensalidade_valor?: number | null;
  matricula_valor?: number | null;

  
  valor_mensal_presencial?: number | null;
  valor_mensal_ead?: number | null;
  valor_avista_presencial?: number | null;
  valor_avista_ead?: number | null;
  valor_matricula?: number | null;
  percentual_parcelamento?: number | null;

  
  cursos?: GradeCurso[];
}

export interface GradeDetail extends Grade {
  descricao_longa_md: string | null;
  valor_matricula: number | null;
  horas_total: number;
  cursos: GradeCurso[];
}

export interface CursoPersonalizavel {
  id_curso: number;
  nome: string;
  slug: string;
  horas: number;
  descricao_curta: string;
  descricao_longa_md?: string | null;
  pode_montar_grade: boolean;
}
