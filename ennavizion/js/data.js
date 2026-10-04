/* =========================================================
   ENNAVIZION — site settings and content
   Edit this file to change contact details, prices, posts,
   league tables and gallery items. No other code changes needed.

   BEFORE LAUNCH — confirm or replace the following. Everything below is
   demo/placeholder content used to show the site's structure, not verified fact:
     - CENTERS: names, cities (Lagos/Abuja/Port Harcourt) and pitch types are examples.
       This is the single source for "how many centres" and "which cities" — it already
       feeds the homepage hero strip, so update it here rather than in the HTML.
     - PROGRAMS: fees and program text are placeholders, not agreed pricing.
     - LEAGUE: season name, table and fixtures are sample data, not a real season.
     - TESTIMONIALS: placeholder quote, not a real or consented testimonial. Do not
       publish until you have written permission from the parent/player quoted.
     - IMPACT: community/donation stats are intentionally "XX+"-style placeholders.
     - Claims elsewhere in the HTML such as "recognised licences", "partner clubs
       abroad" and "international trials" (about.html, index.html) describe the intended
       program and are marked with HTML comments at their location — confirm before launch.
     - Operational claims on enroll.html ("we call within three working days", the
       submit → review → contact → trial flow) and the consent wording there describe
       the intended process, not a confirmed SLA or reviewed legal text — confirm both
       with the client (and a legal advisor for consent wording) before launch.
     - PRODUCTS: names, prices, SKUs and stock status are demo placeholders — see the
       comment directly above the PRODUCTS array below.
   ========================================================= */

const SITE = {
  name: "Ennavizion",
  domain: "ennavizion.com",
  tagline: "Talent seen early. Talent built right.",
  email: "info@ennavizion.com",
  phone: "+234 800 000 0000",
  whatsapp: "2348000000000",          // digits only, with country code — used for shop orders
  address: "Ennavizion Training Ground, Lagos, Nigeria",
  hours: "Mon–Sat, 7:00 – 18:00",
  currency: "₦",
  socials: {
    instagram: "https://instagram.com/",
    x: "https://x.com/",
    youtube: "https://youtube.com/",
    facebook: "https://facebook.com/"
  },
  // Paste a Formspree (or similar) endpoint here to receive form submissions by email,
  // e.g. "https://formspree.io/f/abcdwxyz". Leave empty to store submissions in the browser (demo mode).
  formEndpoint: "",
  // The Laravel backend's API base (see /backend). When set, main.js loads all content from
  // it (the admin panel's data replaces everything below), sends the contact/enrolment/
  // newsletter forms to it, and runs donations through it. If the API can't be reached, the
  // site falls back to the content in this file. Picked by where the site is opened: the
  // live domain uses the live backend, a local copy uses `php artisan serve`, and anywhere
  // else (e.g. a Netlify preview) runs on this file's content alone.
  apiBase: ["ennavizion.com", "www.ennavizion.com"].includes(location.hostname) ? "https://api.ennavizion.com/api"
    : ["localhost", "127.0.0.1"].includes(location.hostname) ? "http://127.0.0.1:8000/api"
    : ""
};

const PROGRAMS = [
  { id: "foundation", name: "Foundation", ages: "Ages 6–9", fee: 25000, period: "per month",
    text: "Ball mastery, coordination and a love for the game. Two sessions a week plus a Saturday match." },
  { id: "development", name: "Development", ages: "Ages 10–13", fee: 35000, period: "per month",
    text: "Positional play, small-sided tactics and weekly video feedback. Three sessions a week." },
  { id: "elite", name: "Elite Pathway", ages: "Ages 14–18", fee: 50000, period: "per month",
    text: "Full-time style training, strength work, league fixtures and a scouting profile shared with partner clubs." },
  { id: "keepers", name: "Goalkeeper Unit", ages: "Ages 10–18", fee: 30000, period: "per month",
    text: "Specialist keeper coaching: handling, distribution, positioning and decision-making under pressure." }
];

