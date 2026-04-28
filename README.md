# Fight House API

> Backend de um sistema de gestão de mensalidades para academia de jiu-jitsu.

[![Tests](https://github.com/Kuligowskilucas/fighthouse-api/actions/workflows/tests.yml/badge.svg)](https://github.com/Kuligowskilucas/fighthouse-api/actions/workflows/tests.yml)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)

API REST construída em Laravel 13 com PostgreSQL e autenticação via Sanctum, desenvolvida como projeto sem fins lucrativos para a Fight House Club. Substitui o controle manual em caderno por um sistema digital com cadastro de alunos, registro de pagamentos, geração automática de mensalidades e dashboard mensal.

> 🇧🇷 Português · [🇺🇸 English](README.en.md) (em breve)

---

## Sobre o projeto

A Fight House Club é uma academia de jiu-jitsu cujo dono, Marquete, hoje gerencia as mensalidades dos ~30 alunos em um caderno físico. Este projeto é uma alternativa digital gratuita, focada em três coisas:

1. **Cadastro centralizado** dos alunos com seus respectivos planos.
2. **Registro de pagamentos** com histórico e cálculo automático de inadimplência.
3. **Visão mensal consolidada** (recebido, a receber, em atraso) — substitui o "ter que somar a mão a cada mês".

A v2 prevê o disparo de mensagens automáticas pelo WhatsApp para alunos inadimplentes; a v1 (este repositório) entrega o backend completo com cobertura de testes e CI.

---

## Stack

- **PHP 8.5** + **Laravel 13**
- **PostgreSQL 16**
- **Laravel Sanctum** (autenticação por token bearer)
- **Docker** via Laravel Sail (ambiente de desenvolvimento)
- **PHPUnit** para testes feature
- **GitHub Actions** para CI

---

## Funcionalidades

### Autenticação
- Login com email e senha, retornando token Sanctum com 7 dias de expiração.
- Logout do dispositivo atual ou de todos os dispositivos (`logout-all`).
- Troca de senha pelo próprio usuário (exige senha atual; invalida outros tokens).

### Cadastros
- **Planos** com nome, valor e frequência semanal.
- **Alunos** identificados por telefone normalizado, com plano vinculado, dia de vencimento e valor personalizado opcional.
- **Mensalidades** únicas por aluno e mês, com status calculado automaticamente (paga, atrasada, aberta).

### Operações sobre mensalidades
- Marcar e desfazer pagamento (registra forma de pagamento e data).
- Filtros por status, aluno e mês de referência.
- **Geração automática** de mensalidades:
  - Comando Artisan agendado para rodar dia 1 de cada mês.
  - Endpoint manual para disparo via frontend.
  - Idempotente — pode rodar múltiplas vezes sem duplicar.

### Dashboard
- Resumo mensal: alunos ativos, totais recebidos, a receber, em atraso.
- Lista de inadimplentes agrupada por aluno, com valor devido e dias de atraso.

### Operação
- Comandos Artisan para criar usuário admin (`user:create`) e resetar senha esquecida (`user:reset-password`).

---

## Setup local

### Pré-requisitos
- Docker e Docker Compose
- Git

> Não é necessário ter PHP, Composer ou Postgres instalados localmente — tudo roda em containers via Laravel Sail.

### Passo a passo

```bash
# 1. Clonar o repositório
git clone https://github.com/Kuligowskilucas/fighthouse-api.git
cd fighthouse-api

# 2. Copiar o .env de exemplo
cp .env.example .env

# 3. Instalar dependências do Composer (executa em container)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

# 4. Subir os containers
./vendor/bin/sail up -d

# 5. Gerar APP_KEY
./vendor/bin/sail artisan key:generate

# 6. Rodar migrations e seeders
./vendor/bin/sail artisan migrate --seed
```

A API estará disponível em `http://localhost`. Em desenvolvimento, o seeder cria o usuário **`marquete@fighthouse.local`** com senha **`senha123`**, além de 30 alunos fictícios e mensalidades variadas.

### Alias do Sail (recomendado)

Para evitar digitar `./vendor/bin/sail` toda vez, adicione ao seu `~/.bashrc` ou `~/.zshrc`:

```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

A partir daí, todos os comandos podem ser invocados como `sail artisan ...`, `sail test`, etc.

---

## Comandos úteis

| Comando | O que faz |
|---|---|
| `sail up -d` | Sobe os containers em background |
| `sail down` | Para os containers |
| `sail artisan migrate:fresh --seed` | Recria o banco do zero com dados de seed |
| `sail artisan test` | Roda toda a suíte de testes |
| `sail artisan tinker` | REPL interativo do Laravel |
| `sail artisan route:list` | Lista todas as rotas |
| `sail artisan schedule:list` | Lista os comandos agendados |
| `sail artisan user:create` | Cria um novo usuário interativamente |
| `sail artisan user:reset-password {email}` | Reseta a senha de um usuário (operação manual) |
| `sail artisan mensalidades:gerar` | Gera mensalidades do mês atual manualmente |

---

## Testes

A suíte cobre os fluxos críticos do sistema (autenticação, cadastros, pagamentos, geração de mensalidades, gerenciamento de usuários) com **41 testes feature**:

```bash
sail artisan test
```

O CI (GitHub Actions) executa a mesma suíte em cada push e pull request para a `main`, em ambiente Ubuntu + Postgres limpo.

---

## API endpoints (resumo)

Todas as rotas (exceto `POST /api/login`) exigem token Sanctum no header `Authorization: Bearer {token}`.

### Autenticação
| Método | Rota | Descrição |
|---|---|---|
| POST | `/api/login` | Login (rate-limited a 5 req/min) |
| POST | `/api/logout` | Logout do dispositivo atual |
| POST | `/api/logout-all` | Logout de todos os dispositivos |
| GET | `/api/me` | Dados do usuário autenticado |
| POST | `/api/me/change-password` | Trocar a própria senha |

### Recursos
| Método | Rota | Descrição |
|---|---|---|
| GET / POST | `/api/users` | Listar / criar usuários |
| GET / POST / PUT / DELETE | `/api/planos` | CRUD de planos |
| GET / POST / PUT / DELETE | `/api/alunos` | CRUD de alunos (filtros: `search`, `ativo`, `plano_id`) |
| GET / POST / PUT / DELETE | `/api/mensalidades` | CRUD de mensalidades (filtros: `status`, `aluno_id`, `mes_referencia`) |

### Ações específicas
| Método | Rota | Descrição |
|---|---|---|
| POST | `/api/mensalidades/{id}/marcar-pagamento` | Registra pagamento |
| POST | `/api/mensalidades/{id}/desfazer-pagamento` | Reverte pagamento |
| POST | `/api/mensalidades/gerar` | Gera mensalidades do mês (manual) |

### Dashboard
| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/dashboard/resumo` | Resumo mensal (aceita `?mes=YYYY-MM-DD`) |
| GET | `/api/dashboard/inadimplentes` | Lista de inadimplentes |

---

## Estrutura do projeto

```
app/
├── Console/Commands/      # Comandos Artisan (user:create, mensalidades:gerar, etc)
├── Http/
│   ├── Controllers/Api/   # Controllers REST
│   ├── Middleware/        # SecurityHeaders
│   ├── Requests/          # Form Requests (validação)
│   └── Resources/         # API Resources (serialização)
├── Models/                # Eloquent models
└── Services/              # GeradorDeMensalidades
database/
├── factories/             # Factories para testes e seeders
├── migrations/            # Schema do banco
└── seeders/               # PlanoSeeder, DatabaseSeeder
routes/
├── api.php                # Rotas da API
└── console.php            # Comandos agendados
tests/
└── Feature/               # Testes feature (41 no total)
```

---

## Decisões técnicas

Decisões de arquitetura, regras de negócio, escolhas de escopo e itens pendentes de validação estão documentados em [DECISOES.md](DECISOES.md).

---

## Licença

Sem licença aberta formal. Código publicado para fins de portfólio e uso da Fight House Club.