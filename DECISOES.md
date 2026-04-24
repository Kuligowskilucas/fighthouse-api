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