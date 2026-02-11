import { Link } from "react-router-dom";
import type { Grade } from "../../types/api";

interface Props {
  grade: Grade;
}

export default function GradeCard({ grade }: Props) {
  return (
    <article className="border rounded-lg px-4 py-3">
      <h2 className="text-lg font-semibold">{grade.nome}</h2>

      {grade.descricao_curta && (
        <p className="text-sm mt-1">{grade.descricao_curta}</p>
      )}

      <div className="mt-3">
        <Link
          to={`/grades/${grade.slug}`}
          className="inline-block border px-3 py-1 text-sm rounded"
        >
          Ver detalhes
        </Link>
      </div>
    </article>
  );
}
