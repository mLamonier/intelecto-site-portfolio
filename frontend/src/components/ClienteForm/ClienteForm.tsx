import { useState } from "react";
import { Link } from "react-router-dom";
import api from "../../services/api";
import "./ClienteForm.css";
import { sitePath } from "../../services/site";

interface ClienteFormProps {
  onSubmit: (dados: ClienteData) => void;
  onCancel?: () => void;
  loading?: boolean;
  errorMessage?: string | null;
}

export interface ClienteData {
  nome: string;
  email: string;
  telefone: string;
  cpf: string;
}

export default function ClienteForm({
  onSubmit,
  onCancel,
  loading = false,
  errorMessage,
}: ClienteFormProps) {
  const [nome, setNome] = useState("");
  const [email, setEmail] = useState("");
  const [telefone, setTelefone] = useState("");
  const [cpf, setCpf] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [verificando, setVerificando] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (loading || verificando) return;

    const newErrors: Record<string, string> = {};

    if (!nome.trim()) {
      newErrors.nome = "Nome é obrigatório";
    }

    if (!email.trim()) {
      newErrors.email = "Email é obrigatório";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      newErrors.email = "Email inválido";
    }

    if (!telefone.trim()) {
      newErrors.telefone = "Telefone é obrigatório";
    } else if (
      !/^\(?[1-9]{2}\)?\s?9?\s?\d{4}-?\d{4}$/.test(telefone.replace(/\s/g, ""))
    ) {
      newErrors.telefone = "Telefone inválido";
    }

    if (!cpf.trim()) {
      newErrors.cpf = "CPF é obrigatório";
    } else if (!/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/.test(cpf)) {
      newErrors.cpf = "CPF inválido";
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    setVerificando(true);
    try {
      const response = await api.post("/usuarios/verificar", {
        email,
        cpf: cpf.replace(/\D/g, ""),
      });

      if (response.status === 200) {
        onSubmit({ nome, email, telefone, cpf: cpf.replace(/\D/g, "") });
      }
    } catch (err: unknown) {
      const status = (err as { response?: { status?: number } })?.response
        ?.status;
      if (status === 409) {
        setErrors({
          submit: "userExists",
        });
      } else if (status !== 409) {
        setErrors({
          submit: "Erro ao verificar dados. Tente novamente.",
        });
      }
    } finally {
      setVerificando(false);
    }
  };

  const formatTelefone = (value: string) => {
    const numbers = value.replace(/\D/g, "");
    if (numbers.length <= 11) {
      return numbers
        .replace(/^(\d{2})(\d)/g, "($1) $2")
        .replace(/(\d{5})(\d)/, "$1-$2");
    }
    return telefone;
  };

  const formatCpf = (value: string) => {
    const numbers = value.replace(/\D/g, "");
    if (numbers.length <= 11) {
      return numbers
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    }
    return cpf;
  };

  const getLoginUrl = () => {
    return sitePath("login.php");
  };

  return (
    <div className="cliente-form-overlay">
      <div className="cliente-form-modal">
        <h2>Antes de continuar...</h2>
        <p className="subtitle">
          Precisamos de algumas informações para processar seu pedido
        </p>

        {errorMessage && <p className="error-banner">{errorMessage}</p>}
        {errors.submit === "userExists" ? (
          <div className="error-banner" style={{ textAlign: "center" }}>
            <p style={{ marginBottom: "10px" }}>
              E-mail ou CPF já cadastrados. Favor faça o login.
            </p>
            <a
              href={getLoginUrl()}
              style={{
                color: "#242323",
                textDecoration: "underline",
                fontWeight: "bold",
              }}>
              Clique aqui para fazer login
            </a>
          </div>
        ) : errors.submit ? (
          <p className="error-banner">{errors.submit}</p>
        ) : null}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="nome">Nome Completo *</label>
            <input
              type="text"
              id="nome"
              value={nome}
              onChange={(e) => setNome(e.target.value)}
              className={errors.nome ? "error" : ""}
              placeholder="Seu nome completo"
              disabled={verificando || loading}
            />
            {errors.nome && (
              <span className="error-message">{errors.nome}</span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="email">Email *</label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className={errors.email ? "error" : ""}
              placeholder="seu@email.com"
              disabled={verificando || loading}
            />
            {errors.email && (
              <span className="error-message">{errors.email}</span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="telefone">Telefone *</label>
            <input
              type="tel"
              id="telefone"
              value={telefone}
              onChange={(e) => setTelefone(formatTelefone(e.target.value))}
              className={errors.telefone ? "error" : ""}
              placeholder="(00) 00000-0000"
              maxLength={15}
              disabled={verificando || loading}
            />
            {errors.telefone && (
              <span className="error-message">{errors.telefone}</span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="cpf">CPF *</label>
            <input
              type="text"
              id="cpf"
              value={cpf}
              onChange={(e) => setCpf(formatCpf(e.target.value))}
              className={errors.cpf ? "error" : ""}
              placeholder="000.000.000-00"
              maxLength={14}
              disabled={verificando || loading}
            />
            {errors.cpf && <span className="error-message">{errors.cpf}</span>}
          </div>

          <p className="privacy-note">
            Ao clicar em continuar, você concorda com nossas{" "}
            <Link to="/politica-de-privacidade">política de privacidade</Link> e{" "}
            <Link to="/termos-de-uso">termos de uso</Link>.
          </p>

          <div className="form-actions">
            {onCancel && (
              <button
                type="button"
                onClick={onCancel}
                className="btn-cancel"
                disabled={verificando || loading}>
                Cancelar
              </button>
            )}
            <button
              type="submit"
              className="btn-submit"
              disabled={loading || verificando}>
              {loading || verificando
                ? "Verificando..."
                : "Continuar para Pagamento"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
