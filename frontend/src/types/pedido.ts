export interface PedidoCurso {
  id_curso: number;
  nome: string;
  carga_horaria: number | null;
}

export interface Pedido {
  id_pedido: number;
  id_usuario?: number | null;
  id_grade?: number | null;
  tipo: string;
  modalidade: string;
  meses_duracao?: number | null;
  horas_total: number | string | null;
  valor_total: number | string | null;
  forma_pagamento?: "MENSAL" | "AVISTA" | "PARCELADO" | null;
  valor_mensal?: number | string | null;
  valor_avista?: number | string | null;
  valor_matricula?: number | string | null;
  status: string;
  criado_em: string;
  grade_nome?: string | null;
  grade_slug?: string | null;
  cursos?: PedidoCurso[];
}