const CENTERS = [
  { name: "Lagos — Surulere", pitch: "Full-size grass pitch", days: "Mon, Wed, Fri, Sat" },
  { name: "Abuja — Wuse", pitch: "Artificial turf, floodlit", days: "Tue, Thu, Sat" },
  { name: "Port Harcourt — GRA", pitch: "Grass pitch + gym", days: "Mon, Wed, Sat" }
];

const LEAGUE = {
  season: "2026/27 Season",
  table: [
    // team, played, won, drawn, lost, goals for, goals against
    ["Ennavizion U17",   8, 6, 1, 1, 19, 7],
    ["Lagoon Stars",     8, 5, 2, 1, 15, 8],
    ["Northgate FC",     8, 4, 3, 1, 13, 9],
    ["Harbour United",   8, 4, 1, 3, 12, 11],
    ["Savannah Rovers",  8, 3, 2, 3, 10, 10],
    ["Eko Juniors",      8, 2, 2, 4, 9, 13],
    ["Delta Rangers",    8, 1, 3, 4, 7, 14],
    ["Plateau Youth",    8, 0, 2, 6, 5, 18]
  ],
  fixtures: [
    { date: "2026-10-03", time: "10:00", home: "Ennavizion U17", away: "Northgate FC", venue: "Surulere" },
    { date: "2026-10-03", time: "12:30", home: "Lagoon Stars", away: "Eko Juniors", venue: "Wuse" },
    { date: "2026-10-10", time: "10:00", home: "Harbour United", away: "Ennavizion U17", venue: "GRA" },
    { date: "2026-10-10", time: "12:30", home: "Delta Rangers", away: "Savannah Rovers", venue: "Surulere" }
  ],
  results: [
    { date: "2026-09-26", home: "Ennavizion U17", hs: 3, away: "Delta Rangers", as: 0 },
    { date: "2026-09-26", home: "Plateau Youth", hs: 1, away: "Lagoon Stars", as: 2 },
    { date: "2026-09-19", home: "Savannah Rovers", hs: 1, away: "Ennavizion U17", as: 1 },
    { date: "2026-09-19", home: "Northgate FC", hs: 2, away: "Harbour United", as: 2 }
  ]
};

// Community / Social Impact placeholder stats — homepage and community.html.
// Replace every value with a confirmed figure before launch; do not publish "XX+" copy.
const IMPACT = [
  { value: "XX+", label: "Players supported" },
  { value: "₦X.XM", label: "Scholarship support" },
  { value: "XXX+", label: "Kits distributed" }
];

// Preset donation amounts (in naira) shown as quick-select buttons on donate.html.
// A "Custom amount" option is always added in addition to this list — see initDonate() in main.js.
const DONATION_AMOUNTS = [5000, 10000, 25000, 50000];

// Placeholder testimonial(s) for the homepage. NOT a verified quote — replace with a real,
// consented parent/player testimonial (with their permission to publish) before launch.
const TESTIMONIALS = [
  // Deliberately generic placeholder copy — do not write a specific success claim (an
  // overseas trial, a scholarship, a named club) here until a real player/parent has
  // supplied and approved a quote. The photo is an unrelated Unsplash stand-in for layout
  // purposes only, not a picture of any real Ennavizion player or parent — replace with
  // real Ennavizion imagery (with consent) at the same time as the quote.
  { quote: "Player success story will appear here once supplied and approved by Ennavizion.",
    name: "Verified player / parent testimonial", role: "Pending confirmation",
    image: "https://images.unsplash.com/photo-1506277886164-e25aa3f4ef7f?auto=format&fit=crop&w=400&q=80" }
];

