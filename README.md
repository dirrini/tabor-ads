# Tabor Ads

Tabor Ads is an ad analytics SaaS platform. It combines pixel-based impression tracking, team workspaces, campaigns with multiple ads, an analytics dashboard, and real-time traffic simulation.

## Current features

- Responsive public landing page with plan details.
- Sign-up and login with email/password or Google OAuth.
- Automatic linking of Google identities to existing accounts with the same email address.
- Isolated workspaces, members, and email invitations.
- Profile page with plan details, Premium expiration date, quota usage, account information, and password creation/change.
- PT-BR and EN interfaces; the public language preference is stored in the browser, while the authenticated preference is also saved to the user profile.
- Standard campaigns with multiple ads and a unique tracking pixel for each ad.
- Unlimited simulation campaigns that do not count toward plan quotas.
- Dashboard with date range, campaign, and ad filters, totals, browser breakdown, period comparison, and daily trends.
- WebSocket updates through private workspace and campaign channels.
- Embedded Mercado Pago checkout with credit card and Pix payments on the same page.
- Global success and error feedback through toast notifications.

> Public tracking currently measures impressions. Click and conversion tracking are not part of this version yet.

## Plans and limits

| Feature | Free | Premium |
| --- | ---: | ---: |
| Active standard campaigns | 3 | 20 |
| Ads per standard campaign | 1 | 10 |
| Workspace members | 1 | 5 |
| Dashboard | On-demand snapshot | Real-time |
| Real-time simulator | Yes | Yes |

`simulation` campaigns do not count toward the campaign quota. When the simulator is enabled, all simulation campaigns receive events every second for up to three minutes. Each campaign receives a different, stable weight for the duration of the session, preventing their series from converging on nearly identical volumes. Starting a new session redistributes the weights.

## Tech stack

- Laravel 13 and PHP 8.4
- Vue 3, Vue Router, Pinia, Vue I18n, Chart.js, and Laravel Echo
- MySQL 8
- Laravel Reverb for WebSockets
- Laravel Socialite for Google OAuth
- Mercado Pago embedded checkout
- Docker Compose and Nginx
- PHPUnit and Laravel Pint

## Architecture

```text
Browser
  `-- frontend :3000 (Nginx + Vue SPA)
      |-- /api, /t, and /broadcasting -> webserver :8080
      `-- /app/{reverb-key}           -> reverb :8080

webserver (Nginx/FastCGI)
  `-- backend (Laravel PHP-FPM)
      `-- db (MySQL 8)
```

Laravel handles authentication, authorization, domain logic, tracking, analytics, billing, webhooks, and broadcasting. Reverb runs within the same Laravel application and publishes only to authorized private channels.

## Run locally

Prerequisite: Docker Desktop with Docker Compose.

1. Copy the example environment file:

```powershell
Copy-Item .env.example .env
```

2. Fill in the optional Google and Mercado Pago credentials.
3. Start the environment:

```powershell
docker compose up -d --build
```

4. Open:

- Application: `http://127.0.0.1:3000`
- API health check: `http://127.0.0.1:8080/up`

Migrations run automatically when the `backend` container starts. The local database is persisted in `data/`, which must not be committed.

To monitor the services:

```powershell
docker compose ps
docker compose logs -f backend reverb frontend
```

## Environment variables

The [.env.example](.env.example) file documents every variable used by Docker Compose. The main categories are:

- Application: `APP_KEY`, `APP_URL`, `FRONTEND_URL`, `SESSION_DOMAIN`.
- MySQL: `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`.
- Google OAuth: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.
- Mercado Pago: `MERCADO_PAGO_PUBLIC_KEY`, `MERCADO_PAGO_ACCESS_TOKEN`, `MERCADO_PAGO_WEBHOOK_SECRET`, prices, and notification URL.
- Reverb: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, host, port, and scheme.

Never commit `.env`, access tokens, private keys, or MySQL volume data.

## Google OAuth

