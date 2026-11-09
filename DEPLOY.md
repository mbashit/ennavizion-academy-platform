# Deploying Ennavizion to Namecheap

Everything runs on one Namecheap **shared hosting** plan (any Stellar plan) with cPanel:

| Address | What it is | Folder on the server |
|---|---|---|
| `https://ennavizion.com` | The public website | `~/public_html` |
| `https://api.ennavizion.com` | Laravel backend (API) | `~/ennavizion-backend/public` |
| `https://api.ennavizion.com/admin` | Staff admin panel | (same) |
| `https://api.ennavizion.com/portal` | Parent portal (sign up, players, trial applications) | (same) |

The backend lives **outside** `public_html`, so its code, `.env` and database password are
never reachable from the web; only its `public` folder is served.

## 1. Build the upload packages (on your PC)

```powershell
powershell -ExecutionPolicy Bypass -File deploy\build-namecheap.ps1
```

This creates `dist\ennavizion-site.zip` and `dist\ennavizion-backend.zip`. The backend zip
already contains its `vendor` folder, so the server never has to run Composer.

## 2. One-time cPanel setup

Log in to Namecheap → **Hosting List** → **Go to cPanel**.

1. **PHP version** — *Select PHP Version*: choose **8.2 or newer**. Under *Extensions*
   make sure these are ticked: `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `zip`, `gd`,
   `curl`, `openssl`.
2. **Database** — *MySQL Database Wizard*: create a database (e.g. `ennavizion`) and a user
   with a strong password, and give the user **All Privileges**. Write down the full names
   cPanel shows — they get your cPanel username as a prefix (e.g. `abcduser_ennavizion`).
3. **API subdomain** — *Domains* → **Create A New Domain**: enter `api.ennavizion.com`,
   **untick** "Share document root", and set the document root to
   `ennavizion-backend/public`.
4. **Email** — *Email Accounts*: create `info@ennavizion.com` (receives form alerts) and
   `noreply@ennavizion.com` (sends them). Keep the `noreply` password for step 4.
5. **HTTPS** — *SSL/TLS Status*: tick `ennavizion.com`, `www.ennavizion.com` and
   `api.ennavizion.com`, then **Run AutoSSL**. Wait until all show a valid certificate.

## 3. Upload the backend

1. *File Manager* → your home folder (one level **above** `public_html`) → create the
   folder `ennavizion-backend`.
2. Open it, **Upload** `ennavizion-backend.zip`, then right-click it → **Extract**. Delete
   the zip afterwards.
3. Click *Settings* (top right) → tick **Show Hidden Files**. Copy `.env.production.example`
   to `.env`, then **Edit** `.env` and fill in every `CHANGE_ME`: the database name, user and
   password from step 2.2, and the `noreply` email password from step 2.4.

## 4. Finish the backend setup

Open cPanel → **Terminal** (or connect over SSH) and run:

```bash
cd ~/ennavizion-backend
php -v                              # must say 8.2 or newer
php artisan key:generate --force
php artisan migrate --seed --force  # creates the tables and loads the starter content
php artisan storage:link            # makes uploaded photos visible on the website
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan make:filament-user      # the client's admin login (name, email, password)
php artisan app:make-developer      # your developer login — hidden from the client
```

The client can add more staff logins later under **Admin users** in the panel; they never
see or can change the developer account. Forgotten password: use "Forgot password?" on the
login page (emails a reset link), or in Terminal run
`php artisan app:admin-password their@email.com`.

`--seed` only adds the site name, email and the four programmes by age group (with no
fees). Everything else — fees, training locations, league seasons, staff, photos, products —
is added by the academy in the admin panel once it's real. Run it **only on the first deploy**.

**Back up `APP_KEY`.** Copy the `APP_KEY=` line from `.env` somewhere safe (a password
manager). Medical notes on applications are encrypted with it; if the key is lost or
regenerated, those notes can't be read again.

**Scheduled tasks.** cPanel → *Cron Jobs* → add, every minute:

```
cd ~/ennavizion-backend && php artisan schedule:run >> /dev/null 2>&1
```

This runs `app:prune-submissions` daily, which deletes old submissions only after the
academy has set `SUBMISSION_RETENTION_DAYS` in `.env` (blank = keep everything).

## 5. Upload the website

**Domain.** The site's sitemap, robots.txt, social preview image and structured data point
at the current production address, `https://ennavizion.netlify.app`. When `ennavizion.com`
becomes the main address, replace it in these four files before building:
`ennavizion/sitemap.xml`, `ennavizion/robots.txt`, and the `og:image` / JSON-LD lines in the
`<head>` of every `ennavizion/*.html` page (one find-and-replace across the folder).


*File Manager* → `public_html` → delete Namecheap's default files (e.g. `default.html`) →
**Upload** `ennavizion-site.zip` → **Extract** → delete the zip. Check that the hidden
`.htaccess` file is there (it forces HTTPS and serves the 404 page).

## 6. Check it works

- `https://api.ennavizion.com/api/content` shows a page of data (JSON).
- `https://api.ennavizion.com/admin` lets you log in with the user from step 4.
- Parent portal: open `https://api.ennavizion.com/portal/register`, sign up with a real
  mailbox, click the verification email, add a player and apply for a trial. The application
  appears in the admin panel under **Parent portal → Trial applications**; set it to
  "Trial scheduled" with a date and place and check the parent receives the update email.
  Mail must work for parents to verify their accounts, so test `MAIL_*` first.
  `QUEUE_CONNECTION=sync` (already in `.env.production.example`) sends these emails
  immediately; don't change it to `database` unless a queue worker is running.
- `https://ennavizion.com` loads. Edit a program fee in the admin, reload the site — the
  new fee shows.
- Send the contact form: it appears under **Submissions** in the admin, and an email
  arrives at `info@ennavizion.com`.
- Contribution checkout stays closed (the programme information is still shown) until
  Paystack is switched on. To test: set `PAYSTACK_MODE=test` and Paystack **test** keys
  (`sk_test_…`) in `.env`, run `php artisan config:cache`, and in the Paystack
  dashboard (Settings → API Keys & Webhooks) set the webhook URL to
  `https://api.ennavizion.com/api/donations/webhook`. Make a test donation with a Paystack
  test card; it shows as **Paid** under Finance → Contributions, marked *Test*, and is not counted
  in the totals. Switch to `PAYSTACK_MODE=live` with **live** keys only when the academy
  has authorised real payments and verified its settlement account.
- Apply for a trial with test details: you get a reference like `ENR-2026-7KQ4XM`, the
  application appears under **Submissions**, and the notification email contains only the
  reference.

If anything shows "Server Error", the details are in
`~/ennavizion-backend/storage/logs/` (newest file).

## Updating later

1. Rebuild the packages: step 1.
2. **Website changes:** upload `ennavizion-site.zip` to `public_html` and extract,
   overwriting files.
3. **Backend changes:** upload `ennavizion-backend.zip` to `~/ennavizion-backend` and extract,
   overwriting files. Your `.env` and uploaded photos aren't in the zip, so they're kept.
   Then in Terminal:

   ```bash
   cd ~/ennavizion-backend
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

Any change to `.env` needs `php artisan config:cache` afterwards to take effect.
