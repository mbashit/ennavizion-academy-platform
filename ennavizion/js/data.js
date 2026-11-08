/* =========================================================
   ENNAVIZION — site settings and fallback content

   On ennavizion.com this file is only a fallback: main.js replaces everything here with
   what staff have published in the admin panel (GET /api/content). Anywhere without the
   backend (a Netlify preview, a local file) the site renders from this file alone.

   PLACEHOLDERS: text in [square brackets] is shown on the live site on purpose, so each
   section is visible while the real details are gathered. Replace every [bracketed] value
   with the confirmed detail (search this file for "["). Fees, sessions and the free first
   trial come from the 2027 business plan. docs/MISSING-BUSINESS-INFO.md lists everything.
   ========================================================= */

const SITE = {
  name: "Ennavizion",
  domain: "ennavizion.com",
  tagline: "Talent seen early. Talent built right.",
  // The academy's planned address on its own domain (see DEPLOY.md). Confirm the mailbox
  // exists before launch — every contact route on the site falls back to it.
  email: "info@ennavizion.com",
  // Shown as written. [Bracketed] values are placeholders: shown as text, never as a
  // clickable phone link. WhatsApp stays blank until the real number is known, because a
  // wrong number would send families' messages to a stranger.
  phone: "+7 906 590-93-21",
  whatsapp: "",          // digits only, with country code, e.g. "2348012345678"
  address: "Training ground, Jos, Nigeria",
  hours: "[Office hours, e.g. Mon–Fri 9:00–17:00]",
  // Business plan 2027, section 4.
  registrationFee: 10000,  // one-time, newly admitted players
  goalkeeperAddOn: 15000,  // per month, for players enrolled in another programme
  freeTrial: true,         // one complimentary introductory trial per new player
  currency: "₦",
  socials: {
    // Full profile URLs only, e.g. "https://instagram.com/ennavizion". Blank = not shown.
    instagram: "",
    x: "",
    youtube: "",
    facebook: ""
  },
  // Set by the backend from its Paystack configuration — never set these by hand.
  // donationMode: "off" (information only), "test" (labelled test checkout) or "live".
  donations: false,
  donationMode: "off",
  // Optional: a Formspree-style endpoint for static-only hosting.
  formEndpoint: "",
  // On Netlify, forms are delivered by Netlify Forms (see netlify-forms.html): submissions
  // appear in the Netlify dashboard → Forms, with email notifications.
  netlifyForms: /\.netlify\.app$/.test(location.hostname),
  // Paystack "Payment Page" link for contributions while there is no backend, e.g.
  // "https://paystack.com/pay/ennavizion". Blank = the page shows "not open yet".
  paystackPageUrl: "",
  // The Laravel backend's API base (see /backend). Picked by where the site is opened: the
  // live domain uses the live backend, a local copy uses `php artisan serve`, anywhere else
  // (e.g. a Netlify preview) runs on this file's content alone with forms switched off.
  apiBase: ["ennavizion.com", "www.ennavizion.com"].includes(location.hostname) ? "https://api.ennavizion.com/api"
    : ["localhost", "127.0.0.1"].includes(location.hostname) ? "http://127.0.0.1:8000/api"
    : ""
};

// Programmes, fees and sessions from the 2027 business plan (section 4).
const PROGRAMS = [
  { id: "foundation", name: "Foundation", ages: "Ages 6–9", fee: 25000, period: "per month",
    text: "Ball mastery, coordination and enjoyment of the game for younger players. 2 sessions a week." },
  { id: "development", name: "Development", ages: "Ages 10–13", fee: 35000, period: "per month",
    text: "Technical development, small-sided games and quicker decision-making. 3 sessions a week." },
  { id: "elite", name: "Elite Pathway", ages: "Ages 14–18", fee: 50000, period: "per month",
    text: "Advanced coaching and competitive exposure for older players. 4 sessions a week." },
  { id: "keepers", name: "Goalkeeper Unit", ages: "Ages 10–18", fee: 30000, period: "per month",
    text: "Specialist goalkeeper coaching: handling, distribution, positioning and decision-making. 2 specialist sessions a week." }
];

// Training locations: { name, pitch, days }. The plan starts with one centre.
const CENTERS = [
  { name: "Jos training ground", pitch: "[Pitch type, e.g. full-size grass pitch]", days: "[Training days]" }
];

// Team profiles shown on the About page. Only REAL staff go here, and a profile is shown on
// the live site only when both isVerified and isPublished are true (identity, role and any
// qualification checked; the person agreed to be listed). Fields:
//   { name, role, description, image: "assets/images/team/<file>.webp", altText,
//     isVerified: true, isPublished: true }
// Until a real profile qualifies, the labelled sample profiles in js/demo-team.js are shown.
const STAFF = [];

// Roles the academy is appointing (business plan 2027, section 5) — listed while profiles
// are being finalised.
const TEAM_ROLES = [
  "Technical Director / Head Coach", "Foundation Coach", "Development and Elite Coach",
  "Goalkeeper Coach", "Academy Operations Manager", "Safeguarding Lead", "League Coordinator"
];

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