Create a **Web application** OAuth client in Google Cloud and configure:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:3000/api/auth/google/callback
```

The URI registered with Google must match `GOOGLE_REDIRECT_URI` exactly, including its scheme, host, port, and path. In production, replace it with the public HTTPS domain.

When Google returns an email address that already exists, the identity is linked to the current account without removing its password. Users whose accounts were created through Google can set a password later on the profile page.

## Mercado Pago

Use the **Public Key** and **Access Token** from the same application and environment (test or production):

```dotenv
MERCADO_PAGO_BASE_URL=https://api.mercadopago.com
MERCADO_PAGO_PUBLIC_KEY=
MERCADO_PAGO_ACCESS_TOKEN=
MERCADO_PAGO_WEBHOOK_SECRET=
MERCADO_PAGO_PREMIUM_MONTHLY_AMOUNT=1.90
MERCADO_PAGO_PREMIUM_ANNUAL_AMOUNT=9.90
MERCADO_PAGO_CURRENCY=BRL
MERCADO_PAGO_NOTIFICATION_URL=https://ads.your-domain.com/api/webhooks/mercadopago
```

Credit card data is tokenized by the Mercado Pago SDK in the browser; the card number, expiration date, and CVV never pass through Laravel. Pix payments are created through the authenticated API, and the QR code is displayed inside the checkout form. Neither flow redirects users to a Mercado Pago login page.

Prices are never accepted from the frontend: the backend selects the amount based on the validated billing period.

- Monthly Premium: R$1.90 for one month of access.
- Annual Premium: R$9.90 for twelve months of access.
- Both are one-time Pix or credit card payments with manual renewal in this version.

Configure the Mercado Pago payment webhook:

```text
POST https://ads.your-domain.com/api/webhooks/mercadopago
```

When configured, the webhook validates the request signature, processes events idempotently, and retrieves the payment again before changing the local subscription. Approved payments activate or extend Premium; cancellations, refunds, and chargebacks remove the associated access period.

Mercado Pago cannot deliver webhooks to a local or private URL. For testing, use an HTTPS tunnel and set `MERCADO_PAGO_NOTIFICATION_URL` to its public URL.

## Development and validation

Run the frontend with Vite:

```powershell
Set-Location frontend
npm ci
npm run dev
```

Create a production build:

```powershell
Set-Location frontend
npm run build
```

Run Laravel tests and style checks:

```powershell
docker compose exec -T backend php artisan test
docker compose exec -T backend vendor/bin/pint --test
```

Validate the complete container setup:

```powershell
docker compose config --quiet
docker compose build backend frontend
```

## CI/CD

### Continuous integration

The `Continuous Integration` workflow runs on pushes and pull requests targeting `main` and `develop`:

- PHP 8.4, Composer, Laravel Pint, and the complete PHPUnit test suite.
- Node.js 22, `npm ci`, and the Vite production build.
- Docker Compose validation and builds of the main images.

### OCI deployment

The `Deploy to OCI Production` workflow starts only after CI succeeds on `main`, or manually through `workflow_dispatch`. It:

1. Connects to the server over SSH.
2. Deploys the exact commit SHA validated by CI.
3. Confirms that the production `.env` file exists.
4. Validates the Compose configuration and recreates only the required services.
5. Checks `http://127.0.0.1:8080/up`.
6. Rolls back to the previous commit if the health check fails.

Required secrets in the GitHub `Production` environment:

- `OCI_SERVER_IP`
- `OCI_SERVER_USER`
- `OCI_SSH_KEY`

The server must have Git, Docker with Compose, read access to the repository, and a persistent `~/impression-track/.env` file. The SSH user must be able to run `sudo docker compose` non-interactively. The public domain and TLS termination must point to the local services exposed by Compose.

## Project structure

```text
.github/workflows/      CI and OCI deployment
api/                    Laravel application, migrations, and tests
config/nginx.conf       API Nginx/FastCGI configuration
frontend/               Vue landing page and application
data/                   Git-ignored local MySQL volume
docker-compose.yml      Integrated environment
```

The previous PHP and WebSocket implementations have been removed. The single backend source is `api/`, and Laravel Reverb is the official real-time server.
