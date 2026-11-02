# Ennavizion — Youth Football Academy Platform

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white) ![Filament](https://img.shields.io/badge/Filament-3-FDAE4B) ![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white) ![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black) ![Paystack](https://img.shields.io/badge/payments-Paystack-0BA4DB)

A full-stack website and content-management platform for a youth football academy: a fast static front end backed by a **Laravel REST API** and a **Filament admin panel** that lets non-technical staff manage everything on the site.

## Features

**Public website** (`ennavizion/`)
- 17 pages: home, about, programs & fees, league, blog & posts, gallery, community, shop, donate, enroll, contact and legal pages
- Plain HTML/CSS/JS — no build step, fast on low-bandwidth connections
- Content (programs, league tables, posts, testimonials, stats) loaded live from the API

**Backend & admin** (`backend/`)
- **Filament admin panel** with CRUD for 12 resources: programs, centres, posts, gallery, products, testimonials, impact stats, league teams / fixtures / results, form submissions and admin users
- **REST API** — `GET /api/content` for site content, `POST /api/submissions` for enrolment & contact forms, donation endpoints
- **Online donations with Paystack** — payment initialisation and server-side verification
- Email notifications for new submissions
- Role separation: a protected developer account plus staff logins managed in the panel
- Password-reset flow and an artisan command for admin recovery

## Tech stack

| Layer | Tools |
|---|---|
| Backend | PHP 8.2, Laravel 12, Filament 3, Eloquent ORM |
| Database | SQLite locally, MySQL in production |
| Front end | HTML5, CSS3, vanilla JavaScript |
| Payments | Paystack API |
| Deployment | cPanel shared hosting (Namecheap), PowerShell packaging script |

## Getting started

```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve          # API + admin at http://localhost:8000/admin
```

Open `ennavizion/index.html` with a local server (e.g. VS Code Live Server) for the public site.

## Deployment

Step-by-step production deployment to cPanel hosting — building upload packages, database setup, environment configuration and post-deploy checks — is documented in [`DEPLOY.md`](DEPLOY.md).

## Project structure

```
backend/     # Laravel API + Filament admin (models, resources, migrations, services)
ennavizion/  # static public website
deploy/      # build-namecheap.ps1 packaging script
DEPLOY.md    # deployment guide
```
