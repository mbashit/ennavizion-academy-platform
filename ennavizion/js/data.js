/* =========================================================
   ENNAVIZION — site settings and fallback content

   On ennavizion.com this file is only a fallback: main.js replaces everything here with
   what staff have published in the admin panel (GET /api/content). Anywhere without the
   backend (a Netlify preview, a local file) the site renders from this file alone.

   RULE: only verified, academy-approved information goes in here. Anything left empty
   renders as an honest empty state ("No upcoming fixtures have been published.") or is
   hidden — never as a placeholder. What the academy still needs to supply is listed in
   docs/MISSING-BUSINESS-INFO.md.
   ========================================================= */

const SITE = {
  name: "Ennavizion",
  domain: "ennavizion.com",
  tagline: "Talent seen early. Talent built right.",
  // The academy's planned address on its own domain (see DEPLOY.md). Confirm the mailbox
  // exists before launch — every contact route on the site falls back to it.
  email: "info@ennavizion.com",
  // Leave blank until the academy confirms them. Blank values are hidden on the site (no
  // phone row, no WhatsApp button, shop orders go by email instead).
  phone: "",
  whatsapp: "",          // digits only, with country code, e.g. "2348012345678"
  address: "",
  hours: "",
  currency: "₦",
  socials: {
    // Full profile URLs only, e.g. "https://instagram.com/ennavizion". Blank = not shown.
    instagram: "",
    x: "",
    youtube: "",
    facebook: ""
  },
  // Set by the backend from its Paystack configuration — never set this to true by hand.
  donations: false,
  // Optional: a Formspree-style endpoint for static-only hosting. Forms are disabled (with an
  // email alternative) when neither this nor apiBase is set; they never pretend to send.
  formEndpoint: "",
  // The Laravel backend's API base (see /backend). Picked by where the site is opened: the
  // live domain uses the live backend, a local copy uses `php artisan serve`, anywhere else
  // (e.g. a Netlify preview) runs on this file's content alone with forms switched off.
  apiBase: ["ennavizion.com", "www.ennavizion.com"].includes(location.hostname) ? "https://api.ennavizion.com/api"
    : ["localhost", "127.0.0.1"].includes(location.hostname) ? "http://127.0.0.1:8000/api"
    : ""
};

// Programme structure by age group. Names and age bands describe how the academy groups
// players; fees stay null ("Fees on request") until the academy confirms pricing.
const PROGRAMS = [
  { id: "foundation", name: "Foundation", ages: "Ages 6–9", fee: null, period: "",
    text: "Ball mastery, coordination and enjoyment of the game for younger players." },
  { id: "development", name: "Development", ages: "Ages 10–13", fee: null, period: "",
    text: "Positional play, small-sided games and quicker decision-making." },
  { id: "elite", name: "Elite Pathway", ages: "Ages 14–18", fee: null, period: "",
    text: "Advanced technical, tactical and physical work for older players aiming at competitive football." },
  { id: "keepers", name: "Goalkeeper Unit", ages: "Ages 10–18", fee: null, period: "",
    text: "Specialist goalkeeper coaching: handling, distribution, positioning and decision-making." }
];

// Training locations: { name, pitch, days }. Empty until confirmed.
const CENTERS = [];

// League data. With the backend, the table arrives already calculated from published
// results using the season's rules. Without it, main.js calculates the table from
// `results` here using `rules`.
const LEAGUE = {
  season: "",
  rules: { win: 3, draw: 1, loss: 0 },
  table: [],     // [{ team, p, w, d, l, gf, ga, gd, pts, us }]
  teams: [],     // [{ name, us }] — teams registered for the season
  fixtures: [],  // [{ date: "2026-10-03", time: "10:00", home, away, venue }]
  results: []    // [{ date, home, hs, away, as }]
};

// Confirmed community figures only: { value: "120", label: "Players supported" }.
const IMPACT = [];

// Preset donation amounts (naira) for donate.html, used once online donations are open.
const DONATION_AMOUNTS = [5000, 10000, 25000, 50000];

// Only real quotes with written consent from the person (and a parent, for a minor).
const TESTIMONIALS = [];

// Academy photos only — { src, category: "training" | "matches" | "events", caption }.
// Photos of children need parental consent for publication.
const GALLERY = [];

// Shop products — { id, sku, name, price, tag, stock, colors?, image }. Empty = shop closed.
const PRODUCTS = [];

// News posts — { id, title, date, category, minutes, image, imageAlt, excerpt, body: [] }.
const POSTS = [];