const GALLERY = [
  // Sourced from Unsplash (free-tier "Unsplash License") as stand-ins for real academy
  // photography — swap each "src" for your own photo (1:1 square crop) when ready; the
  // caption/alt text is generated from "caption" below, so update that too if you replace it.
  { src: "https://images.unsplash.com/photo-1786088987909-8fca31316c64?auto=format&fit=crop&w=800&h=800&q=80", category: "training", caption: "Morning rondo session" },
  { src: "https://images.unsplash.com/photo-1598880513655-d1c6d4b2dfbf?auto=format&fit=crop&w=800&h=800&q=80", category: "matches",  caption: "U17 league opener" },
  { src: "https://images.unsplash.com/photo-1518905332052-b6cfda20ee45?auto=format&fit=crop&w=800&h=800&q=80", category: "training", caption: "Goalkeeper handling drills" },
  { src: "https://images.unsplash.com/photo-1680024436315-fb06267264b2?auto=format&fit=crop&w=800&h=800&q=80", category: "events",   caption: "Open trial day" },
  { src: "https://images.unsplash.com/photo-1787535849781-9d35cfde0445?auto=format&fit=crop&w=800&h=800&q=80", category: "matches",  caption: "Last-minute winner" },
  { src: "https://images.unsplash.com/photo-1771577125789-93dc33e63f31?auto=format&fit=crop&w=800&h=800&q=80", category: "events",   caption: "Parents' evening" },
  { src: "https://images.unsplash.com/photo-1502904550040-7534597429ae?auto=format&fit=crop&w=800&h=800&q=80", category: "training", caption: "Strength and conditioning" },
  { src: "https://images.unsplash.com/photo-1715277331664-a546eb85a8e8?auto=format&fit=crop&w=800&h=800&q=80", category: "matches",  caption: "Penalty shoot-out" },
  { src: "https://images.unsplash.com/photo-1664325426906-ba7596dd1258?auto=format&fit=crop&w=800&h=800&q=80", category: "events",   caption: "Scouts at the showcase" }
];

// DEMO CONTENT — every product below is a placeholder: names, prices, SKUs and stock
// status are all illustrative, not real inventory or agreed pricing. Confirm all of it
// with the client before launch. Images are Unsplash stand-ins — replace with real product
// photography of your own kit once printed/branded.
//
// "sku" and "stock" are structural placeholders for a future backend/inventory system —
// nothing currently reads real stock levels, so "stock" only drives the honest, generic
// "Available to order" / "Currently unavailable" label in shop(), never a fake countdown
// ("Only 2 left!") or invented urgency. "colors" is optional; omit it for one-colour items.
const PRODUCTS = [
  { id: "home-kit",  sku: "ENV-KIT-HOME-26", name: "Home kit 2026",        price: 18000, tag: "Kit",       stock: true, colors: ["Green/Gold"], image: "https://images.unsplash.com/photo-1602674809970-89073c530b0a?auto=format&fit=crop&w=700&h=700&q=80" },
  { id: "away-kit",  sku: "ENV-KIT-AWAY-26", name: "Away kit 2026",        price: 18000, tag: "Kit",       stock: true, colors: ["Off-white"], image: "https://images.unsplash.com/photo-1689624291789-7b402a15915a?auto=format&fit=crop&w=700&h=700&q=80" },
  { id: "training",  sku: "ENV-TRN-TOP-26",  name: "Training top",         price: 12000, tag: "Training",  stock: true, image: "https://images.unsplash.com/photo-1774414489671-9f33b8dff0a9?auto=format&fit=crop&w=700&h=700&q=80" },
  { id: "shorts",    sku: "ENV-TRN-SHT-26",  name: "Training shorts",      price: 7500,  tag: "Training",  stock: true, image: "https://images.unsplash.com/photo-1789110519888-758d7ab3908e?auto=format&fit=crop&w=700&h=700&q=80" },
  { id: "ball",      sku: "ENV-EQP-BALL-5",  name: "Match ball, size 5",   price: 15000, tag: "Equipment", stock: true, image: "https://images.unsplash.com/photo-1653273056548-9af3f97dfdca?auto=format&fit=crop&w=700&h=700&q=80" },
  { id: "bottle",    sku: "ENV-EQP-BTL",     name: "Water bottle",         price: 4000,  tag: "Equipment", stock: true, image: "https://images.unsplash.com/photo-1554415955-3e13eb195c5f?auto=format&fit=crop&w=700&h=700&q=80" },
  { id: "cap",       sku: "ENV-MER-CAP",     name: "Academy cap",          price: 6000,  tag: "Merch",     stock: true, image: "https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=700&h=700&q=80" },
  { id: "bag",       sku: "ENV-MER-BAG",     name: "Kit bag",              price: 14000, tag: "Merch",     stock: false, image: "https://images.unsplash.com/photo-1708622833152-924c6e364138?auto=format&fit=crop&w=700&h=700&q=80" }
];

