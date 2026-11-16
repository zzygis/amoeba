# Amoeba — digital games marketplace

An Eneba-style marketplace for game keys, built as an intern assignment: browse and search
the catalog, compare sellers' offers, fill a cart, sign in, pay (simulated) and get your keys.

**Amoeba** is the app's name; layout, colours and typography (Metropolis) follow eneba.com,
and the logo pairs Eneba's swirl mark (`frontend/public/favicon.svg`) with the "amoeba" name.
It is a demo: **no real payments are taken, the keys are randomly generated and the sellers
are made up**. Game facts come from Steam's public store API; images and trailers are loaded
from Steam's CDN.

- Live site: _add the Vercel URL here after deploying_ (admin: `/admin`)
- API health check: _add `https://<render-url>/api/health` here_

## The assignment, part by part

| Requirement | Where it is |
|---|---|
| **Store**: browse everything, filter (platform, region, price), change the order, search by name | `/store`: sidebar filters (product type, platform, region, price range), 7 sort orders, numbered pages, removable filter chips; all state in the URL so results can be shared |
| **Homepage** from blocks, each fed by the backend, nothing hardcoded | `/`: the blocks (banner, platform tiles, product rows), their titles, order and settings live in the `home_blocks` table; each block type has its own content class in `backend/app/Home`; `GET /api/home` returns them and React only draws block types. Admins rename, reorder or hide blocks at `/admin/homepage` |
| **Search** from anywhere, coping with typos and partial names | Header search on every page with suggestions while typing; `App\Support\TitleSearch` matches partial words and typos ("eldn rin" → Elden Ring, "cyberpnk" → Cyberpunk 2077) |
| **Product page** with details and offers from several sellers | `/games/:slug`: a featured offer (cheapest seller with keys) plus "Recommended offers": every seller with rating, verified badge, price, discount and stock, each with Buy now / Add to cart. Also trailer, screenshots, description, activation guide, PC requirements, other platforms, "Gamers also viewed", reviews |
| **Checkout**: cart, pay, receive the key | Cart → Payment (simulated card/PayPal) → order page with the keys (copy buttons) and an email with the keys |
| **Authentication**; checkout and order history only when signed in | Register, log in, forgot/reset password, account settings. `/checkout`, `/orders`, `/wishlist`, `/account` require sign-in; the guest cart is kept and merged in at login |
| **Eneba styling** | Eneba's palette (purple page, dark cards, yellow actions, green discounts), Metropolis font, square components, their cart → payment → "get your product" steps |

## More features

- **Wishlist** (heart on every product), **reviews** (only by customers who bought the game), **discount codes** in the cart
- **Order history** with the keys, **order confirmation email**
- **Admin panel** at `/admin`, separate from the shop (own layout and sign-in): sellers' prices and key uploads, homepage blocks, discount codes, recent orders
- **DLC and software** next to games, with a product-type filter
- Works on phones (checked at 375 px)

Demo discount codes: `WELCOME10` (10% off) and `AMOEBA20` (20% off).

## Stack

| Layer | Choice |
|---|---|
| API | Laravel 12 (REST only), Sanctum bearer tokens — `backend/` |
| Frontend | React 19 + TypeScript + Vite, React Router, CSS Modules — `frontend/` |
| Database | MySQL 8.4 locally, Aiven MySQL in production |
| Hosting | API on Render (Docker), frontend on Vercel |
| Tests | PHPUnit feature tests (87) against real MySQL; Vitest unit tests (14) |

## Architecture

```
frontend/ (React SPA)  ──HTTPS JSON──▶  backend/ (Laravel API)  ──▶  MySQL
  pages/       shop pages, one per route   routes/api.php        every endpoint
  admin/       the separate admin area     Http/Controllers/Api
  components/  shared UI                   Http/Resources        JSON shapes (mirrored in frontend/src/types.ts)
  auth/ cart/ wishlist/  React contexts    Models/               Eloquent models
  api/client.ts  the only place that       Home/                 one class per homepage block type
                 calls fetch()             Support/TitleSearch   typo-tolerant search
                                           database/data/games.json  the catalog
```

