
import api from "./api";

type ClienteInfo = {
  nome: string;
  email: string;
  telefone?: string;
};

const STORAGE_KEY = "intelecto_cliente_id";

export function getStoredClienteId(): number | null {
  const raw = window.localStorage.getItem(STORAGE_KEY);
  if (!raw) return null;
  const id = Number(raw);
  return Number.isFinite(id) && id > 0 ? id : null;
}

export async function ensureClienteId(
  getInfo?: () => Promise<ClienteInfo | null>
): Promise<number> {
  const existing = getStoredClienteId();
  if (existing) return existing;

  let info: ClienteInfo | null = null;
  if (getInfo) {
    info = await getInfo();
  } else {
    
    const nome = window.prompt("Digite seu nome completo:")?.trim() ?? "";
    const email = window.prompt("Digite seu e-mail:")?.trim() ?? "";
    const telefone = window.prompt("Telefone (opcional):")?.trim() ?? "";
    info = nome && email ? { nome, email, telefone } : null;
  }

  if (!info) throw new Error("Informações do cliente não fornecidas.");

  const res = await api.post("/usuarios", {
    nome: info.nome,
    email: info.email,
    telefone: info.telefone ?? null,
  });
  const id = Number(res.data?.id ?? 0);
  if (!id || !Number.isFinite(id)) throw new Error("Falha ao criar usuário.");
  window.localStorage.setItem(STORAGE_KEY, String(id));
  return id;
}
