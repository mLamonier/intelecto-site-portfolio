import { Link } from "react-router-dom";
import "./CTASection.css";

export default function CTASection() {
  return (
    <section className="cta-section">
      <div className="container cta-inner">
        <h2>Monte sua grade de cursos personalizada!</h2>
        <p>Escolha cursos e monte uma trilha do seu jeito.</p>
        <Link to="/monte-sua-grade" className="cta-btn">
          Montar grade
        </Link>
      </div>
    </section>
  );
}
