# Intelecto Site

Plataforma full stack para captação, venda e gestão de cursos profissionalizantes.

Este projeto cobre o fluxo completo: vitrine pública, montagem de grade personalizada, checkout com PagBank (PIX/Boleto/Cartão), área do aluno, painel administrativo e API própria.

## Stack usada (exatamente no projeto)

### Backend
- PHP 8+ com PDO (MySQL)
- PHPMailer `^7.0`
- Sessões PHP (`$_SESSION`) para autenticação/ACL
- cURL para integrações externas (PagBank e Google Places)

### Frontend
- React `19.2.0`
- TypeScript `~5.9.3`
- Vite `^7.2.4`
- React Router DOM `^7.11.0`
- Axios `^1.13.2`
- Swiper `^12.0.3`
- @hello-pangea/dnd `^18.0.1` (drag-and-drop da grade personalizada)
- React PDF `^10.3.0`
- Lucide React + React Icons

### Tooling e qualidade
- ESLint 9 + TypeScript ESLint
- Geração de imagens responsivas com `sharp`
- Build analysis com `rollup-plugin-visualizer`
- Scripts de verificação com Lighthouse (arquivos em `frontend/scripts/`)

## Arquitetura

### Frontend (SPA)
- Rotas com `react-router-dom` em `frontend/src/routes/AppRoutes.tsx`
- Páginas principais:
  - Home
  - Quem somos
  - Catálogo de cursos
  - Detalhe da grade
  - Monte sua grade
  - Checkout
  - Meus pedidos / detalhe do pedido
  - Política de privacidade / termos de uso
- Cliente HTTP centralizado com Axios (`withCredentials: true`) para sessão via cookie
- Proxy local do Vite para backend PHP em `/intelecto-site/api`

### Backend (API + páginas server-rendered)
- API central com roteamento em `api/index.php`
- Controllers para:
  - cursos, categorias, grades
  - usuários
  - pedidos
  - pagamentos
  - homepage (banners, FAQ, depoimentos, carrosséis, stats)
  - avaliações Google
- Páginas PHP de autenticação (`login.php`, `register.php`, `forgot-password.php`, `reset-password.php`)
- Painel admin em `admin/` com gestão operacional completa

## Funcionalidades implementadas

### Jornada do aluno
- Cadastro e login com sessão
- Recuperação e redefinição de senha
- Montagem de grade personalizada com drag-and-drop
- Seleção de modalidade (Presencial/EAD) e plano (Mensal/À vista/Parcelado)
- Criação de pedido e checkout integrado
- Acompanhamento de pedidos e pagamentos

### Pagamentos
- Integração PagBank para:
  - PIX (QR Code, código copia-e-cola, expiração e regeneração)
  - Boleto (link de pagamento)
  - Cartão de crédito (com parcelamento)
- Endpoint de webhook para atualização assíncrona do status
- Validação de assinatura HMAC no webhook
- Modo sandbox com aprovação manual para homologação

### Painel administrativo
- Dashboard com indicadores de leads e pedidos
- CRUD de usuários, categorias, cursos e grades
- Gestão de pedidos e status
- Gestão de conteúdo da homepage:
  - banners
  - categorias em destaque
  - carrossel de grades
  - FAQ
  - depoimentos
  - estatísticas
- Configurações globais de valores

### Integrações auxiliares
- Google Places para avaliações (com cache local em arquivo)
- Disparo de e-mails transacionais:
  - primeiro acesso
  - recuperação de senha
  - confirmação/liberação após pagamento

## Decisões técnicas e engenharia aplicada
- Controle de acesso por papel (`ADMIN`/`ALUNO`) com guards na API
- `session_regenerate_id(true)` no login
- Tokens CSRF em formulários server-rendered
- Senhas com hash (`password_hash` / `password_verify`)
- API com tratamento de erros e códigos HTTP coerentes
- Retry para criação de pagamento (PIX/BOLETO) em falhas transitórias
- Build front com code splitting manual de vendors (React/Router/Swiper)
- Pipeline de imagens responsivas com manifesto gerado automaticamente

## Estrutura do projeto

```text
portfolio/
  admin/                      # Painel administrativo (PHP)
  api/                        # API (roteamento, controllers, services)
  includes/                   # utilitários compartilhados (site, csrf, mailer)
  frontend/                   # SPA React + Vite
  login.php
  register.php
  forgot-password.php
  reset-password.php
  .env.example
  .env.local.example
```

## Variáveis de ambiente

### Backend (`.env.local` ou `.env`)
Partindo de `portfolio/.env.example`:
- App:
  - `APP_URL`, `SITE_URL`
- Banco:
  - `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`
- E-mail/SMTP:
  - `MAIL_FROM`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USER`, `MAIL_PASS`
- PagBank:
  - `PAGBANK_EMAIL`, `PAGBANK_TOKEN`, `PAGBANK_PUBLIC_KEY`, `PAGBANK_SANDBOX`
  - `PAGBANK_WEBHOOK_URL`
  - flags de sandbox/aprovação (`PAGBANK_SANDBOX_FORCE_APPROVAL`, etc.)
- Segurança:
  - `CSRF_SECRET`
- Google (usado pelo módulo de reviews):
  - `GOOGLE_API_KEY`, `GOOGLE_PLACE_ID`

### Frontend (`frontend/.env`)
Partindo de `portfolio/frontend/.env.example`:
- `VITE_PAGBANK_PUBLIC_KEY`

## Como rodar localmente

### 1) Backend
```bash
cd portfolio
composer install
```

Configure as variáveis de ambiente:
- copiar `.env.example` para `.env.local` (ou `.env`)
- ajustar banco, PagBank, SMTP e URLs

### 2) Frontend
```bash
cd portfolio/frontend
npm install
npm run dev
```

### 3) Acesso local
- Frontend: `http://localhost:5173`
- Backend/API em WAMP: `http://localhost/intelecto-site`
- Admin (após login): `http://localhost/intelecto-site/admin/index.php`

## Scripts úteis (frontend)
```bash
npm run dev
npm run build
npm run build:analyze
npm run lint
npm run images:generate
```

Scripts adicionais em `frontend/scripts/`:
- `run-verify-server.mjs`
- `run-lighthouse-verify.mjs`

## Endpoints principais da API
- `GET /api/cursos`
- `GET /api/categorias`
- `GET /api/grades`
- `POST /api/pedidos`
- `POST /api/pagamentos`
- `GET /api/pagamentos/pedido/{id}/ultimo`
- `GET /api/google-reviews`
- `GET /api/homepage/*`
- `POST /api/homepage/upload`
- `POST /api/webhooks/pagbank.php`

## Segurança para publicação pública
Este diretório de portfólio foi preparado para repositório público:
- não inclui `.env` reais
- não inclui `vendor/` e `node_modules/`
- não inclui chaves/tokens de produção
- inclui apenas arquivos de exemplo (`.env.example`)

## Sobre o autor
Projeto desenvolvido por **Miguel Lamonier**.
