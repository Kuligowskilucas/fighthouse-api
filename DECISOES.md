# Decisões do projeto

Documento vivo com as decisões técnicas e de negócio tomadas durante o desenvolvimento. Itens marcados com **A validar** dependem de confirmação com o Marquete.

> Última atualização: maio de 2026 (pós deploy em produção).

---

## Stack

- **Backend:** Laravel 13 + PostgreSQL + Sanctum
- **Frontend:** Next.js 15 + TypeScript + Tailwind + shadcn/ui (em outro repositório)
- **Infraestrutura dev:** Docker (Laravel Sail) sobre WSL2 + Ubuntu
- **Infraestrutura prod:** Oracle Cloud Always Free (Ubuntu 24.04, VM.Standard.E2.1.Micro, São Paulo) + Supabase (Postgres gerenciado em São Paulo) + Vercel (frontend) + DuckDNS (DNS) + Let's Encrypt via certbot (SSL)
- **Custo operacional total:** R$ 0/mês

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

## Infraestrutura de produção

### Por que Oracle Cloud Always Free + Supabase (e não Fly.io + Neon)

O plano original era Fly.io para o backend e Neon para o Postgres. Mudança feita pelos seguintes motivos:

- **Restrição absoluta de custo zero** (compromisso com o Marquete: sistema 100% gratuito, sem cartão exigido pra manter no ar).
- Fly.io free tier exige cartão e tem limites de RAM que podem virar cobrança em pico.
- Oracle Cloud Always Free é genuinamente gratuito de forma permanente.
- Supabase free tier dá 500MB de Postgres na região São Paulo, suficiente pra escala do projeto.

Trade-off aceito: Oracle Always Free exige configurar o servidor do zero (nginx, PHP-FPM, firewall, SSL). Fly.io seria push-and-deploy, mais simples. Aceito conscientemente — virou aprendizado de DevOps real.

### Por que VM.Standard.E2.1.Micro (e não Ampere ARM)

Ampere A1.Flex (até 4 OCPU + 24 GB no free tier) é o shape ideal do Always Free, mas estava sem capacidade em São Paulo no momento do provisionamento (capacity issue conhecido da Oracle). E2.1.Micro (AMD, 1 OCPU + 1 GB RAM) foi o fallback. Suficiente pro caso de uso, com 2 GB de swap como rede de segurança.

### Por que nginx + PHP-FPM nativos (e não Docker em produção)

Em vez de subir Sail ou um Dockerfile customizado pra produção:

- **Menor consumo de RAM** — crucial na E2.1.Micro com apenas 1 GB.
- **Menos camadas entre o sistema e o app** — debug fica mais direto, error logs mais transparentes.
- **Skill mais valorizada no CV** — configurar Linux server do zero é fundamento; Docker em prod é incremento sobre isso.

Trade-off aceito: divergência dev/prod (Sail em dev, nativo em prod). Mitigado pelo Laravel ser portável e pelos comandos artisan funcionarem igual nos dois lados.

### Por que Ubuntu 24.04 LTS (e não Oracle Linux 9)

- Familiaridade — WSL local também é Ubuntu, mesmos comandos `apt`, mesmos paths.
- PHP 8.3+ disponível no repositório padrão.
- Comunidade Laravel/PHP gravita ao Debian/Ubuntu, tutoriais e documentação batem mais fácil.

### Versão do PHP: 8.4 via PPA Ondrej

O apt do Ubuntu 24.04 entrega PHP 8.3 por padrão. Mas o `composer.lock` gerado no ambiente local (que roda PHP 8.5 via Sail) travou Symfony 8.0.8, que requer PHP ≥ 8.4. Resultado: `composer install` falhou no servidor com mismatch de versão.

Resolução: adicionar o PPA do Ondrej Sury (`ppa:ondrej/php`), instalar PHP 8.4 + extensões necessárias, configurar `update-alternatives` pro CLI, remover PHP 8.3.

Lição registrada: **paridade de versão entre dev e prod importa**. Considerar travar a versão maior do PHP em produção pra evitar deriva similar no futuro.

### Por que DuckDNS (e não domínio comprado)

Restrição zero custo: domínio próprio custaria R$ 40+/ano. DuckDNS dá subdomínio grátis pra sempre, suporta atualização via API, e funciona com Let's Encrypt.

A API tá acessível em `fighthouseapi.duckdns.org`.

Trade-off aceito: subdomínio menos profissional que `api.fighthouseclub.com.br`. A alternativa quebraria a promessa do "zero custo". Migração futura pra domínio próprio é trivial — atualiza A record do novo domínio, refaz certbot, atualiza CORS.

### Firewall em duas camadas

- **Security List da Oracle** (firewall de rede, gerenciado no console): libera 22 (SSH), 80, 443. Bloqueia tudo o resto.
- **iptables no Ubuntu** (firewall do OS): as imagens Ubuntu da Oracle vêm com `REJECT` padrão pra praticamente tudo exceto SSH. Adicionei regras explícitas pra 80 e 443.

Decisão de defense in depth: dois firewalls em camadas diferentes. Se um for mal configurado ou tiver bug, o outro ainda protege. Regras salvas via `netfilter-persistent save` pra sobreviver reboot.