// "Support a Future Footballer" contribution programme (business plan 2027, section 7).
// Mirrors backend/config/contributions.php, which replaces it when the backend is live.
// Purposes are planned allocations, not a promise of a specific item or beneficiary.
const CONTRIBUTIONS = {
  programme: "Support a Future Footballer",
  options: [
    { amount: 5000, purpose: "Toward shared training equipment", allocation: "equipment" },
    { amount: 10000, purpose: "Toward a player's training kit", allocation: "kit" },
    { amount: 25000, purpose: "One month of Foundation training", allocation: "training_fees" },
    { amount: 35000, purpose: "One month of Development training", allocation: "training_fees" },
    { amount: 50000, purpose: "One month of Elite Pathway training", allocation: "training_fees" },
    { amount: 150000, purpose: "Toward a longer-term player sponsorship", allocation: "sponsorship" }
  ],
  allocations: {
    greatest_need: "Where it is needed most",
    equipment: "Training equipment",
    kit: "Player kit",
    training_fees: "Training fees for supported players",
    sponsorship: "Longer-term player sponsorship",
    community: "Community football clinics"
  },
  min: 500
};

// Targets for the first operating year. Always shown as goals, never as results achieved.
const GOALS = {
  year: 2027,
  items: [
    { value: "250", label: "Young people reached through the academy and outreach" },
    { value: "30", label: "Players receiving full or partial financial support" },
    { value: "4", label: "Community football clinics" },
    { value: "₦3,000,000", label: "Raised in contributions and sponsorship" }
  ]
};

// Only real quotes with written consent from the person (and a parent, for a minor).
const TESTIMONIALS = [
  { quote: "[Parent or player testimonial, used with their written consent]", name: "[Name]", role: "[Parent of a Foundation player]" }
];

// Gallery — { src, category: "training" | "matches" | "events", caption }. These are
// illustrative stock photos (Unsplash) until the academy's own are added; the gallery page
// says so. Photos of the academy's players need parental consent for publication.
const GALLERY = [
  { src: "https://images.unsplash.com/photo-1786088987909-8fca31316c64?auto=format&fit=crop&w=800&h=800&q=75", category: "training", caption: "Rondo drill" },
  { src: "https://images.unsplash.com/photo-1598880513655-d1c6d4b2dfbf?auto=format&fit=crop&w=800&h=800&q=75", category: "matches", caption: "Match day" },
  { src: "https://images.unsplash.com/photo-1518905332052-b6cfda20ee45?auto=format&fit=crop&w=800&h=800&q=75", category: "training", caption: "Goalkeeper handling" },
  { src: "https://images.unsplash.com/photo-1680024436315-fb06267264b2?auto=format&fit=crop&w=800&h=800&q=75", category: "events", caption: "Trial day" },
  { src: "https://images.unsplash.com/photo-1787535849781-9d35cfde0445?auto=format&fit=crop&w=800&h=800&q=75", category: "matches", caption: "Celebrating a goal" },
  { src: "https://images.unsplash.com/photo-1771577125789-93dc33e63f31?auto=format&fit=crop&w=800&h=800&q=75", category: "events", caption: "Team talk" },
  { src: "https://images.unsplash.com/photo-1502904550040-7534597429ae?auto=format&fit=crop&w=800&h=800&q=75", category: "training", caption: "Strength and conditioning" },
  { src: "https://images.unsplash.com/photo-1715277331664-a546eb85a8e8?auto=format&fit=crop&w=800&h=800&q=75", category: "matches", caption: "Penalty practice" },
  { src: "https://images.unsplash.com/photo-1664325426906-ba7596dd1258?auto=format&fit=crop&w=800&h=800&q=75", category: "events", caption: "Watching from the touchline" }
];

// Shop products — { id, sku, name, price, tag, stock, colors?, image }. Placeholder items are
// listed but can't be ordered ("stock: false") until real names and prices are filled in.
const PRODUCTS = [
  { id: "item-1", sku: "[SKU]", name: "[Home kit]", price: 0, tag: "Kit", stock: false, image: "https://images.unsplash.com/photo-1602674809970-89073c530b0a?auto=format&fit=crop&w=700&h=700&q=75" },
  { id: "item-2", sku: "[SKU]", name: "[Training top]", price: 0, tag: "Training", stock: false, image: "https://images.unsplash.com/photo-1774414489671-9f33b8dff0a9?auto=format&fit=crop&w=700&h=700&q=75" },
  { id: "item-3", sku: "[SKU]", name: "[Training shorts]", price: 0, tag: "Training", stock: false, image: "https://images.unsplash.com/photo-1789110519888-758d7ab3908e?auto=format&fit=crop&w=700&h=700&q=75" },
  { id: "item-4", sku: "[SKU]", name: "[Academy cap]", price: 0, tag: "Merch", stock: false, image: "https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=700&h=700&q=75" }
];

// News posts — { id, title, date, category, minutes, image, imageAlt, excerpt, body: [] }.
const POSTS = [];