const POSTS = [
  { id: "why-small-sided-games", title: "Why we play small-sided games before 11-a-side",
    date: "2026-09-12", category: "Coaching", minutes: 4,
    // Unsplash stand-in — swap for a real photo of an Ennavizion small-sided session.
    image: "https://images.unsplash.com/photo-1652665314612-c48e10a01598?auto=format&fit=crop&w=1000&q=80",
    imageAlt: "Young footballers contesting the ball in a tight small-sided training game",
    excerpt: "More touches, more decisions, more fun. Here is how 4v4 builds better players than full matches at young ages.",
    body: [
      "At the youngest ages, a full 11-a-side match can mean a child touches the ball a handful of times. In a 4v4 game, the same player might touch it more than fifty times.",
      "Every touch is a chance to make a decision: pass, dribble, shoot, or keep the ball. That repetition is what turns ability into instinct.",
      "Our Foundation and Development groups spend most of their match time in small-sided formats, and move up to larger games as their understanding grows."
    ] },
  { id: "trial-day-guide", title: "What to expect at an Ennavizion trial day",
    date: "2026-08-30", category: "Academy", minutes: 3,
    image: "https://images.unsplash.com/photo-1526494661200-9d7cfd4b2404?auto=format&fit=crop&w=1000&q=80",
    imageAlt: "Coach kneeling to help a young player get ready before a training session",
    excerpt: "Arrival, warm-up, drills, games and feedback — a simple guide for players and parents before their first visit.",
    body: [
      "Trial days start with registration and a relaxed warm-up. Coaches want to see players at their normal level, not nervous.",
      "Players then rotate through technical stations and finish with small-sided games, where most of the assessment happens.",
      "Every family gets written feedback within a week, whatever the outcome, with clear next steps."
    ] },
  { id: "nutrition-for-young-players", title: "Simple match-day eating for young players",
    date: "2026-08-14", category: "Player Development", minutes: 5,
    image: "https://images.unsplash.com/photo-1751394212425-f13d2712d98e?auto=format&fit=crop&w=1000&q=80",
    imageAlt: "Footballer drinking from a sports bottle to rehydrate after training",
    excerpt: "Nothing complicated: what to eat the night before, the morning of, and straight after a game.",
    body: [
      "The evening before a match, a normal balanced meal with rice, yam or pasta alongside protein and vegetables is ideal.",
      "On match morning, eat two to three hours before kick-off and keep it familiar. Water through the morning matters more than any sports drink.",
      "After the game, a snack with carbohydrates and protein within an hour helps recovery — fruit and a sandwich works."
    ] },
  { id: "league-season-preview", title: "League preview: eight teams, one trophy",
    date: "2026-08-01", category: "Matches", minutes: 3,
    image: "https://images.unsplash.com/photo-1722978687695-212eecfa4cbe?auto=format&fit=crop&w=1000&q=80",
    imageAlt: "Young player bursting forward under floodlights during a competitive youth match",
    excerpt: "The new season of the Ennavizion League kicks off with eight youth sides and more scouts in attendance than ever.",
    body: [
      "This season the league grows to eight teams across three cities, with fixtures every Saturday from September to March.",
      "All matches are filmed, and highlight clips are added to each player's scouting profile.",
      "Fixtures and the live table are on the League page, updated after every matchday."
    ] }
];
