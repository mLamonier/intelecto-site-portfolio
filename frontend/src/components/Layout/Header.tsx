import { Link } from "react-router-dom";
import "./Header.css";
import {
  whatsAppUrl,
  adminLoginUrl,
  adminDashboardUrl,
  apiBaseUrl,
} from "../../services/site";
import { FaWhatsapp, FaUser, FaChevronDown } from "react-icons/fa";
import { useState, useEffect, useRef } from "react";
import axios from "axios";
import ResponsiveImage from "../Media/ResponsiveImage";

export default function Header() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [isAdmin, setIsAdmin] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    let cancelled = false;
    let timeoutId: number | null = null;
    let idleCallbackId: number | null = null;

    const checkAuth = async () => {
      try {
        const response = await axios.get(`${apiBaseUrl()}/auth-check.php`, {
          withCredentials: true,
        });

        if (cancelled) return;

        if (response.data.logado) {
          setIsLoggedIn(true);
          const roles = response.data?.usuario?.roles ?? [];
          setIsAdmin(Array.isArray(roles) && roles.includes("ADMIN"));
        } else {
          setIsLoggedIn(false);
          setIsAdmin(false);
        }
      } catch {
        if (cancelled) return;
        setIsLoggedIn(false);
        setIsAdmin(false);
      }
    };

    timeoutId = window.setTimeout(() => {
      if (typeof window.requestIdleCallback === "function") {
        idleCallbackId = window.requestIdleCallback(
          () => {
            void checkAuth();
          },
          { timeout: 2500 },
        );
        return;
      }

      void checkAuth();
    }, 1500);

    return () => {
      cancelled = true;
      if (timeoutId !== null) {
        window.clearTimeout(timeoutId);
      }
      if (
        idleCallbackId !== null &&
        typeof window.cancelIdleCallback === "function"
      ) {
        window.cancelIdleCallback(idleCallbackId);
      }
    };
  }, []);

  
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target as Node)
      ) {
        setDropdownOpen(false);
      }
    };

    if (dropdownOpen) {
      document.addEventListener("mousedown", handleClickOutside);
    }

    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [dropdownOpen]);

  const toggleMenu = () => {
    setMenuOpen(!menuOpen);
  };

  const toggleDropdown = () => {
    setDropdownOpen(!dropdownOpen);
  };

  const handleLogout = async () => {
    try {
      await axios.post(
        `${apiBaseUrl()}/logout.php`,
        {},
        {
          withCredentials: true,
        },
      );

      setIsLoggedIn(false);
      setIsAdmin(false);
      setDropdownOpen(false);

      
      window.location.href = adminLoginUrl();
    } catch {
      alert("Erro ao fazer logout. Tente novamente.");
    }
  };

  const closeMenu = () => {
    setMenuOpen(false);
  };

  return (
    <header className="site-header">
      <div className="header-inner">
        <Link to="/" className="logo" onClick={closeMenu}>
          <ResponsiveImage
            src="/assets/logo/Marca - Sem Fundo.png"
            alt="Intelecto Logo"
            sizes="(max-width: 768px) 180px, 300px"
            width={300}
            height={70}
            loading="eager"
            priority
          />
        </Link>
        <nav className={`nav ${menuOpen ? "active" : ""}`}>
          <Link to="/quem-somos" onClick={closeMenu}>
            QUEM SOMOS
          </Link>
          <Link to="/cursos" onClick={closeMenu}>
            CURSOS
          </Link>
          <Link to="/monte-sua-grade" onClick={closeMenu}>
            MONTAR GRADE
          </Link>
          <a href="/#duvidas" onClick={closeMenu}>
            DÚVIDAS
          </a>
          <a href="/#depoimentos" onClick={closeMenu}>
            DEPOIMENTOS
          </a>
        </nav>
        <div className="header-icons">
          <a
            className="icon-btn whatsapp-btn"
            href={whatsAppUrl()}
            target="_blank"
            rel="noreferrer"
            aria-label="WhatsApp">
            <FaWhatsapp />
          </a>
          {isLoggedIn ? (
            <div className="user-menu-wrapper" ref={dropdownRef}>
              <button
                className="icon-btn user-btn logged-in"
                onClick={toggleDropdown}
                aria-label="Minha Conta"
                aria-expanded={dropdownOpen}>
                <FaUser />
                <span className="user-text">Minha Conta</span>
                <FaChevronDown className="chevron-icon" />
              </button>
      {dropdownOpen && (
        <div className="user-dropdown">
          <Link
            to="/meus-pedidos"
            className="dropdown-item"
            onClick={() => {
              setDropdownOpen(false);
              closeMenu();
            }}>
            Meus Pedidos
          </Link>
          {isAdmin && (
            <a
              className="dropdown-item"
              href={adminDashboardUrl()}
              onClick={() => {
                setDropdownOpen(false);
                closeMenu();
              }}>
              Dashboard Admin
            </a>
          )}
          <button
            className="dropdown-item logout"
            onClick={handleLogout}>
            Sair
          </button>
        </div>
      )}
            </div>
          ) : (
            <a
              className="icon-btn user-btn"
              href={adminLoginUrl()}
              aria-label="Login">
              <FaUser />
            </a>
          )}
          <button
            className={`hamburger ${menuOpen ? "active" : ""}`}
            onClick={toggleMenu}
            aria-label="Menu"
            aria-expanded={menuOpen}>
            <span></span>
            <span></span>
            <span></span>
          </button>
        </div>
      </div>
    </header>
  );
}
