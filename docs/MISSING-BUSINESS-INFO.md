# Information the academy needs to provide

The website no longer shows anything that hasn't been confirmed. Wherever information is
missing, visitors see an honest message ("No upcoming fixtures have been published.") or
the section is hidden. This list is what Ennavizion needs to supply, and where it goes once
it's real.

"Admin" means the admin panel at `api.ennavizion.com/admin` once the backend is live.
Until then (Netlify), the same values go in `ennavizion/js/data.js`.

## Needed before launch

| What | Why it matters | Where it goes |
|---|---|---|
| **Official phone number** | No phone is shown anywhere until confirmed. | Admin → Settings → Phone |
| **WhatsApp business number** | The WhatsApp button and shop orders by WhatsApp only appear once this is set. Shop orders currently fall back to email. | Admin → Settings → WhatsApp (digits only, with 234) |
| **Confirm `info@ennavizion.com` works** | It's the only contact route on the site, and where applications are notified. The mailbox is created in the hosting setup (DEPLOY.md step 2.4). | DEPLOY.md |
| **Address and office hours** | The contact page shows them (and a map) only when set. | Admin → Settings |
| **Social media profile links** | Footer icons only appear for full profile URLs. | Admin → Settings |
| **Programme fees** (per programme, and the billing period) | Programmes show "Fees on request" with a "Request fee information" link until a fee is entered. | Admin → Programs → Fee / Period |
| **Confirm the four programmes and age bands** | Foundation 6–9, Development 10–13, Elite Pathway 14–18, Goalkeeper Unit 10–18 came from the original site. Applications are age-checked against these bands. | Admin → Programs |
| **Training locations** (name, pitch, training days) | The application form only asks for a location once at least one is published. | Admin → Centers |
| **Safeguarding policy** and the **name and contact of the safeguarding lead** | The safeguarding page says the policy is being finalised. It must not be presented as approved until it is. | `ennavizion/safeguarding.html` |
| **Privacy policy** (reviewed) and a **retention period** for applications and messages | The site shows an interim privacy notice that describes what the code does. How long data is kept is a legal/business decision. | `ennavizion/privacy.html`; `SUBMISSION_RETENTION_DAYS` in the backend `.env` |
| **Enrolment terms**: fees, cancellations, refunds, photo/video consent | The terms page currently says these are being finalised. | `ennavizion/terms.html` |

## Needed to switch features on

| What | Unlocks | Where it goes |
|---|---|---|
| **Paystack account and API keys** (test first, then live) and authorisation to take live payments | Online donations. The form is hidden until a key is set. | Backend `.env` → `PAYSTACK_SECRET_KEY`; webhook URL in the Paystack dashboard (DEPLOY.md) |
| **League season**: name, age group, teams, fixtures (date, time, venue), results | The league table, fixtures and results. The table is calculated from published results. | Admin → League → Seasons and table / Teams / Fixtures and results |
| **Shop products**: name, price, sizes, photo, availability | The Shop link in the navigation and the shop page. | Admin → Products |
| **Coaching staff**: name, role, verified licences/qualifications, photo (with consent) | A staff section on the About page. Removed until real. Needs a small page addition when ready. | Ask the developer |

## Content that would make the site stronger

| What | Notes |
|---|---|
| **The academy's own photographs** | Hero, About, Community and Programmes photos are illustrative stock images (marked in the HTML comments) and don't claim to show Ennavizion. Replace them with real training photos. Photos of children need written parental consent. The Gallery stays empty until real photos are added (Admin → Gallery). |
| **Confirmed community figures** (players supported, scholarships, kit) | Shown only when entered and ticked "Confirmed" (Admin → Impact stats). |
| **Testimonials** | Shown only when ticked both "Verified" and "Published", with written consent (and a parent's consent for a minor). |
| **News posts** | The news page shows "News from the academy will appear here." until posts are published (Admin → Posts). |
| **Partner clubs or scouting arrangements** | All "partner clubs at home and abroad" claims were removed. Add them back only for real, documented partnerships. |
| **Whether the trial is free** | The site says "trial session", not "free trial", until confirmed. |
| **Expected reply/response times** | Removed ("within one working day", "within three working days") until the academy commits to them. |
| **Camps, private coaching and school clinics** | The Programmes page invites people to ask about these rather than claiming they run. List them once confirmed. |
