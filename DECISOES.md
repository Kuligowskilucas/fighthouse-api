# Decisões do projeto

Documento vivo com as decisões técnicas e de negócio tomadas durante o desenvolvimento. Itens marcados com **A validar** dependem de confirmação com o Marquete.

---

## Stack

- **Backend:** Laravel 13 + PostgreSQL + Sanctum
- **Frontend:** Next.js 15 + TypeScript + Tailwind + shadcn/ui (em outro repositório)
- **Infraestrutura dev:** Docker (Laravel Sail)
- **Infraestrutura prod:** Fly.io (backend) + Neon (Postgres) + Vercel (frontend) — a configurar no deploy

---

## Regras de negócio

### Valor da mensalidade
- O valor base é definido pelo plano.
- Cada aluno pode ter um `valor_personalizado` opcional que sobrescreve o do plano.
- Cobre casos de desconto (amizade, aluno antigo, bolsista).
- **A validar:** existem casos assim hoje?

### Dia de vencimento
- Cada aluno tem seu próprio dia, baseado na data de matrícula.
- Ex.: matriculou dia 12, vence todo dia 12.
- Quando o dia não existe no mês (ex.: dia 31 em fevereiro), ajusta para o último dia do mês.
- **A validar:** prefere um dia fixo pra todos?

### Tolerância de atraso
- Zero dias. Passou do vencimento, está atrasado.
- Fácil de aumentar para X dias depois se necessário.
- **A validar:** tem prática atual na academia?

### Pagamento atrasado
- Quita o mês em aberto (o vencido), não o atual.
- Ex.: venceu março, paga em abril → quita março. Abril continua em aberto.
- **A validar:** é assim que o Marquete pensa?

### Planos iniciais (Fight House Club)
- Plano Livre — R$ 249,90 — frequência livre
- Muaythai ou Jiu Jitsu (masculino) — R$ 169,90 — 3x/semana
- Boxe / Muaythai Feminino / Jiu Jitsu Feminino — R$ 129,90 — 2x/semana
- **A validar:** preços e modalidades atuais?

---

## Gerenciamento de usuários

### Implementado
- `POST /api/users`: qualquer usuário autenticado pode criar outros usuários (sistema interno, pessoas de confiança).
- `POST /api/me/change-password`: troca a própria senha. Exige senha atual. Invalida tokens de outros dispositivos, mantém o atual.
- `php artisan user:create`: comando interativo. Usado no deploy para criar o primeiro admin.
- `php artisan user:reset-password {email}`: reset operacional para senha esquecida. Invalida todos os tokens do usuário.

### Requisitos de senha
- Mínimo 8 caracteres
- Pelo menos 1 letra
- Pelo menos 1 número
- Aplicados em criação, troca e reset.

---

## Geração de mensalidades

### Implementado
- **Service `GeradorDeMensalidades`**: lógica central, reutilizada por comando e endpoint.
- **Comando `php artisan mensalidades:gerar`**: aceita `--mes=YYYY-MM` opcional. Agendado para rodar dia 1 de cada mês via `Schedule::command()`.
- **Endpoint `POST /api/mensalidades/gerar`**: para disparo manual via frontend.

### Regras
- Gera apenas para alunos com `ativo = true`.
- Aluno cadastrado após o mês de referência **não** recebe mensalidade retroativa.
- Idempotente: re-executar não duplica (`firstOrCreate` + unique constraint).

### A validar
- **Não gera retroativo para alunos novos.** Quem matricula dia 15 começa a pagar a partir do mês seguinte. Decisão tomada por simplicidade. Confirmar se faz sentido pro Marquete (ele pode cobrar matrícula no ato fora do sistema).

---

## Segurança

### Implementado
- CORS restrito a `FRONTEND_URL`.
- Rate limit de 5 req/min na rota de login (proteção anti força-bruta).
- Tokens Sanctum com expiração de 7 dias e prune diário automatizado.
- Endpoint `POST /api/logout-all` para invalidar todos os tokens.
- Middleware `SecurityHeaders` global: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.

### Avaliados e descartados pelo contexto
- **Rate limit em rotas autenticadas:** sistema interno, baixo volume.
- **Timing-safe login:** poucos emails cadastrados, enumeração não é ameaça realista.
- **Content-Security-Policy:** alta complexidade, baixo benefício para API JSON.

### Pendente para o deploy
- HTTPS forçado em produção (Fly.io faz nativo, confirmar).
- Secrets gerenciados pelo Fly.io (não pelo `.env`).
- Backup automático do banco (Neon faz nativo).

---

## Fora do escopo da v1

### Funcionalidades
- Cobrança/pagamento online — aluno paga fora do sistema, Marquete só registra.
- Cadastro feito pelo aluno — apenas Marquete e equipe usam o sistema.
- App mobile nativo — sistema é web, responsivo.
- Avisos automáticos por WhatsApp para inadimplentes (planejado para v2).

### Segurança e operação
- **Reset de senha por email (forgot password):** requer integração com provedor SMTP (SendGrid/Mailgun/Resend), configuração de domínio (SPF/DKIM) e fluxo de duplo endpoint. Substituído por reset operacional via comando Artisan na v1. Considerar para v2 junto com integração de email para avisos.
- **Roles/permissões:** sistema atual não diferencia perfis. Aceitável dado o contexto (poucos usuários, todos de confiança). Reavaliar se o sistema crescer.
