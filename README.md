# Tabor Ads

Tabor Ads é uma plataforma SaaS de analytics para anúncios. Ela combina tracking de impressões por pixel, workspaces com equipes, campanhas com múltiplos ads, dashboard analítico e demonstração de tráfego em tempo real.

## Recursos atuais

- Landing page pública responsiva com apresentação dos planos.
- Cadastro e login por e-mail/senha ou Google OAuth.
- Associação automática do Google a uma conta existente com o mesmo e-mail.
- Workspaces isolados, membros e convites por e-mail.
- Perfil com plano, validade do Premium, uso dos limites, dados da conta e troca/criação de senha.
- Interface em PT-BR e EN; a preferência pública fica no navegador e a autenticada também é salva no usuário.
- Campanhas padrão com múltiplos ads e pixel exclusivo por ad.
- Campanhas de simulação ilimitadas, fora da cota do plano.
- Dashboard com filtros por período, campanhas e ads, totais, navegadores, comparação entre períodos e evolução diária.
- Atualizações por WebSocket em canais privados do workspace/campanha.
- Checkout transparente do Mercado Pago com cartão e Pix na própria página.
- Feedback global de sucesso e erro por toast.

> O tracking público atual mede impressões. Rastreamento de cliques e conversões ainda não faz parte desta versão.

## Planos e limites

| Recurso | Free | Premium |
| --- | ---: | ---: |
| Campanhas padrão não arquivadas | 3 | 20 |
| Ads por campanha padrão | 1 | 10 |
| Membros no workspace | 1 | 5 |
| Dashboard | Snapshot sob demanda | Realtime |
| Simulador em realtime | Sim | Sim |

Campanhas `simulation` não consomem a cota de campanhas. Ao ligar o simulador, todas elas recebem eventos a cada segundo por até três minutos. Cada campanha recebe um peso estável e diferente durante a sessão, evitando séries com volumes praticamente iguais. Uma nova sessão redistribui os pesos.

## Stack

- Laravel 13 e PHP 8.4
- Vue 3, Vue Router, Pinia, Vue I18n, Chart.js e Laravel Echo
- MySQL 8
- Laravel Reverb para WebSockets
- Laravel Socialite para Google OAuth
- Mercado Pago Checkout Transparente
- Docker Compose e Nginx
- PHPUnit e Laravel Pint

## Arquitetura

```text
Browser
  └─ frontend :3000 (Nginx + Vue SPA)
       ├─ /api, /t e /broadcasting → webserver :8080
       └─ /app/{reverb-key}         → reverb :8080

webserver (Nginx/FastCGI)
  └─ backend (Laravel PHP-FPM)
       └─ db (MySQL 8)
```

O Laravel concentra autenticação, autorização, domínio, tracking, analytics, billing, webhooks e broadcasting. O Reverb reutiliza a mesma aplicação Laravel e publica somente em canais privados autorizados.

## Executar localmente

Pré-requisitos: Docker Desktop com Docker Compose.

1. Copie o arquivo de exemplo:

```powershell
Copy-Item .env.example .env
```

2. Preencha as credenciais opcionais de Google e Mercado Pago.
3. Suba o ambiente:

```powershell
docker compose up -d --build
```

4. Acesse:

- Aplicação: `http://127.0.0.1:3000`
- Health check da API: `http://127.0.0.1:8080/up`

As migrations são executadas automaticamente quando o contêiner `backend` inicia. O banco local persiste em `data/`, que não deve ser versionado.

Para acompanhar os serviços:

```powershell
docker compose ps
docker compose logs -f backend reverb frontend
```

## Variáveis de ambiente

O arquivo [.env.example](.env.example) documenta todas as variáveis usadas pelo Compose. As principais categorias são:

- Aplicação: `APP_KEY`, `APP_URL`, `FRONTEND_URL`, `SESSION_DOMAIN`.
- MySQL: `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`.
- Google OAuth: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.
- Mercado Pago: `MERCADO_PAGO_PUBLIC_KEY`, `MERCADO_PAGO_ACCESS_TOKEN`, `MERCADO_PAGO_WEBHOOK_SECRET`, preços e URL de notificação.
- Reverb: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, host, porta e esquema.

Nunca versione `.env`, Access Tokens, chaves privadas ou dados do volume MySQL.

## Google OAuth

