# Decisões do projeto

Decisões tomadas no início do desenvolvimento. Validar com o Marquete quando possível e atualizar aqui.

## Regras de negócio

### Valor do plano
- O valor é definido pelo plano, mas cada aluno pode ter um `valor_personalizado` opcional que sobrescreve.
- Motivo: cobrir casos de desconto de amizade, aluno antigo, bolsista, etc.
- **A validar:** existem casos assim hoje?

### Dia de vencimento
- Cada aluno tem seu próprio dia de vencimento, baseado na data de matrícula.
- Ex.: matriculou dia 12, vence todo dia 12.
- **A validar:** o Marquete prefere um dia fixo pra todo mundo (tipo dia 5 ou 10)?

### Tolerância de atraso
- Zero dias. Passou do vencimento, tá atrasado.
- Fácil de mudar pra uma tolerância (ex.: 3 dias) depois.
- **A validar:** tem uma prática atual na academia?

### Pagamento atrasado
- Quita o mês em aberto (o vencido), não o atual.
- Ex.: venceu março, paga em abril → quita março. Abril continua em aberto.
- **A validar:** é assim que ele pensa?

## Planos iniciais (da academia Fight House Club)

- Plano Livre — R$ 249,90 — frequência livre
- Muaythai ou Jiu Jitsu (masculino) — R$ 169,90 — 3x/semana
- Boxe / Muaythai Feminino / Jiu Jitsu Feminino — R$ 129,90 — 2x/semana
- **A validar:** esses são os únicos planos? Preços ainda estão atuais?

## Fora do escopo da v1

- Cobrança/pagamento online (aluno paga fora do sistema, Marquete só registra)
- Cadastro feito pelo aluno (apenas Marquete e recepção usam o sistema)
- App mobile nativo (sistema é web, responsivo)

## Stack

- Backend: Laravel 13 + PostgreSQL + Sanctum
- Frontend: Next.js 15 + TypeScript + Tailwind + shadcn/ui
- Infra: Docker (Sail) em dev, hospedagem a definir

cat >> DECISOES.md << 'EOF'

## Gerenciamento de usuários

### Implementado na v1
- `POST /api/users`: qualquer usuário autenticado pode criar outros usuários (sistema interno, todos são pessoas de confiança).
- `POST /api/me/change-password`: trocar a própria senha. Exige senha atual como prova de identidade. Invalida todos os outros tokens, mantém o atual.
- `php artisan user:create`: comando interativo para criar o primeiro admin no deploy.
- `php artisan user:reset-password {email}`: comando operacional para resetar senha esquecida (Lucas executa quando solicitado). Invalida todos os tokens do usuário.

### Requisitos de senha
- Mínimo 8 caracteres
- Pelo menos 1 letra
- Pelo menos 1 número
- Aplicado em criação, troca e reset.

### Fora do escopo da v1
- **Reset de senha por email (forgot password):** requer integração com provedor SMTP (SendGrid/Mailgun/Resend), configuração de domínio (SPF/DKIM) e fluxo de duplo endpoint (forgot/reset). Substituído por reset operacional via comando Artisan na v1. Considerar para v2 quando a integração de email para avisos de mensalidade for implementada.
- **Roles/permissões:** sistema atual não diferencia "admin" de "operador comum". Aceitável dado o contexto (poucos usuários, todos de confiança). Reavaliar se o sistema crescer.


## Geração de mensalidades

### Implementado na v1
- **Service `GeradorDeMensalidades`**: lógica única, reutilizada por comando e endpoint.
- **Comando `php artisan mensalidades:gerar`**: aceita `--mes=YYYY-MM` opcional. Agendado para rodar dia 1 de cada mês à meia-noite via `Schedule::command()`.
- **Endpoint `POST /api/mensalidades/gerar`**: aceita `mes_referencia` opcional no body. Para uso manual via frontend ("gerar mensalidades do mês").

### Regras de negócio
- Gera apenas para alunos com `ativo = true`.
- Aluno cadastrado após o último dia do mês de referência **não** recebe mensalidade retroativa.
- Valor: usa `valor_personalizado` se existir, senão `plano->valor`.
- Vencimento: `dia_vencimento` do aluno no mês de referência, ajustado para último dia do mês quando o dia não existe (ex: dia 31 em fevereiro vira 28).
- Idempotente: rodar duas vezes no mesmo mês não duplica (proteção via `firstOrCreate` + unique constraint `[aluno_id, mes_referencia]`).

### A validar com o Marquete (decisão tomada por padrão)
- **Não gera mensalidade retroativa para alunos novos.** Aluno que matricula dia 15 do mês não recebe mensalidade desse mês — começa a pagar a partir do mês seguinte. Decisão tomada por simplicidade e por ser prática comum em academias. Confirmar se faz sentido.
