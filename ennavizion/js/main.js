/* =========================================================
   ENNAVIZION — site behaviour
   Shared header/footer, mobile menu, cart, forms, and the
   content renderers for each page. Content lives in data.js
   (or the backend's /api/content, which replaces it).
   ========================================================= */

(function () {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const money = (n) => SITE.currency + Number(n).toLocaleString("en-NG");
  const fmtDate = (iso, opts = { day: "numeric", month: "short", year: "numeric" }) =>
    new Date(iso + "T00:00:00").toLocaleDateString("en-GB", opts);
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const joinAnd = (arr) => arr.length <= 1 ? (arr[0] || "") : arr.slice(0, -1).join(", ") + " and " + arr[arr.length - 1];
  const empty = (text) => `<p class="empty-state">${esc(text)}</p>`;
  const mailto = (subject) => `mailto:${SITE.email}${subject ? "?subject=" + encodeURIComponent(subject) : ""}`;
  const emailLink = () => `<a href="${mailto()}">${esc(SITE.email)}</a>`;
  // Forms only send when there is somewhere real for them to go: the backend, a form
  // service, or Netlify Forms on the Netlify site.
  const formsOn = () => Boolean(SITE.apiBase || SITE.formEndpoint || SITE.netlifyForms);
  const viaNetlify = () => !SITE.apiBase && !SITE.formEndpoint && Boolean(SITE.netlifyForms);
  // "[Bracketed]" values are placeholders the academy will replace: shown as text, never
  // turned into links, map pins or choices on a form.
  const isPh = (v) => /\[[^\]]+\]/.test(String(v ?? ""));

  const store = {
    get(key, fallback, area = localStorage) { try { const v = area.getItem(key); return v ? JSON.parse(v) : fallback; } catch { return fallback; } },
    set(key, val, area = localStorage) { try { area.setItem(key, JSON.stringify(val)); } catch { /* storage unavailable */ } },
    del(key, area = localStorage) { try { area.removeItem(key); } catch { /* storage unavailable */ } }
  };
  // Earlier versions kept form submissions (including children's details) in the visitor's
  // browser as a "demo mode". That data never reached the academy; remove any left behind.
  store.del("ennavizion_submissions");

  /* ---------- Content from the backend ---------- */
  // Fetches GET /content (backend ContentController) and overwrites the data.js globals in
  // place, so every renderer below works unchanged whichever source the data came from.
  // Started immediately so the request overlaps HTML parsing. Any failure leaves data.js's
  // content in use — which is honest content, just less of it.
  const contentReady = (async function loadContent() {
    if (!SITE.apiBase) return;
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), 6000);
    try {
      const res = await fetch(`${SITE.apiBase}/content`, { headers: { Accept: "application/json" }, signal: ctrl.signal });
      if (!res.ok) throw new Error("Server responded " + res.status);
      const c = await res.json();
      const swap = (arr, next) => { if (Array.isArray(next)) arr.splice(0, arr.length, ...next); };
      if (c.SITE) Object.assign(SITE, c.SITE, { socials: { ...SITE.socials, ...c.SITE.socials } });
      swap(PROGRAMS, c.PROGRAMS); swap(CENTERS, c.CENTERS); swap(IMPACT, c.IMPACT);
      swap(TESTIMONIALS, c.TESTIMONIALS); swap(GALLERY, c.GALLERY); swap(PRODUCTS, c.PRODUCTS); swap(POSTS, c.POSTS);
      if (c.LEAGUE) Object.assign(LEAGUE, c.LEAGUE);
      if (c.CONTRIBUTIONS) Object.assign(CONTRIBUTIONS, c.CONTRIBUTIONS);
      if (c.GOALS) Object.assign(GOALS, c.GOALS);
    } catch (err) {
      console.warn("Using bundled content from data.js — backend unavailable:", err.message);
    } finally { clearTimeout(timer); }
  })();

  /* ---------- Icons ---------- */
  const ICON = {
    // Club crest (cropped from the client's logo artwork). alt is empty because the
    // brand link always carries the site name as text alongside it.
    logo: '<img class="brand-logo" src="assets/images/logo.webp" alt="" width="68" height="48">',
    cart: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>',
    menu: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>',
    close: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    up: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 14l6-6 6 6"/></svg>',
    wa: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.8-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.4-.3z"/></svg>',
    instagram: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 8.2a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4zM17.3 5.5a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4zM12 3.8c2.7 0 3 0 4 .1 2.7.1 4 1.4 4.1 4.1.1 1 .1 1.3.1 4s0 3-.1 4c-.1 2.7-1.4 4-4.1 4.1-1 .1-1.3.1-4 .1s-3 0-4-.1c-2.7-.1-4-1.4-4.1-4.1-.1-1-.1-1.3-.1-4s0-3 .1-4C4 5.3 5.3 4 8 3.9c1-.1 1.3-.1 4-.1zM12 2C9.3 2 8.9 2 7.9 2.1 4.3 2.2 2.2 4.3 2.1 7.9 2 8.9 2 9.3 2 12s0 3.1.1 4.1c.1 3.6 2.2 5.7 5.8 5.8 1 .1 1.4.1 4.1.1s3.1 0 4.1-.1c3.6-.1 5.7-2.2 5.8-5.8.1-1 .1-1.4.1-4.1s0-3.1-.1-4.1c-.1-3.6-2.2-5.7-5.8-5.8C15.1 2 14.7 2 12 2z"/></svg>',
    x: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.8 3h3.1l-6.8 7.7 8 10.3h-6.2l-4.9-6.4L5.4 21H2.3l7.2-8.3L1.8 3h6.4l4.4 5.8L17.8 3zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5z"/></svg>',
    youtube: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12 31 31 0 0 0 1 16.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.8 31 31 0 0 0-.5-4.8zM9.7 15V9l5.8 3-5.8 3z"/></svg>',
    facebook: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8V6.2c0-.8.2-1.2 1.4-1.2H17V2h-2.6C11.4 2 10.4 3.4 10.4 6v2H8v3h2.4v11H14V11h2.7L17 8h-3z"/></svg>'
  };
  const SOCIAL_LABELS = { instagram: "Instagram", x: "X", youtube: "YouTube", facebook: "Facebook" };

  /* ---------- Navigation ---------- */
  // Labels are user-facing names; filenames stay as they were so existing links keep
  // working ("Academy" is about.html, "Programmes" is programs.html). Shop only appears
  // once there is something to buy.
  const navItems = () => [
    ["index.html", "Home"],
    ["about.html", "Academy"],
    ["programs.html", "Programmes"],
    ["league.html", "League"],
    ["community.html", "Community"],
    ...(PRODUCTS.length ? [["shop.html", "Shop"]] : []),
    ["contact.html", "Contact"]
  ];
  const currentPage = () => {
    const page = (location.pathname.split("/").pop() || "index.html").toLowerCase();
    return { "enroll.html": "programs.html", "donate.html": "community.html" }[page] || page;
  };
  const CTA_LABEL = "Apply for a trial";

  /* ---------- Layout: header first (no waiting on the API), the rest once content is in ---------- */
  function renderHeader() {
    // Pages ship an empty <header class="site-header"> so its space is reserved from the
    // first paint; fall back to creating one if a page doesn't have it.
    let header = $(".site-header");
    if (!header) { header = document.createElement("header"); header.className = "site-header"; document.body.prepend(header); }
    header.innerHTML = `
      <div class="wrap header-inner">
        <a class="brand" href="index.html">${ICON.logo}<span>${esc(SITE.name)}</span></a>
        <nav class="main-nav" id="main-nav" aria-label="Main">
          <ul id="nav-list"></ul>
          <a class="btn btn-gold btn-block nav-cta" href="enroll.html">${CTA_LABEL}</a>
        </nav>
        <div class="header-actions">
          <button class="cart-btn" type="button" data-open-cart aria-label="Open shopping cart" hidden>${ICON.cart}<span class="cart-count" data-count="0">0</span></button>
          <a class="btn btn-gold btn-sm header-cta" href="enroll.html">${CTA_LABEL}</a>
          <button class="menu-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Open menu">${ICON.menu}</button>
        </div>
      </div>`;
    renderNav();

    const skip = document.createElement("a");
    skip.className = "skip-link"; skip.href = "#main"; skip.textContent = "Skip to content";
    document.body.prepend(skip);

    document.body.insertAdjacentHTML("beforeend", `
      <button class="to-top" type="button" aria-label="Back to top">${ICON.up}</button>
      <div class="toast" role="status" aria-live="polite"></div>`);
  }

  function renderNav() {
    const active = currentPage();
    $("#nav-list").innerHTML = navItems().map(([href, label]) =>
      `<li><a href="${href}"${href === active ? ' aria-current="page"' : ""}>${label}</a></li>`).join("");
  }

  function renderFooter() {
    const socials = Object.entries(SITE.socials || {}).filter(([k, url]) => ICON[k] && /^https?:\/\/.+\/.+/.test(url || ""));
    const year = new Date().getFullYear();
    const footer = document.createElement("footer");
    footer.className = "site-footer";
    footer.innerHTML = `
      <div class="wrap">
        <div class="footer-grid">
          <div>
            <a class="brand" href="index.html">${ICON.logo}<span>${esc(SITE.name)}</span></a>
            <p>${esc(SITE.tagline)} A youth football academy for players aged 6 to 18.</p>
            ${socials.length ? `<div class="socials">${socials.map(([k, url]) => `<a href="${esc(url)}" aria-label="${SOCIAL_LABELS[k]}" target="_blank" rel="noopener">${ICON[k]}</a>`).join("")}</div>` : ""}
          </div>
          <div>
            <h2 class="footer-heading">Academy</h2>
            <ul>
              <li><a href="about.html">About the academy</a></li>
              <li><a href="programs.html">Programmes</a></li>
              <li><a href="league.html">League</a></li>
              <li><a href="blog.html">News</a></li>
              <li><a href="gallery.html">Gallery</a></li>
            </ul>
          </div>
          <div>
            <h2 class="footer-heading">Get involved</h2>
            <ul>
              <li><a href="enroll.html">${CTA_LABEL}</a></li>
              <li><a href="donate.html">Support a player</a></li>
              <li><a href="contact.html?topic=sponsor">Become a sponsor</a></li>
              <li><a href="contact.html?topic=partner">Partner with us</a></li>
              <li><a href="contact.html?topic=scout">Scouting requests</a></li>
              ${PRODUCTS.length ? '<li><a href="shop.html">Shop</a></li>' : ""}
            </ul>
          </div>
          <div>
            <h2 class="footer-heading">Contact</h2>
            <ul class="footer-contact">
              <li><a href="${mailto()}">${esc(SITE.email)}</a></li>
              ${SITE.phone ? `<li>${isPh(SITE.phone) ? esc(SITE.phone) : `<a href="tel:${esc(SITE.phone.replace(/[^\d+]/g, ""))}">${esc(SITE.phone)}</a>`}</li>` : ""}
              ${SITE.address ? `<li>${esc(SITE.address)}</li>` : ""}
            </ul>
            ${formsOn() ? `
            <p class="footer-sub">Academy news by email</p>
            <form class="newsletter" data-form="newsletter" novalidate>
              <label class="visually-hidden" for="nl-email">Email address</label>
              <input id="nl-email" type="email" name="email" placeholder="you@example.com" required autocomplete="email">
              <button class="btn btn-gold btn-sm" type="submit">Subscribe</button>
            </form>` : ""}
          </div>
        </div>
        <div class="footer-base">
          <span>© ${year} ${esc(SITE.name)}. All rights reserved.</span>
          <nav class="footer-legal" aria-label="Policies">
            <a href="safeguarding.html">Safeguarding</a>
            <a href="privacy.html">Privacy</a>
            <a href="terms.html">Terms</a>
          </nav>
        </div>
      </div>`;
    document.body.appendChild(footer);
  }

  // Parts of the chrome that depend on content (shop, WhatsApp, contact details).
  function renderContentChrome() {
    renderNav();
    renderFooter();
    $$("[data-email]").forEach((a) => { a.href = mailto(a.dataset.subject); a.textContent = SITE.email; });
    const orderable = PRODUCTS.some((p) => p.stock);
    $(".cart-btn").hidden = !orderable;
    if (orderable) {
      document.body.insertAdjacentHTML("beforeend", `
        <div class="drawer-backdrop" data-close-cart></div>
        <aside class="cart-drawer" id="cart-drawer" aria-label="Shopping cart" aria-hidden="true" inert>
          <div class="cart-head"><h2>Your cart</h2><button class="icon-btn" type="button" data-close-cart aria-label="Close cart">${ICON.close}</button></div>
          <div class="cart-items" id="cart-items"></div>
          <div class="cart-foot">
            <div class="cart-total"><span>Subtotal</span><span id="cart-total">${money(0)}</span></div>
            <button class="btn btn-gold btn-block" type="button" id="checkout-btn">${SITE.whatsapp ? "Send order on WhatsApp" : "Send order by email"}</button>
            <button class="btn-link-block" type="button" data-close-cart>Continue shopping</button>
            <p class="muted cart-note">Nothing is paid on this site. We confirm sizes, availability, delivery or collection, and payment with you directly.</p>
          </div>
        </aside>`);
    }
    if (SITE.whatsapp) {
      document.body.insertAdjacentHTML("beforeend",
        `<a class="wa-float" href="https://wa.me/${esc(SITE.whatsapp)}?text=${encodeURIComponent(`Hello, I'd like to learn more about ${SITE.name} Football Academy.`)}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">${ICON.wa}</a>`);
    }
  }

  function toast(msg) {
    const t = $(".toast");
    t.textContent = msg; t.classList.add("show");
    clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove("show"), 2600);
  }

  /* ---------- Mobile menu + back to top ---------- */
  function initChrome() {
    const btn = $(".menu-toggle"), nav = $("#main-nav");
    const mobile = matchMedia("(max-width: 1140px)");
    let open = false;
    const setOpen = (next, { restoreFocus = false } = {}) => {
      open = next && mobile.matches;
      nav.classList.toggle("open", open);
      // Closed on mobile = off-screen, so take it out of the tab order and screen-reader tree.
      nav.inert = mobile.matches && !open;
      btn.setAttribute("aria-expanded", open);
      btn.setAttribute("aria-label", open ? "Close menu" : "Open menu");
      btn.innerHTML = open ? ICON.close : ICON.menu;
      document.body.classList.toggle("menu-open", open);
      // While the full-screen menu is open, keep focus inside it (header stays usable).
      ["main", ".site-footer"].forEach((s) => { const el = $(s); if (el) el.inert = open; });
      if (open) $("a", nav)?.focus();
      else if (restoreFocus) btn.focus();
    };
    btn.addEventListener("click", () => setOpen(!open, { restoreFocus: true }));
    nav.addEventListener("click", (e) => { if (e.target.closest("a")) setOpen(false); });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape" && open) setOpen(false, { restoreFocus: true }); });
    mobile.addEventListener("change", () => setOpen(false));
    window.addEventListener("pageshow", () => setOpen(false)); // back/forward cache restores
    setOpen(false);

    const top = $(".to-top");
    window.addEventListener("scroll", () => top.classList.toggle("show", scrollY > 600), { passive: true });
    top.addEventListener("click", () => scrollTo({ top: 0, behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth" }));
  }

  /* ---------- Cart ---------- */
  const CART_KEY = "ennavizion_cart";
  const cart = {
    items: store.get(CART_KEY, []),
    save() { store.set(CART_KEY, this.items); renderCart(); },
    add(id, size) {
      const key = id + "|" + (size || "");
      const hit = this.items.find((i) => i.key === key);
      if (hit) hit.qty += 1; else this.items.push({ key, id, size: size || "", qty: 1 });
      this.save();
    },
    change(key, delta) {
      const it = this.items.find((i) => i.key === key); if (!it) return;
      it.qty += delta; if (it.qty < 1) this.items = this.items.filter((i) => i !== it);
      this.save();
    },
    remove(key) { this.items = this.items.filter((i) => i.key !== key); this.save(); },
    total() { return this.items.reduce((s, i) => s + (PRODUCTS.find((p) => p.id === i.id)?.price || 0) * i.qty, 0); },
    count() { return this.items.reduce((s, i) => s + i.qty, 0); }
  };

  function renderCart() {
    const c = cart.count();
    $$(".cart-count").forEach((el) => { el.textContent = c; el.dataset.count = c; });
    const box = $("#cart-items");
    if (!box) return;
    box.innerHTML = !cart.items.length
      ? '<p class="cart-empty">Your cart is empty.</p>'
      : cart.items.map((i) => {
        const p = PRODUCTS.find((x) => x.id === i.id); if (!p) return "";
        return `<div class="cart-item">
          <div><strong>${esc(p.name)}</strong><small>${i.size ? "Size " + esc(i.size) + " — " : ""}${money(p.price)} each</small>
            <div><span class="qty"><button type="button" data-qty="-1" data-key="${esc(i.key)}" aria-label="Decrease quantity of ${esc(p.name)}">−</button><span>${i.qty}</span><button type="button" data-qty="1" data-key="${esc(i.key)}" aria-label="Increase quantity of ${esc(p.name)}">+</button></span><button class="remove-link" type="button" data-remove="${esc(i.key)}">Remove</button></div>
          </div>
          <strong>${money(p.price * i.qty)}</strong>
        </div>`;
      }).join("");
    $("#cart-total").textContent = money(cart.total());
    $("#checkout-btn").disabled = !cart.items.length;
  }

  function initCart() {
    const drawer = $("#cart-drawer"); if (!drawer) return;
    // Drop saved items whose product no longer exists (removed in the admin).
    const known = cart.items.filter((i) => PRODUCTS.some((p) => p.id === i.id && p.stock));
    if (known.length !== cart.items.length) { cart.items = known; store.set(CART_KEY, known); }
    const backdrop = $(".drawer-backdrop"); let lastFocus;
    const open = () => { lastFocus = document.activeElement; drawer.inert = false; drawer.classList.add("open"); backdrop.classList.add("open"); drawer.setAttribute("aria-hidden", "false"); $(".icon-btn", drawer).focus(); };
    const close = () => { if (!drawer.classList.contains("open")) return; drawer.classList.remove("open"); backdrop.classList.remove("open"); drawer.setAttribute("aria-hidden", "true"); drawer.inert = true; lastFocus?.focus(); };
    document.addEventListener("click", (e) => {
      if (e.target.closest("[data-open-cart]")) open();
      if (e.target.closest("[data-close-cart]")) close();
      const q = e.target.closest("[data-qty]"); if (q) cart.change(q.dataset.key, Number(q.dataset.qty));
      const r = e.target.closest("[data-remove]"); if (r) cart.remove(r.dataset.remove);
    });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") close(); });
    $("#checkout-btn").addEventListener("click", () => {
      const lines = cart.items.map((i) => {
        const p = PRODUCTS.find((x) => x.id === i.id);
        return `- ${i.qty} x ${p.name}${i.size ? " (size " + i.size + ")" : ""} [${p.sku || p.id}] — ${money(p.price * i.qty)}`;
      });
      const msg = `Hello ${SITE.name}, I'd like to order:\n${lines.join("\n")}\nTotal: ${money(cart.total())}\n\nPlease confirm availability, delivery or collection, and how to pay.`;
      if (SITE.whatsapp) window.open(`https://wa.me/${SITE.whatsapp}?text=${encodeURIComponent(msg)}`, "_blank", "noopener");
      else location.href = `mailto:${SITE.email}?subject=${encodeURIComponent("Shop order")}&body=${encodeURIComponent(msg)}`;
    });
    renderCart();
  }

  /* ---------- Forms ---------- */
  function validateField(field) {
    const input = $("input, select, textarea", field); if (!input) return true;
    let ok = true;
    if (input.type === "radio") ok = !input.required || $$(`input[name="${input.name}"]`, field).some((r) => r.checked);
    else if (input.type === "checkbox") ok = !input.required || input.checked;
    else ok = input.checkValidity();
    // A field-specific check (e.g. age range) gives a clearer message than the browser's
    // generic one, so it also runs when the value is merely out of the min/max range.
    const m = $(".error-msg", field);
    if (m && m.dataset.default == null) m.dataset.default = m.textContent;
    let msg = "";
    const outOfRange = input.validity && (input.validity.rangeOverflow || input.validity.rangeUnderflow);
    if (typeof field._check === "function" && (ok || outOfRange)) msg = field._check(input);
    if (msg) ok = false;
    if (m) m.textContent = msg || m.dataset.default;
    field.classList.toggle("invalid", !ok);
    input.setAttribute("aria-invalid", String(!ok));
    return ok;
  }

  // Links each field's error message to its input so screen readers announce it.
  function wireErrors(form) {
    $$(".field", form).forEach((f, i) => {
      const msg = $(".error-msg", f), input = $("input, select, textarea", f);
      if (!msg || !input) return;
      msg.id = msg.id || `${form.id || form.dataset.form}-err-${i}`;
      if (input.type !== "radio") input.setAttribute("aria-describedby", msg.id);
      msg.setAttribute("aria-live", "polite");
    });
  }

  // Hidden field humans never see or fill; bots that fill every input get dropped server-side.
  function addHoneypot(form) {
    if ($('[name="website"]', form)) return;
    form.insertAdjacentHTML("beforeend", '<div class="hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>');
  }

  const clientRef = (prefix) => `${prefix}-${Date.now().toString(36).toUpperCase()}-${Math.random().toString(36).slice(2, 6).toUpperCase()}`;

  // Sends to the backend (stored + staff notified) or a configured form service. Never
  // reports success unless the server accepted the submission.
  async function submitForm(form, type) {
    const data = Object.fromEntries(new FormData(form).entries());
    if (SITE.apiBase) {
      const res = await fetch(`${SITE.apiBase}/submissions`, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify({ type, ...data }) });
      const body = await res.json().catch(() => ({}));
      if (!res.ok) {
        const firstError = body.errors ? Object.values(body.errors)[0]?.[0] : null;
        throw new Error(res.status === 429 ? "Too many attempts. Wait a minute and try again." : firstError || body.message || "The server couldn't accept it (" + res.status + ").");
      }
      return { ...data, reference: body.reference };
    }
    if (viaNetlify()) {
      // Netlify Forms: the form names and fields are declared in netlify-forms.html.
      const reference = type === "newsletter" ? undefined : clientRef(type === "enrolment" ? "ENR" : "MSG");
      delete data.medical_notes; delete data.notes; // not collected without the encrypted backend
      const body = new URLSearchParams({ "form-name": type, ...(reference ? { reference } : {}), ...data });
      const res = await fetch("/", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body.toString() });
      if (!res.ok) throw new Error("The form couldn't be delivered (" + res.status + ").");
      return { ...data, reference };
    }
    if (SITE.formEndpoint) {
      const reference = type === "enrolment" ? clientRef("ENR") : undefined;
      const res = await fetch(SITE.formEndpoint, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify({ _type: type, reference, ...data }) });
      if (!res.ok) throw new Error("The form service couldn't accept it (" + res.status + ").");
      return { ...data, reference };
    }
    throw new Error("Online forms aren't connected yet.");
  }

  function formsOffNotice(what) {
    return `<div class="notice" role="note"><h2>Online ${what} isn't open yet</h2><p>Email ${emailLink()} and we'll reply to you directly.</p></div>`;
  }

  function initForms() {
    $$("form[data-form]").forEach((form) => {
      const type = form.dataset.form;
      wireErrors(form);
      $$(".field", form).forEach((f) => f.addEventListener("change", () => f.classList.contains("invalid") && validateField(f)));
      if (type === "enroll" || type === "donate") return; // handled by initEnroll / initDonate
      if (!formsOn()) {
        if (type === "contact") form.outerHTML = `<div class="panel">${formsOffNotice("messaging")}</div>`;
        return;
      }
      addHoneypot(form);
      form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const status = $(".form-status", form);
        const allOk = $$(".field", form).map(validateField).every(Boolean);
        if (type === "newsletter") {
          const input = $("input[type=email]", form);
          if (!input.checkValidity()) { toast("Enter a valid email address to subscribe."); input.focus(); return; }
        } else if (!allOk) { $(".field.invalid input, .field.invalid select, .field.invalid textarea", form)?.focus(); return; }
        const btn = $('button[type="submit"]', form); const label = btn.textContent;
        btn.disabled = true; btn.textContent = "Sending…";
        try {
          await submitForm(form, type);
          if (type === "newsletter") toast("Subscribed. Thank you.");
          else if (status) { status.className = "form-status ok"; status.textContent = "Message received. We'll reply to the email address you gave."; }
          form.reset();
        } catch (err) {
          if (status) { status.className = "form-status err"; status.textContent = `Your message didn't send. ${err.message} You can also email ${SITE.email}.`; }
          else toast("Subscription didn't go through. Try again in a moment.");
        } finally { btn.disabled = false; btn.textContent = label; }
      });
    });
  }

  /* ---------- Shared bits used on several pages ---------- */
  function photo(src, label, extraClass = "") {
    return `<div class="photo ${extraClass}">${src ? `<img src="${esc(src)}" alt="${esc(label)}" loading="lazy" decoding="async">` : ""}</div>`;
  }

  // Parses "Ages 6–9" / "Ages 10-18" into [6, 9]. null if the label has no numbers.
  function ageRange(label) {
    const n = String(label || "").match(/\d+/g);
    return n ? [Number(n[0]), Number(n[n.length - 1])] : null;
  }
  const allAges = () => {
    const r = PROGRAMS.map((p) => ageRange(p.ages)).filter(Boolean);
    return r.length ? [Math.min(...r.map((x) => x[0])), Math.max(...r.map((x) => x[1]))] : null;
  };
  // Age in whole years on a given date (default today).
  function ageOn(dobIso, on = new Date()) {
    const d = new Date(dobIso + "T00:00:00"); if (isNaN(d)) return NaN;
    let a = on.getFullYear() - d.getFullYear();
    if (on.getMonth() < d.getMonth() || (on.getMonth() === d.getMonth() && on.getDate() < d.getDate())) a--;
    return a;
  }
  const feeHTML = (p) => p.fee ? `<strong>${money(p.fee)}</strong><small>${esc(p.period)}</small>` : '<strong class="fee-tbc">Fees on request</strong>';

  /* ---------- League ---------- */
  const TIEBREAK_LABELS = { goal_difference: "goal difference", goals_for: "goals scored", wins: "wins" };
  const rules = () => ({ win: 3, draw: 1, loss: 0, tiebreakers: ["goal_difference", "goals_for"], ...(LEAGUE.rules || {}) });

  // The backend sends the table already calculated from published results. Without a
  // backend, calculate it here from LEAGUE.results with the same rules.
  function standings() {
    if (LEAGUE.table && LEAGUE.table.length) return LEAGUE.table;
    const r = rules(), rows = new Map();
    const row = (name) => {
      if (!rows.has(name)) rows.set(name, { team: name, p: 0, w: 0, d: 0, l: 0, gf: 0, ga: 0, us: name.startsWith(SITE.name) });
      return rows.get(name);
    };
    (LEAGUE.teams || []).forEach((t) => { row(t.name).us = Boolean(t.us); });
    (LEAGUE.results || []).forEach((m) => {
      const h = row(m.home), a = row(m.away);
      h.p++; a.p++; h.gf += m.hs; h.ga += m.as; a.gf += m.as; a.ga += m.hs;
      if (m.hs > m.as) { h.w++; a.l++; } else if (m.hs < m.as) { a.w++; h.l++; } else { h.d++; a.d++; }
    });
    const key = { goal_difference: (x) => x.gd, goals_for: (x) => x.gf, wins: (x) => x.w };
    return [...rows.values()]
      .map((x) => ({ ...x, gd: x.gf - x.ga, pts: x.w * r.win + x.d * r.draw + x.l * r.loss }))
      .sort((a, b) => b.pts - a.pts || r.tiebreakers.reduce((acc, t) => acc || (key[t] ? key[t](b) - key[t](a) : 0), 0) || a.team.localeCompare(b.team));
  }

  function rulesText() {
    const r = rules();
    const tb = r.tiebreakers.map((t) => TIEBREAK_LABELS[t]).filter(Boolean);
    return `${r.win} points for a win, ${r.draw} for a draw${r.loss ? `, ${r.loss} for a loss` : ""}.` +
      (tb.length ? ` Teams level on points are separated by ${joinAnd(tb)}.` : "");
  }

  const TABLE_HEAD = '<thead><tr><th class="pos" scope="col"><span class="visually-hidden">Position</span></th><th class="team" scope="col">Team</th><th scope="col"><abbr title="Played">P</abbr></th><th scope="col"><abbr title="Won">W</abbr></th><th scope="col"><abbr title="Drawn">D</abbr></th><th scope="col"><abbr title="Lost">L</abbr></th><th scope="col"><abbr title="Goal difference">GD</abbr></th><th scope="col"><abbr title="Points">Pts</abbr></th></tr></thead>';

  function tableHTML(limit) {
    const rows = standings();
    if (!rows.length) return null;
    return TABLE_HEAD + "<tbody>" + (limit ? rows.slice(0, limit) : rows).map((r, i) => `
      <tr class="${r.us ? "us" : ""}">
        <td class="pos">${i + 1}</td><th class="team" scope="row">${esc(r.team)}</th>
        <td>${r.p}</td><td>${r.w}</td><td>${r.d}</td><td>${r.l}</td><td>${r.gd > 0 ? "+" : ""}${r.gd}</td><td class="pts">${r.pts}</td>
      </tr>`).join("") + "</tbody>";
  }

  function matchHTML(m, played) {
    const when = played
      ? fmtDate(m.date, { day: "numeric", month: "short", year: "numeric" })
      : [fmtDate(m.date, { weekday: "short", day: "numeric", month: "short" }), [m.time, m.venue].filter(Boolean).map(esc).join(", ")].filter(Boolean).join("<br>");
    return `<li class="match"><span class="when">${when}</span><span class="home">${esc(m.home)}</span>${played ? `<span class="score" aria-label="${esc(m.hs)} to ${esc(m.as)}">${esc(m.hs)} – ${esc(m.as)}</span>` : '<span class="vs">vs</span>'}<span class="away">${esc(m.away)}</span></li>`;
  }
  const NO_FIXTURES = "No upcoming fixtures have been published.";
  const NO_RESULTS = "Match results will appear here when available.";
  const NO_TABLE = "The league table will appear once results are published.";

  function postCard(p) {
    return `<a class="post-card" href="post.html?id=${encodeURIComponent(p.id)}">
      ${photo(p.image || "", p.imageAlt || "")}
      <div class="post-meta">${fmtDate(p.date)} · ${esc(p.category)} · ${esc(p.minutes)} min read</div>
      <h3>${esc(p.title)}</h3><p>${esc(p.excerpt)}</p></a>`;
  }

  function impactHTML() {
    return IMPACT.map((s) => `<div class="impact-stat"><strong>${esc(s.value)}</strong><span>${esc(s.label)}</span></div>`).join("");
  }

  function testimonialHTML(t) {
    return `<figure class="wrap quote">
      ${t.image ? photo(t.image, "", "avatar") : ""}
      <blockquote>${esc(t.quote)}</blockquote>
      <figcaption><strong>${esc(t.name)}</strong><span>${esc(t.role)}</span></figcaption>
    </figure>`;
  }

  function programCards() {
    return PROGRAMS.map((p) => `
      <article class="program-card">
        <h3>${esc(p.name)}</h3>
        <p class="ages">${esc(p.ages)}</p>
        <p>${esc(p.text)}</p>
        <a class="text-link" href="programs.html#program-${esc(p.id)}">Programme details</a>
      </article>`).join("");
  }

  // Removes a section (and keeps the alternating backgrounds tidy) when it has nothing to show.
  const dropSection = (el) => el?.closest("section")?.remove();

  /* ---------- Team profiles (About page) ---------- */
  // Verified + published staff (STAFF in data.js) are shown when there are any. Until then the
  // sample profiles in js/demo-team.js are shown, each labelled "Demo profile" under a note
  // saying they are examples, not Ennavizion staff. The "announced soon" section is the
  // fallback if the sample file can't load.
  const loadScript = (src) => new Promise((resolve, reject) => {
    const el = document.createElement("script"); el.src = src; el.onload = resolve; el.onerror = reject; document.head.appendChild(el);
  });

  function teamCard(m, demo) {
    return `<article class="team-card">
      <div class="team-photo">${m.image ? `<img src="${esc(m.image)}" alt="${esc(m.altText || "")}" width="400" height="500" loading="lazy" decoding="async">` : ""}
        ${demo ? '<span class="team-badge">Demo profile</span>' : ""}</div>
      <div class="team-body">
        <h3>${esc(m.name)}</h3>
        <p class="team-role">${esc(m.role)}</p>
        ${m.description ? `<p class="team-desc">${esc(m.description)}</p>` : ""}
      </div>
    </article>`;
  }

  async function renderTeam() {
    const grid = $("#staff-grid"); if (!grid) return;
    const real = (typeof STAFF !== "undefined" ? STAFF : []).filter((m) => m.isVerified && m.isPublished);
    if (real.length) { grid.innerHTML = real.map((m) => teamCard(m, false)).join(""); return; }
    {
      try {
        await loadScript("js/demo-team.js?v=20261109d");
        grid.insertAdjacentHTML("beforebegin", '<div class="notice notice-test team-demo-note" role="note"><h3>Sample profiles</h3><p>These names and stock photos are examples while the academy finalises its team. They are not Ennavizion staff and the people pictured are not connected with the academy; real profiles will replace them once appointments are confirmed.</p></div>');
        grid.innerHTML = DEMO_STAFF.map((m) => teamCard(m, true)).join("");
        return;
      } catch { /* fall through to the announcement */ }
    }
    const roles = typeof TEAM_ROLES !== "undefined" ? TEAM_ROLES : [];
    grid.outerHTML = `<div class="team-soon">
      <h3>Our coaching team profiles will be announced soon</h3>
      <p>We're finalising appointments and the checks every member of staff completes before working with players. Profiles will appear here once they're confirmed.</p>
      ${roles.length ? `<p class="team-soon-label">The team will include</p><ul class="role-chips">${roles.map((r) => `<li>${esc(r)}</li>`).join("")}</ul>` : ""}
    </div>`;
  }

  /* ---------- Page renderers ---------- */
  const pages = {
    home() {
      const ages = allAges();
      const strip = $("#hero-strip");
      if (strip) {
        const items = [];
        if (ages) items.push([`Ages ${ages[0]}–${ages[1]}`, `${PROGRAMS.length} programmes grouped by age and level`]);
        if (CENTERS.length) items.push([`${CENTERS.length} ${CENTERS.length === 1 ? "centre" : "centres"}`, joinAnd(CENTERS.map((c) => c.name))]);
        if (LEAGUE.season) items.push(["Ennavizion League", LEAGUE.season]);
        items.push(["Parents kept informed", "From the first application to every next step"]);
        strip.innerHTML = items.slice(0, 3).map(([a, b]) => `<div><strong>${esc(a)}</strong><span>${esc(b)}</span></div>`).join("");
      }
      const pc = $("#home-programs"); if (pc) { if (PROGRAMS.length) pc.innerHTML = programCards(); else dropSection(pc); }

      const t = $("#home-table"), f = $("#home-fixtures");
      const s = $("#home-season"); if (s) s.textContent = LEAGUE.season ? `${LEAGUE.season}.` : "Fixtures, results and the table, published by the academy.";
      const table = tableHTML(5);
      if (t) t.closest(".table-scroll").outerHTML = table ? `<div class="table-scroll"><table class="league-table"><caption class="visually-hidden">League table, top five</caption>${table}</table></div>` : empty(NO_TABLE);
      if (f) f.outerHTML = LEAGUE.fixtures.length ? `<ul class="match-list">${LEAGUE.fixtures.slice(0, 3).map((m) => matchHTML(m)).join("")}</ul>` : empty(NO_FIXTURES);

      const im = $("#home-impact"); if (im) { if (IMPACT.length) im.innerHTML = impactHTML(); else im.remove(); }
      const tm = $("#home-testimonial"); if (tm) { if (TESTIMONIALS.length) tm.outerHTML = testimonialHTML(TESTIMONIALS[0]); else dropSection(tm); }
      const b = $("#home-posts"); if (b) { if (POSTS.length) b.innerHTML = POSTS.slice(0, 3).map(postCard).join(""); else dropSection(b); }
    },

    about() { renderTeam(); },

    community() {
      const im = $("#community-impact"); if (im) { if (IMPACT.length) im.innerHTML = impactHTML(); else dropSection(im); }
      const g = $("#community-goals"); if (g) { const h = goalsHTML(); if (h) g.innerHTML = h; else dropSection(g); }
    },

    donate() { initDonate(); },

    programs() {
      const n = $("#program-count"); if (n) n.textContent = PROGRAMS.length === 4 ? "Four" : String(PROGRAMS.length);
      const list = $("#program-list");
      if (list) list.innerHTML = PROGRAMS.length ? PROGRAMS.map((p) => `
        <div class="program" id="program-${esc(p.id)}">
          <div><h3>${esc(p.name)}</h3><span class="ages">${esc(p.ages)}</span></div>
          <p>${esc(p.text)}</p>
          <div class="fee">${feeHTML(p)}
            <div class="fee-actions">
              <a class="btn btn-green btn-sm" href="enroll.html?program=${encodeURIComponent(p.id)}">${CTA_LABEL}</a>
              ${p.fee ? "" : `<a class="text-link" href="contact.html?topic=enrolment&amp;program=${encodeURIComponent(p.name)}">Request fee information</a>`}
            </div>
          </div>
        </div>`).join("") : empty("Programme details will be published soon. Contact us to ask about places.");
      const notes = $("#fee-notes");
      if (notes) {
        const items = [];
        if (SITE.freeTrial) items.push("Every new player's first trial session is free, subject to available places.");
        if (SITE.registrationFee) items.push(`One-time registration fee of ${money(SITE.registrationFee)} for newly admitted players.`);
        if (SITE.goalkeeperAddOn) items.push(`Players in another programme can add specialist goalkeeper training for ${money(SITE.goalkeeperAddOn)} a month, subject to places.`);
        items.push("Fees cover scheduled coaching, development planning, progress updates for parents and supervised academy activities. Transport, personal kit and external tournament costs aren't included.");
        notes.innerHTML = `<ul class="check-list">${items.map((t) => `<li>${esc(t)}</li>`).join("")}</ul>`;
      }
      const c = $("#centers");
      if (c) c.outerHTML = CENTERS.length
        ? `<div class="grid grid-3" id="centers">${CENTERS.map((x) => `<div class="panel"><h3>${esc(x.name)}</h3>${x.pitch ? `<p class="muted">${esc(x.pitch)}</p>` : ""}${x.days ? `<p><strong>Training days:</strong> ${esc(x.days)}</p>` : ""}${isPh(x.name) ? "" : `<a href="contact.html?topic=visit&amp;center=${encodeURIComponent(x.name)}">Arrange a visit</a>`}</div>`).join("")}</div>`
        : empty("Training locations and days are confirmed with each family when we review an application.");
    },

    league() {
      $("#season-name").textContent = LEAGUE.season || "Fixtures, results and the league table";
      const lead = $("#league-lead");
      if (lead) {
        const teams = (LEAGUE.teams?.length) || standings().length;
        lead.textContent = teams ? `${teams} youth teams. Fixtures and results are published here after each matchday.` : "Fixtures and results are published here as soon as the academy confirms them.";
      }
      const table = tableHTML();
      $("#league-table-slot").innerHTML = table ? `<div class="table-scroll" tabindex="0" role="region" aria-label="League table"><table class="league-table" id="league-table"><caption class="visually-hidden">League table</caption>${table}</table></div>` : empty(NO_TABLE);
      $("#league-rules").textContent = rulesText();
      $("#fixtures").outerHTML = LEAGUE.fixtures.length ? `<ul class="match-list" id="fixtures">${LEAGUE.fixtures.map((m) => matchHTML(m)).join("")}</ul>` : empty(NO_FIXTURES);
      $("#results").outerHTML = LEAGUE.results.length ? `<ul class="match-list" id="results">${LEAGUE.results.map((m) => matchHTML(m, true)).join("")}</ul>` : empty(NO_RESULTS);
      initTabs($("#league-tabs"));
    },

    gallery() {
      const grid = $("#gallery-grid");
      if (!GALLERY.length) { $(".tabs", grid.parentElement)?.remove(); grid.outerHTML = empty("Photos from training and match days will appear here."); return; }
      let shown = GALLERY.map((g, i) => ({ ...g, i }));
      const draw = () => {
        grid.innerHTML = shown.map((g) => `<figure><button type="button" data-lb="${g.i}" aria-label="View larger: ${esc(g.caption)}">${photo(g.src, g.caption)}</button><figcaption>${esc(g.caption)}</figcaption></figure>`).join("");
      };
      draw();
      $$("[data-filter]").forEach((b) => b.addEventListener("click", () => {
        $$("[data-filter]").forEach((x) => x.setAttribute("aria-pressed", x === b));
        const cat = b.dataset.filter;
        shown = GALLERY.map((g, i) => ({ ...g, i })).filter((g) => cat === "all" || g.category === cat);
        draw();
      }));
      const lb = $("#lightbox"); let idx = 0; let lastFocus;
      const visible = () => shown.map((g) => g.i);
      const show = (i) => { idx = i; const g = GALLERY[i]; $(".lb-photo", lb).innerHTML = photo(g.src, g.caption); $("p", lb).textContent = g.caption; };
      const step = (d) => { const v = visible(); const pos = v.indexOf(idx); show(v[(pos + d + v.length) % v.length]); };
      grid.addEventListener("click", (e) => { const b = e.target.closest("[data-lb]"); if (!b) return; lastFocus = b; show(Number(b.dataset.lb)); lb.hidden = false; lb.classList.add("open"); $(".lightbox-close", lb).focus(); });
      const close = () => { lb.classList.remove("open"); lb.hidden = true; lastFocus?.focus(); };
      $(".lightbox-close", lb).addEventListener("click", close);
      $(".lightbox-prev", lb).addEventListener("click", () => step(-1));
      $(".lightbox-next", lb).addEventListener("click", () => step(1));
      lb.addEventListener("click", (e) => { if (e.target === lb) close(); });
      document.addEventListener("keydown", (e) => {
        if (!lb.classList.contains("open")) return;
        if (e.key === "Escape") close(); if (e.key === "ArrowLeft") step(-1); if (e.key === "ArrowRight") step(1);
        if (e.key === "Tab") { // keep focus inside the dialog
          const f = $$("button", lb); const i = f.indexOf(document.activeElement);
          if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); } else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
        }
      });
    },

    blog() {
      const grid = $("#post-grid"), search = $("#post-search");
      if (!POSTS.length) { $("#post-tools")?.remove(); grid.outerHTML = empty("News from the academy will appear here."); return; }
      const cats = ["All", ...new Set(POSTS.map((p) => p.category))];
      let cat = "All";
      $("#post-cats").innerHTML = cats.map((c) => `<button class="tab" type="button" data-cat="${esc(c)}" aria-pressed="${c === "All"}">${esc(c)}</button>`).join("");
      const draw = () => {
        const q = search.value.trim().toLowerCase();
        const list = POSTS.filter((p) => (cat === "All" || p.category === cat) && (!q || (p.title + p.excerpt).toLowerCase().includes(q)));
        grid.innerHTML = list.length ? list.map(postCard).join("") : '<p class="muted">No news matches that search. Try a shorter word, or pick "All".</p>';
      };
      $("#post-cats").addEventListener("click", (e) => {
        const b = e.target.closest("[data-cat]"); if (!b) return; cat = b.dataset.cat;
        $$("[data-cat]").forEach((x) => x.setAttribute("aria-pressed", x === b)); draw();
      });
      search.addEventListener("input", draw);
      draw();
    },

    post() {
      const id = new URLSearchParams(location.search).get("id");
      const p = POSTS.find((x) => x.id === id);
      if (!p) { $("#post-title").textContent = "Article not found"; $("#post-body").innerHTML = '<p>This article may have moved. <a href="blog.html">See all news</a>.</p>'; $("#post-more")?.closest("section")?.remove(); return; }
      document.title = `${p.title} | ${SITE.name}`;
      $("#post-title").textContent = p.title;
      $("#post-meta").textContent = `${fmtDate(p.date)} · ${p.category} · ${p.minutes} min read`;
      // bodyHtml comes from the admin panel's rich-text editor (written by signed-in staff
      // only); data.js posts are plain paragraphs and get escaped.
      $("#post-body").innerHTML = p.bodyHtml || (p.body || []).map((para) => `<p>${esc(para)}</p>`).join("");
      const more = POSTS.filter((x) => x.id !== p.id).slice(0, 3);
      if (more.length) $("#post-more").innerHTML = more.map(postCard).join(""); else $("#post-more")?.closest("section")?.remove();
    },

    shop() {
      const grid = $("#product-grid");
      if (!PRODUCTS.length) {
        $("#shop-tools")?.remove(); $("#shop-info")?.remove();
        grid.outerHTML = `<div class="notice"><h2>The academy shop isn't open yet</h2><p>Kit and merchandise will be listed here when they're available. For kit questions, <a href="contact.html?topic=other">contact us</a>.</p></div>`;
        return;
      }
      const tags = ["All", ...new Set(PRODUCTS.map((p) => p.tag))];
      $("#shop-filters").innerHTML = tags.map((t) => `<button class="tab" type="button" data-tag="${esc(t)}" aria-pressed="${t === "All"}">${esc(t)}</button>`).join("");
      $("#order-channel").textContent = SITE.whatsapp ? "on WhatsApp" : "by email";
      const sized = (p) => p.tag === "Kit" || p.tag === "Training";
      const draw = (tag) => {
        grid.innerHTML = PRODUCTS.filter((p) => tag === "All" || p.tag === tag).map((p) => `
          <article class="product">
            ${photo(p.image || "", p.name)}
            <div class="product-body">
              <div class="product-body-top">
                <span class="tag">${esc(p.tag)}</span>
                <span class="stock-status ${p.stock ? "in" : "out"}">${p.stock ? "Available to order" : isPh(p.name) ? "Coming soon" : "Currently unavailable"}</span>
              </div>
              <h3>${esc(p.name)}</h3>
              ${p.colors ? `<p class="muted product-meta">Colour: ${esc([].concat(p.colors).join(", "))}</p>` : ""}
              ${sized(p) ? `<label class="visually-hidden" for="size-${esc(p.id)}">Size for ${esc(p.name)}</label><select id="size-${esc(p.id)}" ${p.stock ? "" : "disabled"}><option value="">Choose size</option>${["Youth S", "Youth M", "Youth L", "S", "M", "L", "XL"].map((s) => `<option>${s}</option>`).join("")}</select>` : ""}
              <div class="price">${p.price ? money(p.price) : SITE.currency + "[price]"}</div>
              <button class="btn btn-line btn-block btn-sm" type="button" data-add="${esc(p.id)}" ${p.stock ? "" : "disabled"}>${p.stock ? "Add to cart" : isPh(p.name) ? "Coming soon" : "Unavailable"}</button>
            </div>
          </article>`).join("");
      };
      draw("All");
      $("#shop-filters").addEventListener("click", (e) => {
        const b = e.target.closest("[data-tag]"); if (!b) return;
        $$("[data-tag]").forEach((x) => x.setAttribute("aria-pressed", x === b)); draw(b.dataset.tag);
      });
      grid.addEventListener("click", (e) => {
        const b = e.target.closest("[data-add]"); if (!b) return;
        const p = PRODUCTS.find((x) => x.id === b.dataset.add);
        const sel = $(`#size-${CSS.escape(p.id)}`);
        if (sel && !sel.value) { toast("Choose a size first."); sel.focus(); return; }
        cart.add(p.id, sel ? sel.value : "");
        toast(`Added ${p.name} to your cart.`);
      });
    },

    contact() {
      const params = new URLSearchParams(location.search);
      const topic = params.get("topic"), center = params.get("center"), program = params.get("program");
      if (topic && $("#c-topic") && $(`#c-topic option[value="${CSS.escape(topic)}"]`)) $("#c-topic").value = topic;
      if ($("#c-message")) {
        if (center) $("#c-message").value = `I'd like to visit the ${center} centre.`;
        if (program) $("#c-message").value = `Please send fee information for the ${program} programme.`;
      }
      const rows = [
        ["Email", `<a href="${mailto()}">${esc(SITE.email)}</a>`],
        SITE.phone && ["Phone", isPh(SITE.phone) ? `<strong>${esc(SITE.phone)}</strong>` : `<a href="tel:${esc(SITE.phone.replace(/[^\d+]/g, ""))}">${esc(SITE.phone)}</a>`],
        SITE.whatsapp && ["WhatsApp", `<a href="https://wa.me/${esc(SITE.whatsapp)}" target="_blank" rel="noopener">Chat with us</a>`],
        SITE.address && ["Address", `<strong>${esc(SITE.address)}</strong>`],
        SITE.hours && ["Office hours", `<strong>${esc(SITE.hours)}</strong>`]
      ].filter(Boolean);
      $("#contact-list").innerHTML = rows.map(([k, v]) => `<li><span>${k}</span>${v}</li>`).join("");
      if (SITE.address && !isPh(SITE.address)) $("#contact-list").insertAdjacentHTML("afterend", `<iframe class="map" title="Map showing ${esc(SITE.address)}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=${encodeURIComponent(SITE.address)}&output=embed"></iframe>`);
    },

    enroll() { initEnroll(); }
  };

  function initTabs(root) {
    if (!root) return;
    const tabs = $$('[role="tab"]', root);
    const select = (t) => tabs.forEach((x) => { const on = x === t; x.setAttribute("aria-selected", on); x.tabIndex = on ? 0 : -1; $("#" + x.getAttribute("aria-controls")).hidden = !on; });
    tabs.forEach((t) => {
      t.addEventListener("click", () => select(t));
      t.addEventListener("keydown", (e) => {
        const i = tabs.indexOf(t);
        const next = e.key === "ArrowRight" ? tabs[(i + 1) % tabs.length] : e.key === "ArrowLeft" ? tabs[(i - 1 + tabs.length) % tabs.length]
          : e.key === "Home" ? tabs[0] : e.key === "End" ? tabs[tabs.length - 1] : null;
        if (next) { e.preventDefault(); next.focus(); select(next); }
      });
    });
  }

  /* ---------- Application for a trial (3 steps) ---------- */
  const DRAFT_KEY = "ennavizion_application_draft";
  // Medical notes and consent are never kept in the draft — only the basic contact details.
  const DRAFT_FIELDS = ["player_first", "player_last", "dob", "position", "current_club", "program", "centre", "guardian_name", "guardian_phone", "guardian_email"];

  function initEnroll() {
    const form = $("#enroll-form"); if (!form) return;
    const status = $("#enroll-status");
    if (!formsOn()) {
      form.remove(); $(".steps-bar")?.remove();
      status.outerHTML = `<div class="notice" role="note"><h2>Online applications aren't open yet</h2><p>To apply now, email ${emailLink()} with the player's name and date of birth, the programme you're interested in, and a parent or guardian's phone number. A parent or guardian must make the application.</p></div>`;
      return;
    }
    addHoneypot(form);
    const box = $("#program-options");
    const pre = new URLSearchParams(location.search).get("program");
    box.innerHTML = PROGRAMS.map((p, i) => `
      <div class="radio-card"><input type="radio" id="prog-${esc(p.id)}" name="program" value="${esc(p.name)}" ${i === 0 ? "required" : ""} ${p.id === pre ? "checked" : ""}>
      <label for="prog-${esc(p.id)}"><strong>${esc(p.name)}</strong>${esc(p.ages)}<br><small>${p.fee ? `${money(p.fee)} ${esc(p.period)}` : "Fees on request"}</small></label></div>`).join("");
    const centreField = $("#centre-select").closest(".field");
    const centres = CENTERS.filter((c) => !isPh(c.name));
    if (centres.length) $("#centre-select").innerHTML = '<option value="">Choose a centre</option>' + centres.map((c) => `<option>${esc(c.name)}</option>`).join("");
    else { centreField.remove(); }
    // Medical notes are only collected where they can be stored encrypted (the backend).
    if (viaNetlify()) {
      const notes = $("#e-notes")?.closest(".field");
      if (notes) notes.outerHTML = '<p class="muted small">If the player has medical needs or injuries, please tell the academy directly when we contact you, rather than in this form.</p>';
    }

    // Age checks: the academy takes players across the programmes' combined age span, and
    // each programme's own band is checked once one is chosen. The server repeats both checks.
    const span = allAges();
    const dob = $("#e-dob");
    const today = new Date();
    if (span) {
      const iso = (d) => d.toISOString().slice(0, 10);
      dob.max = iso(new Date(today.getFullYear() - span[0], today.getMonth(), today.getDate()));
      dob.min = iso(new Date(today.getFullYear() - span[1] - 1, today.getMonth(), today.getDate() + 1));
    }
    dob.closest(".field")._check = (input) => {
      const age = ageOn(input.value);
      if (isNaN(age)) return "Enter a valid date of birth.";
      if (span && (age < span[0] || age > span[1])) return `The academy's programmes are for players aged ${span[0]} to ${span[1]}. This player is ${age}.`;
      return "";
    };
    $("#program-options").closest(".field")._check = () => {
      const chosen = PROGRAMS.find((p) => p.name === (form.elements.program?.value || ""));
      const age = ageOn(dob.value), band = chosen && ageRange(chosen.ages);
      if (band && !isNaN(age) && (age < band[0] || age > band[1])) return `${chosen.name} is for ${chosen.ages.toLowerCase()}. Choose the programme that matches the player's age (${age}).`;
      return "";
    };
    // Make the radio group's check run on change too.
    $$('input[name="program"]', form).forEach((r) => r.addEventListener("change", () => validateField(r.closest(".field"))));

    // Restore a saved draft (this tab only; cleared on submit).
    const draft = store.get(DRAFT_KEY, null, sessionStorage);
    if (draft) DRAFT_FIELDS.forEach((k) => {
      const el = form.elements[k]; if (!el || draft[k] == null) return;
      if (el instanceof RadioNodeList) { const r = $$(`input[name="${k}"]`, form).find((x) => x.value === draft[k]); if (r && !pre) r.checked = true; }
      else el.value = draft[k];
    });
    form.addEventListener("input", () => {
      const d = Object.fromEntries(new FormData(form).entries());
      store.set(DRAFT_KEY, Object.fromEntries(DRAFT_FIELDS.map((k) => [k, d[k] ?? null])), sessionStorage);
    });

    const steps = $$(".form-step", form), bar = $$(".steps-bar li");
    let cur = 0;
    const go = (n, focus = true) => {
      cur = n;
      steps.forEach((s, i) => (s.hidden = i !== n));
      bar.forEach((b, i) => { b.classList.toggle("active", i === n); b.classList.toggle("done", i < n); if (i === n) b.setAttribute("aria-current", "step"); else b.removeAttribute("aria-current"); });
      if (n === steps.length - 1) buildReview();
      if (focus) { $("h2", steps[n])?.focus(); form.scrollIntoView({ behavior: "smooth", block: "start" }); }
    };
    const stepValid = (n) => {
      const ok = $$(".field", steps[n]).map(validateField).every(Boolean);
      if (!ok) $(".field.invalid input, .field.invalid select, .field.invalid textarea", steps[n])?.focus();
      return ok;
    };
    form.addEventListener("click", (e) => {
      if (e.target.closest("[data-next]") && stepValid(cur)) go(cur + 1);
      if (e.target.closest("[data-back]")) go(cur - 1);
    });
    function buildReview() {
      const d = Object.fromEntries(new FormData(form).entries());
      const rows = [
        ["Player", `${d.player_first || ""} ${d.player_last || ""}`],
        ["Date of birth", d.dob ? `${fmtDate(d.dob)} (age ${ageOn(d.dob)})` : ""], ["Position", d.position], ["Current club", d.current_club || "None"],
        ["Programme", d.program], ...(d.centre ? [["Centre", d.centre]] : []),
        ...($("#e-notes") ? [["Medical or other notes", d.medical_notes ? "Provided" : "None"]] : []),
        ["Parent / guardian", d.guardian_name], ["Phone", d.guardian_phone], ["Email", d.guardian_email]
      ];
      $("#review").innerHTML = "<dl>" + rows.map(([k, v]) => `<dt>${k}</dt><dd>${esc(v || "—")}</dd>`).join("") + "</dl>";
    }

    let sending = false;
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (sending) return; // one submission at a time
      if (!stepValid(cur)) return;
      const btn = $('button[type="submit"]', form);
      sending = true; btn.disabled = true; btn.textContent = "Sending…";
      status.className = "form-status"; status.textContent = "";
      try {
        const data = await submitForm(form, "enrolment");
        store.del(DRAFT_KEY, sessionStorage);
        form.hidden = true; $(".steps-bar").hidden = true;
        status.className = "form-status ok";
        status.innerHTML = `<h3>Application received</h3>
          ${data.reference ? `<p>Your reference is <strong class="ref">${esc(data.reference)}</strong>. Keep it for any questions about this application.</p>` : ""}
          <p>Thank you, ${esc(data.guardian_name)}. We've received ${esc(data.player_first)}'s application for ${esc(data.program)}.</p>
          <h4>What happens next</h4>
          <ol><li>The academy reviews the application.</li><li>We contact you on ${esc(data.guardian_phone)} or ${esc(data.guardian_email)} to confirm details.</li><li>If a place suits the player, we arrange a trial session with you.</li></ol>
          <p>Questions? Email ${emailLink()}${data.reference ? " and quote your reference" : ""}.</p>
          <a class="btn btn-green btn-sm" href="programs.html">Back to programmes</a>`;
        status.focus();
      } catch (err) {
        status.className = "form-status err";
        status.textContent = `The application didn't send. ${err.message} Your answers are still here, so you can try again, or email ${SITE.email}.`;
        status.focus();
        btn.disabled = false; btn.textContent = "Send application";
      } finally { sending = false; }
    });
    go(0, false);
  }

  /* ---------- Contributions ("Support a Future Footballer") ---------- */
  // The programme information is always shown. Checkout follows the backend's Paystack state
  // (SITE.donationMode): "off" → information plus another way to give; "test" → working
  // checkout, clearly labelled, no real money; "live" → real payments, only ever switched on
  // deliberately on the server.
  const optionsHTML = () => CONTRIBUTIONS.options.map((o) =>
    `<li><strong>${money(o.amount)}</strong><span>${esc(o.purpose)}</span></li>`).join("");

  function goalsHTML() {
    if (!GOALS || !GOALS.items || !GOALS.items.length) return "";
    return `<div class="impact-grid goals-grid">${GOALS.items.map((g) => `<div class="impact-stat"><strong>${esc(g.value)}</strong><span>${esc(g.label)}</span></div>`).join("")}</div>
      <p class="goals-note">These are targets for ${esc(GOALS.year)}, the academy's first operating year — not results already achieved. Confirmed figures will be published once they have been verified.</p>`;
  }

  function initDonate() {
    const list = $("#contribution-options"); if (list) list.innerHTML = optionsHTML();
    const goals = $("#donate-goals"); if (goals) { const h = goalsHTML(); if (h) goals.innerHTML = h; else dropSection(goals); }
    const form = $("#donate-form"); if (!form) return;
    const status = $("#donate-status");
    const reference = new URLSearchParams(location.search).get("reference");
    const mode = SITE.apiBase ? (SITE.donationMode || (SITE.donations ? "live" : "off")) : "off";

    if (!SITE.apiBase && SITE.paystackPageUrl && /^https:\/\/paystack\.(com|shop)\//.test(SITE.paystackPageUrl)) {
      $("#donate-steps")?.remove();
      form.outerHTML = `<div class="panel"><h2 class="form-title">Give online</h2><p>Contributions are taken securely on Paystack's own payment page. Choose your amount there, and add in the note what you'd like it used for, or "where it's needed most".</p><a class="btn btn-gold" href="${esc(SITE.paystackPageUrl)}" target="_blank" rel="noopener">Give with Paystack</a></div>`;
      return;
    }
    if (mode === "off" && !reference) {
      $("#donate-steps")?.remove();
      form.outerHTML = `<div class="panel"><div class="notice" role="note"><h2>Online contributions aren't open yet</h2><p>Secure online giving opens once the academy's payment account is verified. To support a player now, email ${emailLink()} and we'll explain the options.</p></div></div>`;
      return;
    }
    if (mode === "test") {
      form.insertAdjacentHTML("afterbegin", `<div class="notice notice-test" role="note"><h2>Test mode</h2><p>Checkout uses Paystack's test environment. No real money is taken, and test payments are never counted as contributions.</p></div>`);
    }
    addHoneypot(form);

    $("#amount-options").innerHTML = CONTRIBUTIONS.options.map((o, i) => `
      <div class="radio-card"><input type="radio" id="amt-${o.amount}" name="amount" value="${o.amount}" data-allocation="${esc(o.allocation)}" ${i === 0 ? "required" : ""}>
      <label for="amt-${o.amount}"><strong>${money(o.amount)}</strong><small>${esc(o.purpose)}</small></label></div>`).join("") + `
      <div class="radio-card"><input type="radio" id="amt-custom" name="amount" value="custom" data-allocation="greatest_need">
      <label for="amt-custom"><strong>Other amount</strong><small>You choose</small></label></div>`;
    const alloc = $("#allocation");
    alloc.innerHTML = Object.entries(CONTRIBUTIONS.allocations).map(([k, v]) => `<option value="${esc(k)}">${esc(v)}</option>`).join("");
    const custom = $("#custom-amount");
    if (CONTRIBUTIONS.min) { custom.min = CONTRIBUTIONS.min; $("#custom-amount-label").textContent = `Amount in naira (minimum ${money(CONTRIBUTIONS.min)})`; }

    form.addEventListener("change", (e) => {
      if (e.target.name !== "amount") return;
      const isCustom = e.target.value === "custom";
      $("#custom-amount-field").hidden = !isCustom;
      custom.required = isCustom;
      // Suggest the purpose that goes with the amount; the donor can change it.
      if (e.target.dataset.allocation && alloc.querySelector(`option[value="${e.target.dataset.allocation}"]`)) alloc.value = e.target.dataset.allocation;
    });

    let sending = false;
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (sending) return;
      const allOk = $$(".field", form).map(validateField).every(Boolean);
      if (!allOk) { $(".field.invalid input, .field.invalid select, .field.invalid textarea", form)?.focus(); return; }
      const btn = $('button[type="submit"]', form); const label = btn.textContent;
      sending = true; btn.disabled = true; btn.textContent = "Opening secure checkout…";
      status.className = "form-status"; status.textContent = "";
      try {
        const payload = Object.fromEntries(new FormData(form).entries());
        const res = await fetch(`${SITE.apiBase}/donations/initialize`, {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify(payload)
        });
        const result = await res.json().catch(() => ({}));
        if (!res.ok || !result.authorization_url) {
          const firstError = result.errors ? Object.values(result.errors)[0]?.[0] : null;
          throw new Error(firstError || result.message || "Checkout couldn't be started.");
        }
        location.href = result.authorization_url; // Paystack's hosted page
      } catch (err) {
        status.className = "form-status err";
        status.textContent = `${err.message} No payment was taken. Try again, or email ${SITE.email}.`;
        status.focus();
        sending = false; btn.disabled = false; btn.textContent = label;
      }
    });

    if (reference) checkPaystackReturn(form, status, reference);
  }

  async function checkPaystackReturn(form, status, reference) {
    form.hidden = true;
    status.className = "form-status";
    status.innerHTML = "<p>Checking your payment with Paystack…</p>";
    status.focus();
    try {
      const res = await fetch(`${SITE.apiBase}/donations/verify/${encodeURIComponent(reference)}`, { headers: { Accept: "application/json" } });
      const result = await res.json().catch(() => ({}));
      if (result.status === "success") {
        status.className = "form-status ok";
        status.innerHTML = `<h3>Thank you${result.donor_name ? ", " + esc(result.donor_name) : ""}</h3><p>Your donation of ${money(result.amount)} was received and confirmed by Paystack. Payment reference: <strong class="ref">${esc(reference)}</strong>.</p><a class="btn btn-green btn-sm" href="community.html">See our community work</a>`;
      } else if (result.status === "pending") {
        status.className = "form-status";
        status.innerHTML = `<h3>Payment still processing</h3><p>Paystack hasn't confirmed this payment yet. You don't need to pay again — we'll record it as soon as it's confirmed. Reference: <strong class="ref">${esc(reference)}</strong>.</p>`;
      } else {
        throw new Error(result.message || "This payment wasn't completed.");
      }
    } catch (err) {
      form.hidden = false;
      status.className = "form-status err";
      status.textContent = `${err.message} If money left your account, email ${SITE.email} with the reference ${reference} and we'll check it.`;
    }
    status.focus();
  }

  /* ---------- Scroll reveal ---------- */
  // Adds .reveal/.stagger classes purely at runtime — if this never runs (no JS, or an
  // unsupported browser) nothing is ever hidden, so content always degrades safely.
  function initReveal() {
    if (!("IntersectionObserver" in window)) return;
    if (matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("in-view");
        io.unobserve(entry.target);
      });
    }, { threshold: 0.1, rootMargin: "0px 0px -40px 0px" });
    $$("main > section").forEach((sec) => {
      if (sec.classList.contains("hero") || sec.classList.contains("page-hero")) return;
      const r = sec.getBoundingClientRect();
      if (r.top < innerHeight) return; // already on screen: never hide visible content
      sec.classList.add("reveal");
      io.observe(sec);
    });
  }

  // A photo that fails to load is removed so its green frame shows instead of a broken
  // image icon. Listened for here (capture phase) rather than with inline onerror
  // attributes, which the site's Content-Security-Policy doesn't allow.
  document.addEventListener("error", (e) => {
    const img = e.target;
    if (img instanceof HTMLImageElement && img.closest(".photo, .hero-media")) img.remove();
  }, true);

  /* ---------- Boot ---------- */
  // The script tag sits at the end of <body>, so the page is already parsed: draw the header
  // straight away (CSS reserves its height, so nothing shifts), then fill in content.
  renderHeader();
  initChrome();
  // The rest runs in separate short tasks, yielding to the browser in between, so no single
  // task blocks input for long on a slow phone.
  const yieldToBrowser = () => new Promise((r) => setTimeout(r, 0));
  (async () => {
    await contentReady;
    await yieldToBrowser();
    const page = document.body.dataset.page;
    if (pages[page]) pages[page]();      // the page's own content first
    await yieldToBrowser();
    renderContentChrome();               // footer, cart drawer, WhatsApp button
    initCart();
    initForms();
    await yieldToBrowser();
    initReveal();
  })();
})();
