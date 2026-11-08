# Ennavizion — technical audit (8 Nov 2026)

Baseline: `master` at `391e0bb`. Work happens on the `production-readiness` branch.

## Stack

| Part | What it is | Where |
|---|---|---|
| Public site | Static HTML + one CSS file + vanilla JS (no build step, no npm deps) | `ennavizion/` |
| Content | `js/data.js` globals, replaced at runtime by `GET /api/content` when a backend is configured | `ennavizion/js/data.js`, `js/main.js` |
| Backend | Laravel 11 + Filament 3 admin, SQLite locally / MySQL on Namecheap | `backend/` |
| Payments | Paystack hosted checkout, server-side initialise + verify | `backend/app/Services/PaystackService.php` |
| Hosting now | Netlify, manual CLI deploys of `ennavizion/`, **no backend** (`apiBase` is empty on `*.netlify.app`) | — |
| Hosting planned | Namecheap shared hosting: site on `ennavizion.com`, API on `api.ennavizion.com` | `DEPLOY.md` |

## Confirmed problems

Severity: **P0** critical (safety, data, honesty), **P1** high, **P2** quality.

| # | Sev | Problem | Evidence |
|---|---|---|---|
| 1 | P0 | On Netlify (no backend) the enrolment, contact and newsletter forms save the data — including a child's name, date of birth and medical notes — into the visitor's own `localStorage` and then show **"Application sent"**. Nothing reaches the academy. | `main.js` `submitForm()` "Demo mode" branch; `data.js` `formEndpoint: ""` |
| 2 | P0 | Public placeholder content presented as fact: "Coach Name / Staff Name" cards with "UEFA B licence", a founding story ("one pitch, twelve players"), three named centres, fees, a sibling discount, a full league season with scores, "XX+" impact stats, a placeholder testimonial, stock photos captioned as academy events, demo products, news posts with operational claims. | `about.html`, `index.html`, `programs.html`, `community.html`, `league.html`, `shop.html`, `js/data.js`, `DatabaseSeeder.php` |
| 3 | P0 | Visible development notes: "Replace these with your team's names…", "This page is a placeholder", "PLACEHOLDER", "Development status: …", "Delivery and returns information will be confirmed before launch", "Figures above are placeholders…". | `about.html:61`, `privacy.html`, `terms.html`, `safeguarding.html`, `donate.html`, `shop.html`, `index.html:136`, `community.html:63` |
| 4 | P0 | Unverified commitments: "free trial", "we call within three working days", "we reply within one working day", "written feedback within a week", "every match filmed", "partner clubs at home and abroad", "safeguarding lead at every session". | `index.html`, `enroll.html`, `contact.html`, `league.html`, `programs.html`, `safeguarding.html` |
| 5 | P0 | Placeholder phone `+234 800 000 0000` and WhatsApp `2348000000000`: shop orders, the floating WhatsApp button and the contact page all send people to a number that isn't the academy's. Social links point at the generic Instagram/X/YouTube/Facebook home pages. | `data.js` `SITE`, seeder settings |
| 6 | P0 | Donate form looks fully working but can only fail (no Paystack key; on Netlify no backend at all). The visible "Development status" note admits it. | `donate.html`, `main.js` `initDonate()` |
| 7 | P0 | Paystack: no webhook (a donor who closes the tab after paying is never recorded), `verify` doesn't check the amount, and a later failed verify can overwrite a paid status. No rate limit on `/donations/*`. | `DonationController.php`, `routes/api.php` |
| 8 | P0 | Enrolment notification email contains the child's full record (DOB, medical notes) in plain text. Medical notes are stored in the same JSON as ordinary fields. No retention policy. | `SubmissionController::notify()`, `submissions` table |
| 9 | P1 | No spam protection beyond a 10/min rate limit; no server-side age check; program/centre accept any string; no application reference; duplicate double-submits create duplicate records. | `SubmissionController.php` |
| 10 | P1 | League standings are typed in by hand (`played/won/drawn/lost/goals` columns), not calculated from results; fixtures and results are two duplicate tables; no seasons, no competition rules, no publish/approval step for results. | `league_*` migrations, `LeagueTeam`, `ContentController::league()` |
| 11 | P1 | Header and all page content wait for `/api/content` (up to 4 s) before rendering on the live domain — slow LCP and a layout jump. | `main.js` boot sequence |
| 12 | P1 | Mobile menu: closed menu is moved off-screen with `transform`, so its links stay in the keyboard tab order; menu doesn't close on link click or when resized to desktop. | `main.js` `initChrome()`, `style.css` |
| 13 | P1 | Navigation: 8 links + cart + 2 CTAs; Shop missing from nav but cart icon always shown; News/Gallery in primary nav. | `main.js` `NAV` |
| 14 | P2 | `admin.html` is a public page that reads demo submissions from localStorage. | `admin.html` |
| 15 | P2 | Logo PNG is 212 KB for a 48 px badge; Big Shoulders 900 weight loaded but unused; images lack `width`/`height`. | `assets/images/logo.png`, `<head>` font links |
| 16 | P2 | No `robots.txt`, `sitemap.xml`, `og:image`, structured data, or security headers on Netlify. | site root |
| 17 | P2 | Only placeholder backend tests (`ExampleTest`). | `backend/tests` |
