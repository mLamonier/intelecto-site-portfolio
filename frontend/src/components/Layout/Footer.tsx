import "./Footer.css";
import { FaInstagram, FaFacebook, FaWhatsapp } from "react-icons/fa";
import { Link } from "react-router-dom";
import ResponsiveImage from "../Media/ResponsiveImage";

export default function Footer() {
  return (
    <footer className="site-footer" id="contato">
      <div className="footer-inner">
        <div className="footer-left">
          <ResponsiveImage
            src="/assets/logo/Marca - Sem Fundo.png"
            alt="Intelecto"
            sizes="(max-width: 768px) 180px, 280px"
            width={280}
            height={66}
          />
        </div>
        <div className="footer-social-links">
          <a
            href="https://instagram.com/intelectoprofissionalizantes"
            aria-label="Instagram"
            title="Instagram"
            target="_blank"
            rel="noreferrer">
            <FaInstagram />
          </a>
          <a
            href="https://facebook.com/intelectoprofissionalizantes"
            aria-label="Facebook"
            title="Facebook"
            target="_blank"
            rel="noreferrer">
            <FaFacebook />
          </a>
          <a
            href="https://wa.me/5535998421176"
            aria-label="WhatsApp"
            title="WhatsApp"
            target="_blank"
            rel="noreferrer">
            <FaWhatsapp />
          </a>
        </div>

        <div className="footer-copy">
          Intelecto Profissionalizantes e Idiomas © {new Date().getFullYear()}
        </div>

        <div className="footer-rights">Todos os direitos reservados</div>

        <div className="footer-legal-links">
          <Link to="/politica-de-privacidade">Política de Privacidade</Link>
          <span>|</span>
          <Link to="/termos-de-uso">Termos de Uso</Link>
        </div>

        <div className="footer-credit">
          Desenvolvido por{" "}
          <a
            href="https://wa.me/5535998421176"
            target="_blank"
            rel="noreferrer"
            aria-label="WhatsApp de Miguel Lamonier">
            Miguel Lamonier
          </a>
        </div>
      </div>
    </footer>
  );
}
