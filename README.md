# Amoeba — digital games marketplace

An Eneba-style marketplace for game keys, built as an intern assignment.
**Amoeba** is a playful stand-in brand: styling follows eneba.com, but no Eneba logos or assets are used.

> Work in progress. The full README (architecture, decisions, scope) comes in the final step.

## Stack

| Layer | Choice |
|---|---|
| API | Laravel 12 (REST only), Sanctum bearer tokens — `backend/` |
| Frontend | React + TypeScript + Vite, React Router — `frontend/` |
| Database | MySQL 8.4 locally, Aiven MySQL in production |
| Hosting | API on Render (Docker), frontend on Vercel |

## Local setup

Requirements: PHP 8.5, Composer 2, Node 24, MySQL 8.4 with databases `eneba` and `eneba_test`.

```bash
# API → http://localhost:8000/api
cd backend
composer install
cp .env.example .env        # then fill in DB_PASSWORD
php artisan key:generate
php artisan migrate
php artisan serve

# Frontend → http://localhost:5173
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

Tests run against the separate `eneba_test` MySQL database (not SQLite, because SQLite
ignores the row locks the checkout relies on):

```bash
cd backend
php artisan test
```

## Deployment

- **API:** Render Blueprint in `render.yaml` (Docker, `backend/Dockerfile`). On start the
  container caches config, runs migrations and starts Apache. Health check: `GET /api/health`.
- **Frontend:** Vercel project with root directory `frontend`; `vercel.json` rewrites all
  paths to `index.html` so client-side routes work on refresh.
- **Database:** Aiven free MySQL over TLS; its CA certificate is a Render secret file.