### Reserved IP (e não Ephemeral)

IPs ephemeral são liberados quando a VM para. Reserved fica permanente. Always Free dá 2 IPs reservados grátis. Decisão tomada por estabilidade — o DuckDNS aponta pra esse IP; se mudasse, precisaria atualizar manualmente.

### Supabase: Session Pooler (e não Transaction Pooler ou Direct)

Três modos de conexão disponíveis no Supabase:

- **Direct connection** (porta 5432, IPv6 only no free tier): a VM Oracle Always Free é IPv4 only por padrão, então não funciona.
- **Transaction Pooler** (porta 6543, PgBouncer em transaction mode): conhecidamente tem issues com prepared statements do Laravel/Eloquent.
- **Session Pooler** (porta 5432, PgBouncer em session mode): funciona limpo com Laravel.

Escolha: Session Pooler.

### Supabase: Data API desabilitada

Supabase pode auto-gerar REST endpoints (PostgREST) pra cada tabela no schema `public`. Como o Laravel cria nossas tabelas justamente no `public`, manter essa feature ligada exporia automaticamente `alunos`, `mensalidades`, `users` via REST quem soubesse a `anon key`.

Decisão: desabilitei totalmente "Enable Data API" no momento de criação do projeto. O Laravel é o único consumidor do Postgres; ele é o gatekeeper único.

### SSL via Let's Encrypt + certbot

- Plugin `python3-certbot-nginx` automatiza emissão do cert e modificação do vhost.
- Auto-renew via `certbot.timer` (systemd unit), tenta a cada 12h e renova quando faltam ≤ 30 dias.
- HTTP redireciona pra HTTPS automaticamente (configurado pelo certbot).
- Cert válido por 90 dias.

### Scheduler via cron

Entrada de crontab rodando `* * * * * php artisan schedule:run` (a cada minuto). Laravel decide internamente quais comandos devem executar naquele tick.

Comandos agendados hoje:
- `mensalidades:gerar` — dia 1 de cada mês.
- `sanctum:prune-expired` — diariamente.

Overhead da execução a cada minuto: ~300ms de CPU e ~40 MB de RAM por invocação (liberados após o processo encerrar). Desprezível na E2.1.Micro.

### Swap de 2 GB

Compensa o limite de 1 GB de RAM da E2.1.Micro. `vm.swappiness=10` pra forçar uso de swap apenas em pico real, não em uso normal. Sem isso, um pico que estourasse a memória poderia derrubar nginx ou PHP-FPM via OOM killer.

---

## Segurança

### Implementado
- CORS restrito a `FRONTEND_URL`.
- Rate limit de 5 req/min na rota de login (proteção anti força-bruta).
- Tokens Sanctum com expiração explícita de 7 dias (`createToken()` com `expiresAt`) e prune diário automatizado via `sanctum:prune-expired`.
- Endpoint `POST /api/logout-all` para invalidar todos os tokens do usuário.
- Middleware `SecurityHeaders` global: `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Permissions-Policy` restritivo.
- HTTPS forçado em produção via redirect 301 do nginx (configurado pelo certbot).

### Filosofia: calibrar segurança ao contexto real

Decisões neste projeto seguem o princípio de **calibrar ao contexto** em vez de aplicar checklists genéricos. Como o sistema é interno (só administração usa), com pequena base de usuários conhecidos, dados não-financeiros (nomes e mensalidades), algumas práticas comuns foram **avaliadas e descartadas conscientemente** — registradas aqui pra que, se o sistema escalar, a reavaliação seja explícita:

- **Rate limit em rotas autenticadas:** sistema interno, baixo volume. O login é o vetor real de ataque, e ele já tem throttle.
- **Login timing-safe:** enumeração de usuários não é ameaça realista com base pequena de emails conhecidos.
- **Content-Security-Policy:** alta complexidade de manutenção, baixo benefício pra API JSON pura.
- **Roles e permissões granulares:** todos os usuários do sistema são de confiança. Adicionar RBAC agora seria overhead sem ganho.
- **Auditoria de operações:** nice-to-have, não essencial nesse momento.

---

## Fora do escopo da v1

### Funcionalidades
- Cobrança/pagamento online — aluno paga fora do sistema, Marquete só registra.
- Cadastro feito pelo próprio aluno — apenas Marquete e equipe usam o sistema.
- App mobile nativo — sistema é web, responsivo, mobile-first.
- Notificações automáticas por WhatsApp/email pra inadimplentes (planejado pra v2).
- Reset de senha por email — requer integração SMTP, configuração de domínio (SPF/DKIM) e fluxo de duplo endpoint. Substituído por reset operacional via comando Artisan na v1. Considerar pra v2 junto com email pra notificações.
- Integração com catraca física pra controle de entrada — planejada pra v2 quando o equipamento estiver instalado na academia.

### Operação
- Backups automatizados do Postgres — Supabase faz nativo no plano free (point-in-time recovery limitado), considerar plano pago se o sistema crescer.
- Monitoring formal — pra v1, suficiente conferir manualmente via dashboard. Pra v2, considerar UptimeRobot (free) pra ping em `/up`.