### Data model

```
games ──< products ──< offers >── sellers
  │          │            │
  │          │            └──< product_keys >── order_items >── orders >── users
  │          │                                                     │
  └< reviews (per game)   wishlist_items (per product), cart_items (per offer) ┘
coupons, home_blocks
```

- **games**: what's the same on every platform: description, developer, genres, screenshots, trailer, requirements.
- **products**: a listing: one game on one platform in one region ("Elden Ring Steam Key GLOBAL"). It has a list price but no price of its own.
- **sellers / offers**: each seller sells a product at their own price, with their own keys. The product's "From €X" is its cheapest offer that still has keys.
- **product_keys**: one row per activation code, belonging to an offer. `sold_at` + `order_item_id` are set when it's sold.
- **cart_items**: an offer and a quantity, so the same game from two sellers is two lines.
- **orders / order_items**: what was bought, with product name, seller name and price *as they were at checkout*.
- **home_blocks**: the homepage's blocks (type, title, position, shown/hidden, settings).

### Decisions worth knowing

- **Money is integer cents.** Floats can't represent 0.10 exactly; cents never round wrongly. Formatting to "€12.99" happens only in the browser.
- **Stock and "From" price aren't columns.** Stock is the count of unsold keys, and a product's price is a subquery for its cheapest offer that has keys (`Product::scopeCatalog`), so neither can disagree with the real keys and offers.
- **No overselling.** Checkout runs in one DB transaction and locks the keys it picks with `SELECT … FOR UPDATE`; a second buyer of the last key waits, then sees it as sold. Offers are always locked in id order so two checkouts can't deadlock. If any line is short, the whole order rolls back. Keys always come from the seller the buyer chose. Tested in `CheckoutTest`.
- **Key codes are encrypted at rest** (Laravel's `encrypted` cast, using `APP_KEY`) and hidden from JSON; they're only shown to the buyer on their order page and in their email. Losing or changing `APP_KEY` makes stored codes unreadable.
- **Prices come from the server, always.** The guest cart in localStorage only stores `{offer_id, quantity}`; `/cart/preview` prices it and reports each seller's stock. Coupons are re-checked at checkout and the discount is computed server-side (rounded down, the same as the cart's preview).
- **Order lines are snapshots** of name, seller and price, so later changes don't rewrite history.
- **Typo-tolerant search** (`TitleSearch`): every typed word must match a title word, where match means contains it ("eld" → "elden") or is within a small edit distance (Levenshtein: none for 1–3 letters, 1 up to 6, 2 above). Accents, punctuation and case are ignored. The catalog is small, so titles are compared in PHP; with thousands of products this would move to a search engine (Meilisearch/Elasticsearch) or MySQL full-text with trigrams.
- **Homepage blocks are data.** Adding a new kind of block means one backend class and one React component; changing which blocks show, their titles or order needs no deploy. The seeder uses `firstOrCreate`, so redeploys don't undo an admin's changes.
- **Reviews belong to the game**, not the listing, so a review of the Steam version shows on the Xbox page too. "Bought it" means any listing of that game.
- **"Most popular"** ranks by the game's real Steam review count, a public popularity signal (we have no sales history of our own).
- **Auth: Sanctum bearer tokens in localStorage.** The API (Render) and frontend (Vercel) are on different sites, and browsers block third-party cookies, so cookie-based sessions wouldn't work in production. The trade-off: a script injected into the page (XSS) could read the token. Mitigations: React escapes all output (no `dangerouslySetInnerHTML`), tokens expire after 7 days (`SANCTUM_EXPIRATION`), changing/resetting the password revokes other sessions. The fix for real production would be putting both on one domain (e.g. `api.example.com` + `example.com`) and switching to Sanctum's cookie mode.
- **Admin is separate and enforced by the API.** `/admin` has its own layout and sign-in, but the protection is server-side: every `/api/admin` request checks `is_admin` (403 otherwise). `is_admin` isn't mass-assignable; it's set with `php artisan app:make-admin <email>` (or `ADMIN_EMAIL` on deploy).
- **Brute-force limits**: login/register/password reset 5 per minute per email+IP, discount-code checks 20 per minute.
- **Open-redirect safe**: `?redirect=` after login only accepts paths on this site (`lib/redirect.ts`, unit-tested).
- **Emails** (order confirmation with keys, password reset) use Laravel's mail system. Locally and on the free deploy `MAIL_MAILER=log` writes them to the log; real sending only needs SMTP settings.
- **Trailers**: Steam serves HLS streams only. Safari plays them natively; other browsers use `hls.js`, which is loaded only when you press play, so it doesn't slow down page loads.

