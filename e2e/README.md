# Browser tests

Real-browser checks for the public site, run with Playwright.

```bash
cd e2e
npm install
npx playwright install chromium          # first time only

# Serve the site (two hostnames, two behaviours — see js/data.js apiBase):
#   http://localhost:5500  -> talks to the backend at 127.0.0.1:8000
#   http://127.0.0.2:5500  -> no backend, like the Netlify site
python -m http.server 5500 --bind 0.0.0.0 --directory ../ennavizion

# For the backend tests, in another terminal:
cd ../backend && php artisan migrate && php artisan db:seed && php artisan serve

npm run smoke        # every page at 320/390/1440px: console errors, overflow, stray "undefined"/placeholder text
npm run e2e:static   # forms switched off honestly, empty states, no shop/WhatsApp without data
npm run e2e:backend  # trial application end to end (age checks, reference, double-click, error state, draft),
                     # contact form, donations closed, league from the API, mobile menu keyboard behaviour
npm run csp          # every page under the production Content-Security-Policy
```

`e2e:backend` writes test submissions to the local development database.
