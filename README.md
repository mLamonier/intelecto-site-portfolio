# Intelecto Site (Versão Pública para Portfólio)

Este diretório contém uma cópia organizada do projeto para publicação no GitHub.

## O que está incluso
- `frontend/`: aplicação React + Vite
- `api/`: API PHP
- `admin/`: painel administrativo PHP
- `includes/`: utilitários compartilhados
- arquivos de entrada PHP na raiz (`login.php`, `register.php`, etc.)
- exemplos de ambiente: `.env.example`, `.env.local.example`, `frontend/.env.example`

## O que foi excluído por segurança
- arquivos com segredos reais (`.env`, `.env.local`, `frontend/.env`)
- dependências instaladas (`vendor/`, `frontend/node_modules/`)
- artefatos de build e logs

## Requisitos
- PHP 8.1+ (com extensões comuns para PDO/MySQL)
- Composer
- Node.js 18+ e npm
- MySQL/MariaDB

## Como rodar localmente
1. Backend:
   - copie `.env.example` para `.env.local` (ou `.env`) e ajuste os valores.
   - execute `composer install`.
2. Frontend:
   - entre em `frontend/`.
   - copie `.env.example` para `.env`.
   - execute `npm install`.
   - execute `npm run dev`.

## Publicação no GitHub
1. Crie um novo repositório público vazio.
2. Inicialize o git dentro desta pasta `portfolio/`.
3. Faça commit dos arquivos e envie para o remoto.

Exemplo:

```bash
cd portfolio
git init
git add .
git commit -m "feat: versao publica para portfolio"
git branch -M main
git remote add origin <URL_DO_REPOSITORIO_PUBLICO>
git push -u origin main
```