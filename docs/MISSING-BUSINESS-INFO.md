# Information the academy needs to provide

Every feature is live. Where a real detail isn't known yet, the site shows a visible
**[bracketed placeholder]** for you to replace. Placeholders are shown as plain text only:
never as a phone link, map pin, WhatsApp button, form choice or orderable product.

## Placeholders to replace

All in `ennavizion/js/data.js` unless noted (search for `[`). Once the Laravel backend is
live, the admin panel values replace these.

| Placeholder | Where it shows | Replace with |
|---|---|---|
| `[Office hours, …]` | Contact | `SITE.hours` |
| WhatsApp (blank, not a placeholder) | WhatsApp button, shop orders | `SITE.whatsapp` — digits with 234. Left blank so families never message a wrong number. |
| `[Pitch type…]`, `[Training days]` (centre: Jos training ground) | Programmes → Training locations | `CENTERS` |
| Team profiles | About → Meet our team. Live site: "announced soon" with the roles until a real profile is added. Previews (local, Netlify draft/preview deploys) show the 7 fictional demo profiles from `js/demo-team.js`, labelled | `STAFF` in `js/data.js`: `{ name, role, description, image, altText, isVerified: true, isPublished: true }`. Put photos (4:5, WebP) in `assets/images/team/`. Real people only, with consent and verified details |
| Testimonial `[…]` | Home page | `TESTIMONIALS` — real quote with written consent (and a parent's for a minor) |
| `[Home kit]`, `[Training top]`, `[Training shorts]`, `[Academy cap]`, `[SKU]`, price | Shop ("Coming soon", can't be ordered) | `PRODUCTS` — set name, `sku`, `price`, `image` and `stock: true` to make an item orderable |
| `[Safeguarding lead name]`, `[safeguarding phone number]` | Safeguarding page | `ennavizion/safeguarding.html` |
| `[complaints phone number]` | Terms | `ennavizion/terms.html` |
| `[retention period, …]` | Privacy notice | `ennavizion/privacy.html` (and `SUBMISSION_RETENTION_DAYS` on the backend) |
| Paystack Payment Page link (blank) | Support a player → "Give with Paystack" button | `SITE.paystackPageUrl`, e.g. `https://paystack.com/pay/ennavizion` (create it in the Paystack dashboard → Payment Pages) |

## Now live from the 2027 business plan

Fees (₦25,000 / ₦35,000 / ₦50,000 / ₦30,000 per month), sessions per week, the ₦10,000
registration fee, the ₦15,000 goalkeeper add-on, the free first trial, mission/vision/values,
staff roles, the contribution programme and 2027 goals, and the draft safeguarding,
enrolment/refund, media and contribution policies — labelled **Draft, pending adoption**.
The plan's fictional coach names are not used anywhere.

## Forms on Netlify

Applications, contact messages and newsletter sign-ups are delivered by **Netlify Forms**:
Netlify dashboard → the ennavizion project → **Forms**. Set up email notifications there
(Forms → Form notifications). Each application and message carries a reference number. The
medical-notes field is not offered on Netlify (it only exists where it can be stored
encrypted, on the Laravel backend); families are asked to share medical needs directly.

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
| **Paystack account and API keys** (test first, then live), verified settlement account, and authorisation to take live payments | Contribution checkout. Information is shown without it; checkout opens only in test or live mode. | Backend `.env` → `PAYSTACK_MODE`, `PAYSTACK_SECRET_KEY`; webhook URL in the Paystack dashboard (DEPLOY.md) |
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