### Not included (and why)

- **Real payments**: the brief allows a simulation; a provider's test mode would need its account and keys. The payment step is clearly labelled as a demo.
- **Guest checkout**: the brief says checkout is only for signed-in users. Guests can still fill a cart, which is kept and merged in when they sign in.
- **Seller accounts** (sellers logging in to manage their own offers): sellers are demo data managed from the admin; a seller dashboard would be the next step for a real marketplace.
- **Cashback, gift cards/top-ups, language & currency switching**: each reshapes large parts of the data model and UI; out of scope for the assignment.

## Local setup

Requirements: PHP 8.2+, Composer 2, Node 20+, MySQL 8 with databases `eneba` and `eneba_test`.

```bash
# API → http://localhost:8000/api
cd backend
composer install
cp .env.example .env        # then fill in DB_PASSWORD
php artisan key:generate
php artisan migrate --seed  # tables + demo catalog (28 games, 43 listings, 6 sellers, 112 offers),
                            # discount codes and homepage blocks
php artisan serve

# Frontend → http://localhost:5173
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

- **Admin**: register on the site, run `php artisan app:make-admin you@example.com`, then open http://localhost:5173/admin.
- **Emails** (order confirmations, password-reset links) appear in `backend/storage/logs/laravel.log`.
- **Start over**: `php artisan migrate:fresh --seed`.

## Tests

```bash
cd backend && php artisan test   # 87 feature tests, against the eneba_test MySQL database
cd frontend && npm test          # 14 unit tests (Vitest)
cd frontend && npm run lint      # oxlint
```

Backend tests use MySQL, not SQLite, because SQLite ignores the row locks the checkout relies on.
They cover the catalog (filters, sorts, typo search, best-offer pricing), homepage blocks, cart,
checkout (stock, locking, rollback, choosing a seller), coupons, orders, reviews, wishlist,
accounts, password reset and the admin API.

## Deployment

- **API:** Render Blueprint in `render.yaml` (Docker, `backend/Dockerfile`). On start the
  container caches config, runs migrations, seeds the catalog, sellers, discount codes and homepage blocks (idempotent),
  and starts Apache. Health check: `GET /api/health`.
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
      | `ADMIN_EMAIL` | optional: your account's email (see step 6) |

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
   and redeploy, so CORS allows the browser to call the API (it's also used for links in
   emails). Several origins can be comma-separated; the first one is used for email links:
   `https://amoeba.vercel.app,http://localhost:5173`.

6. **Check** — open the Vercel URL and the `/status` page: *API status: ok*, *Database: ok*.
   To get an admin account: register on the live site, set `ADMIN_EMAIL` to that email in
   Render, and redeploy. The admin panel is at `https://<vercel-url>/admin`.

Optional: real emails — set `MAIL_MAILER=smtp` and `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` from an email provider. Until then emails are in Render's logs.

| Symptom | Likely cause |
|---|---|
| Site says "Could not reach the server" | `FRONTEND_URL` doesn't exactly match the Vercel URL (CORS), or the API is still waking up |
| `/api/health` returns `"database":"unreachable"` | wrong DB credentials, or the CA secret file is missing/misnamed (check Render logs) |
| Render deploy fails with `start.sh: not found` | script saved with CRLF line endings (`.gitattributes` should prevent this) |
| Vercel page 404s on refresh | Root Directory isn't `frontend`, so `vercel.json` wasn't picked up |

## AI usage

This project was built with an AI assistant (Claude). The conversation is exported to
`docs/` as the assignment requires.