Crie um cliente OAuth do tipo **Aplicativo da Web** no Google Cloud e configure:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:3000/api/auth/google/callback
```

A URI cadastrada no Google deve ser idêntica a `GOOGLE_REDIRECT_URI`, incluindo protocolo, host, porta e caminho. Em produção, substitua pelo domínio HTTPS público.

Quando o Google retorna um e-mail já existente, a identidade é vinculada à conta atual sem remover sua senha. Para uma conta nova criada pelo Google, o usuário pode definir posteriormente uma senha na tela de perfil.

## Mercado Pago

Use a **Public Key** e o **Access Token** da mesma aplicação e do mesmo ambiente (teste ou produção):

```dotenv
MERCADO_PAGO_BASE_URL=https://api.mercadopago.com
MERCADO_PAGO_PUBLIC_KEY=
MERCADO_PAGO_ACCESS_TOKEN=
MERCADO_PAGO_WEBHOOK_SECRET=
MERCADO_PAGO_PREMIUM_MONTHLY_AMOUNT=1.90
MERCADO_PAGO_PREMIUM_ANNUAL_AMOUNT=9.90
MERCADO_PAGO_CURRENCY=BRL
MERCADO_PAGO_NOTIFICATION_URL=https://ads.seu-dominio.com/api/webhooks/mercadopago
```

O cartão é tokenizado pelo SDK do Mercado Pago no navegador; número, validade e CVV não passam pelo Laravel. O Pix é criado pela API autenticada e o QR Code é exibido dentro do próprio checkout. Nenhum dos fluxos redireciona o usuário para uma página de login do Mercado Pago.

Os preços nunca são aceitos do frontend: o backend escolhe o valor pela periodicidade validada.

- Premium mensal: R$ 1,90 e um mês de acesso.
- Premium anual: R$ 9,90 e doze meses de acesso.
- Ambos são pagamentos únicos por Pix ou cartão, com renovação manual nesta versão.

Configure no Mercado Pago o webhook de pagamentos:

```text
POST https://ads.seu-dominio.com/api/webhooks/mercadopago
```

O webhook valida a assinatura quando configurada, processa eventos de forma idempotente e consulta novamente o pagamento antes de alterar a assinatura local. Pagamentos aprovados ativam ou estendem o Premium; cancelamentos, estornos e chargebacks removem o período associado.

Uma URL local ou privada não pode receber webhooks do Mercado Pago. Para testes, use um túnel HTTPS e configure `MERCADO_PAGO_NOTIFICATION_URL` com a URL pública.

## Desenvolvimento e validação

Frontend com Vite:

```powershell
Set-Location frontend
npm ci
npm run dev
```

Build de produção:

```powershell
Set-Location frontend
npm run build
```

Testes e estilo do Laravel:

```powershell
docker compose exec -T backend php artisan test
docker compose exec -T backend vendor/bin/pint --test
```

Validação completa dos contêineres:

```powershell
docker compose config --quiet
docker compose build backend frontend
```

## CI/CD

### Integração contínua

O workflow `Continuous Integration` roda em pushes e pull requests para `main` e `develop`:

- PHP 8.4, Composer, Laravel Pint e toda a suíte PHPUnit.
- Node.js 22, `npm ci` e build Vite.
- Validação do Docker Compose e build das imagens principais.

### Deploy em OCI

O workflow `Deploy to OCI Production` inicia somente após o CI da `main` concluir com sucesso, ou manualmente por `workflow_dispatch`. Ele:

1. Conecta ao servidor por SSH.
2. Faz deploy do SHA exatamente validado pelo CI.
3. Confirma a existência do `.env` de produção.
4. Valida o Compose e recria somente os serviços necessários.
5. Verifica `http://127.0.0.1:8080/up`.
6. Volta ao commit anterior se o health check falhar.

Secrets necessários no environment GitHub `Production`:

- `OCI_SERVER_IP`
- `OCI_SERVER_USER`
- `OCI_SSH_KEY`

O servidor deve ter Git, Docker com Compose, acesso de leitura ao repositório e um arquivo persistente `~/impression-track/.env`. O usuário SSH precisa executar `sudo docker compose` sem interação. O domínio público e TLS devem apontar para os serviços locais expostos pelo Compose.

## Estrutura principal

```text
.github/workflows/      CI e deploy em OCI
api/                    Aplicação Laravel, migrations e testes
config/nginx.conf       Nginx/FastCGI da API
frontend/               Landing page e aplicação Vue
data/                   Volume MySQL local ignorado pelo Git
docker-compose.yml      Ambiente integrado
```

As implementações PHP e WebSocket anteriores foram removidas. A fonte única do backend é `api/`, e o servidor realtime oficial é o Laravel Reverb.
