# Deploying Ennavizion to Namecheap

Everything runs on one Namecheap **shared hosting** plan (any Stellar plan) with cPanel:

| Address | What it is | Folder on the server |
|---|---|---|
| `https://ennavizion.com` | The public website | `~/public_html` |
| `https://api.ennavizion.com` | Laravel backend (API) | `~/ennavizion-backend/public` |
| `https://api.ennavizion.com/admin` | Admin panel | (same) |

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
php artisan make:filament-user      # create the admin login (name, email, password)
```

`--seed` loads the same placeholder content the site shows today; run it **only on the
first deploy**. After that, change content in the admin panel.

## 5. Upload the website

*File Manager* → `public_html` → delete Namecheap's default files (e.g. `default.html`) →
**Upload** `ennavizion-site.zip` → **Extract** → delete the zip. Check that the hidden
`.htaccess` file is there (it forces HTTPS and serves the 404 page).

## 6. Check it works

- `https://api.ennavizion.com/api/content` shows a page of data (JSON).
- `https://api.ennavizion.com/admin` lets you log in with the user from step 4.
- `https://ennavizion.com` loads. Edit a program fee in the admin, reload the site — the
  new fee shows.
- Send the contact form: it appears under **Submissions** in the admin, and an email
  arrives at `info@ennavizion.com`.
- Donations: add Paystack **test** keys to `.env`, run `php artisan config:cache`, and make a
  test donation. Switch to **live** keys only when the client is ready to take payments.

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
