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

### First-time deploy, step by step

Do these in order: each service needs a value from the one before it.

1. **GitHub** — create an empty repository, then push this project:
   ```bash
   git remote add origin https://github.com/<you>/<repo>.git
   git push -u origin main
   ```

2. **Aiven (database)** — at [console.aiven.io](https://console.aiven.io), create a free
   **MySQL** service. When it is running, note from its *Overview* page:
   - host, port, user (`avnadmin`), password; the database is `defaultdb`
   - **CA certificate**: download it (`ca.pem`)

3. **Render (API)** — at [dashboard.render.com](https://dashboard.render.com):
   1. **New → Blueprint**, pick the GitHub repo. Render reads `render.yaml`.
   2. Fill in the secrets it asks for:

      | Variable | Value |
      |---|---|
      | `APP_KEY` | output of `php artisan key:generate --show` (run in `backend/`) |
      | `APP_URL` | the Render URL, e.g. `https://amoeba-api.onrender.com` |
      | `FRONTEND_URL` | the Vercel URL from step 4 (use a placeholder until then) |
      | `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | from Aiven |

   3. Service → **Environment → Secret Files**: add a file named `aiven-ca.pem`
      with the contents of Aiven's `ca.pem`. It is mounted at `/etc/secrets/aiven-ca.pem`.
   4. Deploy, then open `https://<render-url>/api/health`. Expect
      `{"status":"ok","database":"ok",...}`.

   > **Keep `APP_KEY` safe and never change it.** Game key codes are encrypted with it;
   > a new key makes every stored code unreadable.
   >
   > The free plan sleeps after ~15 minutes idle; the first request afterwards takes ~1 minute.

4. **Vercel (frontend)** — at [vercel.com/new](https://vercel.com/new), import the repo:
   - **Root Directory:** `frontend` (framework preset: Vite)
   - **Environment variables:** `VITE_API_URL=https://<render-url>/api`, `VITE_APP_NAME=Amoeba`
   - Deploy and copy the URL (e.g. `https://amoeba.vercel.app`).

5. **Connect them** — in Render, set `FRONTEND_URL` to the Vercel URL (no trailing slash)
   and redeploy, so CORS allows the browser to call the API. Several origins can be
   comma-separated: `https://amoeba.vercel.app,http://localhost:5173`.

6. **Check** — open the Vercel URL. The status page should show
   *API status: ok* and *Database: ok*.

| Symptom | Likely cause |
|---|---|
| Status page: "Could not reach the server" | `FRONTEND_URL` doesn't exactly match the Vercel URL (CORS), or the API is still waking up |
| `/api/health` returns `"database":"unreachable"` | wrong DB credentials, or the CA secret file is missing/misnamed (check Render logs) |
| Render build fails at `start.sh` | script saved with CRLF line endings (`.gitattributes` should prevent this) |
| Vercel page 404s on refresh | Root Directory isn't `frontend`, so `vercel.json` wasn't picked up |
