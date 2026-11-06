/* =========================================================
   ENNAVIZION — site behaviour
   Shared header/footer, mobile menu, cart, forms, and the
   content renderers for each page. Content lives in data.js.
   ========================================================= */

(function () {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const money = (n) => SITE.currency + Number(n).toLocaleString("en-NG");
  const fmtDate = (iso, opts = { day: "numeric", month: "short", year: "numeric" }) =>
    new Date(iso + "T00:00:00").toLocaleDateString("en-GB", opts);
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const joinAnd = (arr) => arr.length <= 1 ? (arr[0] || "") : arr.slice(0, -1).join(", ") + " and " + arr[arr.length - 1];

  const store = {
    get(key, fallback) { try { const v = localStorage.getItem(key); return v ? JSON.parse(v) : fallback; } catch { return fallback; } },
    set(key, val) { try { localStorage.setItem(key, JSON.stringify(val)); } catch { /* storage unavailable */ } }
  };

  /* ---------- Content from the backend ---------- */
  // Fetches GET /content (backend ContentController) and overwrites the data.js globals in
  // place, so every renderer below works unchanged whichever source the data came from.
  // Started immediately (not on DOMContentLoaded) so the request overlaps HTML parsing. Any
  // failure — no apiBase, backend down, slow network — leaves data.js's content in use.
  const contentReady = (async function loadContent() {
    if (!SITE.apiBase) return;
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), 4000);
    try {
      const res = await fetch(`${SITE.apiBase}/content`, { headers: { Accept: "application/json" }, signal: ctrl.signal });
      if (!res.ok) throw new Error("Server responded " + res.status);
      const c = await res.json();
      const swap = (arr, next) => { if (Array.isArray(next)) arr.splice(0, arr.length, ...next); };
      if (c.SITE) Object.assign(SITE, c.SITE, { socials: { ...SITE.socials, ...c.SITE.socials } });
      swap(PROGRAMS, c.PROGRAMS); swap(CENTERS, c.CENTERS); swap(IMPACT, c.IMPACT);
      swap(TESTIMONIALS, c.TESTIMONIALS); swap(GALLERY, c.GALLERY); swap(PRODUCTS, c.PRODUCTS); swap(POSTS, c.POSTS);
      if (c.LEAGUE) {
        if (c.LEAGUE.season) LEAGUE.season = c.LEAGUE.season;
        swap(LEAGUE.table, c.LEAGUE.table); swap(LEAGUE.fixtures, c.LEAGUE.fixtures); swap(LEAGUE.results, c.LEAGUE.results);
      }
    } catch (err) {
      console.warn("Using bundled content from data.js — backend unavailable:", err.message);
    } finally { clearTimeout(timer); }
  })();

  /* ---------- Icons ---------- */
  const ICON = {
    // Club crest (cropped from the client's logo artwork). alt is empty because the
    // brand link always carries the site name as text/aria-label alongside it.
    logo: '<img class="brand-logo" src="assets/images/logo.png" alt="" width="480" height="340">',
    cart: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>',
    menu: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>',
    close: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    up: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 14l6-6 6 6"/></svg>',
    wa: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.8-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.4-.3z"/></svg>',
    ig: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 8.2a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4zM17.3 5.5a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4zM12 3.8c2.7 0 3 0 4 .1 2.7.1 4 1.4 4.1 4.1.1 1 .1 1.3.1 4s0 3-.1 4c-.1 2.7-1.4 4-4.1 4.1-1 .1-1.3.1-4 .1s-3 0-4-.1c-2.7-.1-4-1.4-4.1-4.1-.1-1-.1-1.3-.1-4s0-3 .1-4C4 5.3 5.3 4 8 3.9c1-.1 1.3-.1 4-.1zM12 2C9.3 2 8.9 2 7.9 2.1 4.3 2.2 2.2 4.3 2.1 7.9 2 8.9 2 9.3 2 12s0 3.1.1 4.1c.1 3.6 2.2 5.7 5.8 5.8 1 .1 1.4.1 4.1.1s3.1 0 4.1-.1c3.6-.1 5.7-2.2 5.8-5.8.1-1 .1-1.4.1-4.1s0-3.1-.1-4.1c-.1-3.6-2.2-5.7-5.8-5.8C15.1 2 14.7 2 12 2z"/></svg>',
    x: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.8 3h3.1l-6.8 7.7 8 10.3h-6.2l-4.9-6.4L5.4 21H2.3l7.2-8.3L1.8 3h6.4l4.4 5.8L17.8 3zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5z"/></svg>',
    yt: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12 31 31 0 0 0 1 16.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.8 31 31 0 0 0-.5-4.8zM9.7 15V9l5.8 3-5.8 3z"/></svg>',
    fb: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8V6.2c0-.8.2-1.2 1.4-1.2H17V2h-2.6C11.4 2 10.4 3.4 10.4 6v2H8v3h2.4v11H14V11h2.7L17 8h-3z"/></svg>'
  };

  // Primary navigation. Labels are the user-facing names; filenames are left as-is
  // so existing links/bookmarks keep working (e.g. "Academy" still points at programs.html).
  const NAV = [
    ["index.html", "Home"],
    ["about.html", "About"],
    ["programs.html", "Academy"],
    ["league.html", "League"],
    ["blog.html", "News"],
    ["community.html", "Community"],
    ["gallery.html", "Gallery"],
    ["contact.html", "Contact"]
  ];

  /* ---------- Layout: header, footer, cart drawer, floats ---------- */
  function renderLayout() {
    const current = (location.pathname.split("/").pop() || "index.html").toLowerCase();
    const active = current === "post.html" ? "blog.html"
      : current === "enroll.html" ? "programs.html"
      : current === "donate.html" ? "community.html"
      : current;

    const header = document.createElement("header");
    header.className = "site-header";
    header.innerHTML = `
      <div class="wrap header-inner">
        <a class="brand" href="index.html" aria-label="${SITE.name} home">${ICON.logo}<span>${SITE.name}</span></a>
        <nav class="main-nav" id="main-nav" aria-label="Main">
          <ul>
            ${NAV.map(([href, label]) => `<li><a href="${href}"${href === active ? ' aria-current="page"' : ""}>${label}</a></li>`).join("")}
          </ul>
          <a class="btn btn-gold btn-block nav-cta" href="enroll.html">Enrol a player</a>
          <a class="btn btn-line btn-block nav-cta-secondary" href="donate.html">Support us</a>
          <!-- Cart is intentionally not duplicated in the mobile flyout: the header cart
               icon is already reachable there and doesn't need a second entry point. -->
        </nav>
        <div class="header-actions">
          <!-- Cart is deliberately kept even though the shop isn't in primary nav — Ennavizion
               plans a fashion/merch line, so this icon (with its item-count badge, currently
               hidden at 0) is ready to be the entry point once that launches. -->
          <button class="cart-btn" type="button" data-open-cart aria-label="Open shopping cart">${ICON.cart}<span class="cart-count" data-count="0">0</span></button>
          <a class="support-link" href="donate.html">Support us</a>
          <a class="btn btn-gold btn-sm" href="enroll.html">Enrol a player</a>
          <button class="menu-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Open menu">${ICON.menu}</button>
        </div>
      </div>`;
    document.body.prepend(header);

    const skip = document.createElement("a");
    skip.className = "skip-link"; skip.href = "#main"; skip.textContent = "Skip to content";
    document.body.prepend(skip);

    const year = new Date().getFullYear();
    const footer = document.createElement("footer");
    footer.className = "site-footer";
    footer.innerHTML = `
      <div class="wrap">
        <div class="footer-grid">
          <div>
            <a class="brand" href="index.html">${ICON.logo}<span>${SITE.name}</span></a>
            <p>${SITE.tagline} A youth football academy developing players, people and pathways into the professional game.</p>
            <div class="socials">
              <a href="${SITE.socials.instagram}" aria-label="Instagram" target="_blank" rel="noopener">${ICON.ig}</a>
              <a href="${SITE.socials.x}" aria-label="X" target="_blank" rel="noopener">${ICON.x}</a>
              <a href="${SITE.socials.youtube}" aria-label="YouTube" target="_blank" rel="noopener">${ICON.yt}</a>
              <a href="${SITE.socials.facebook}" aria-label="Facebook" target="_blank" rel="noopener">${ICON.fb}</a>
            </div>
          </div>
          <div>
            <h4>Academy</h4>
            <ul>
              <li><a href="about.html">About us</a></li>
              <li><a href="programs.html">Academy programs</a></li>
              <li><a href="league.html">League</a></li>
              <li><a href="blog.html">News</a></li>
              <li><a href="gallery.html">Gallery</a></li>
            </ul>
          </div>
          <div>
            <h4>Get involved</h4>
            <ul>
              <li><a href="enroll.html">Enrol a player</a></li>
              <li><a href="donate.html">Support a player</a></li>
              <li><a href="contact.html?topic=partner">Partner with us</a></li>
              <li><a href="contact.html?topic=scout">Scouting requests</a></li>
              <li><a href="shop.html">Shop</a></li>
            </ul>
          </div>
          <div>
            <h4>Match-day updates</h4>
            <p>Fixtures, results and trial dates, once a month.</p>
            <form class="newsletter" data-form="newsletter" novalidate>
              <label class="visually-hidden" for="nl-email">Email address</label>
              <input id="nl-email" type="email" name="email" placeholder="you@example.com" required autocomplete="email">
              <button class="btn btn-gold btn-sm" type="submit">Subscribe</button>
            </form>
          </div>
        </div>
        <div class="footer-base">
          <span>© ${year} ${SITE.name}. All rights reserved.</span>
          <nav class="footer-legal" aria-label="Legal and community">
            <a href="safeguarding.html">Safeguarding</a>
            <a href="privacy.html">Privacy Policy</a>
            <a href="terms.html">Terms</a>
            <a href="community.html">Community</a>
            <a href="contact.html">Contact</a>
          </nav>
          <span><a href="mailto:${SITE.email}">${SITE.email}</a> &nbsp;|&nbsp; <a href="tel:${SITE.phone.replace(/\s/g, "")}">${SITE.phone}</a></span>
        </div>
      </div>`;
    document.body.appendChild(footer);

    document.body.insertAdjacentHTML("beforeend", `
      <div class="drawer-backdrop" data-close-cart></div>
      <aside class="cart-drawer" id="cart-drawer" aria-label="Shopping cart" aria-hidden="true">
        <div class="cart-head"><h2>Your cart</h2><button class="icon-btn" type="button" data-close-cart aria-label="Close cart">${ICON.close}</button></div>
        <div class="cart-items" id="cart-items"></div>
        <div class="cart-foot">
          <div class="cart-total"><span>Subtotal</span><span id="cart-total">${money(0)}</span></div>
          <button class="btn btn-gold btn-block" type="button" id="checkout-btn">Send order on WhatsApp</button>
          <button class="btn-link-block" type="button" data-close-cart>Continue shopping</button>
          <p class="muted" style="font-size:.9rem;margin:.75rem 0 0">We confirm sizes, delivery and payment with you directly.</p>
        </div>
      </aside>
      <button class="to-top" type="button" aria-label="Back to top">${ICON.up}</button>
      <a class="wa-float" href="https://wa.me/${SITE.whatsapp}?text=${encodeURIComponent("Hello, I'd like to learn more about Ennavizion Football Academy.")}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">${ICON.wa}</a>
      <div class="toast" role="status" aria-live="polite"></div>`);
  }

  function toast(msg) {
    const t = $(".toast");
    t.textContent = msg; t.classList.add("show");
    clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove("show"), 2600);
  }

  /* ---------- Mobile menu + back to top ---------- */
  function initChrome() {
    const btn = $(".menu-toggle"), nav = $("#main-nav");
    const setOpen = (open) => {
      nav.classList.toggle("open", open);
      btn.setAttribute("aria-expanded", open);
      btn.setAttribute("aria-label", open ? "Close menu" : "Open menu");
      btn.innerHTML = open ? ICON.close : ICON.menu;
      document.body.style.overflow = open ? "hidden" : "";
    };
    btn.addEventListener("click", () => setOpen(!nav.classList.contains("open")));
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && nav.classList.contains("open")) { setOpen(false); btn.focus(); }
    });
    const top = $(".to-top");
    window.addEventListener("scroll", () => top.classList.toggle("show", scrollY > 600), { passive: true });
    top.addEventListener("click", () => scrollTo({ top: 0, behavior: "smooth" }));
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
    if (!cart.items.length) {
      box.innerHTML = '<p class="cart-empty">Your cart is empty. Kits, training wear and equipment are in the <a href="shop.html">shop</a>.</p>';
    } else {
      box.innerHTML = cart.items.map((i) => {
        const p = PRODUCTS.find((x) => x.id === i.id); if (!p) return "";
        return `<div class="cart-item">
          <div><strong>${esc(p.name)}</strong><small>${i.size ? "Size " + esc(i.size) + " — " : ""}${money(p.price)} each</small>
            <div><span class="qty"><button type="button" data-qty="-1" data-key="${esc(i.key)}" aria-label="Decrease quantity">−</button><span>${i.qty}</span><button type="button" data-qty="1" data-key="${esc(i.key)}" aria-label="Increase quantity">+</button></span><button class="remove-link" type="button" data-remove="${esc(i.key)}">Remove</button></div>
          </div>
          <strong>${money(p.price * i.qty)}</strong>
        </div>`;
      }).join("");
    }
    $("#cart-total").textContent = money(cart.total());
    $("#checkout-btn").disabled = !cart.items.length;
  }

  function initCart() {
    // Drop saved items whose product no longer exists (removed in the admin, or saved under
    // data.js's ids before the backend's SKU-based ids took over).
    const known = cart.items.filter((i) => PRODUCTS.some((p) => p.id === i.id));
    if (known.length !== cart.items.length) { cart.items = known; store.set(CART_KEY, known); }
    const drawer = $("#cart-drawer"), backdrop = $(".drawer-backdrop");
    const open = () => { drawer.classList.add("open"); backdrop.classList.add("open"); drawer.setAttribute("aria-hidden", "false"); $(".icon-btn", drawer).focus(); };
    const close = () => { drawer.classList.remove("open"); backdrop.classList.remove("open"); drawer.setAttribute("aria-hidden", "true"); };
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
        return `• ${i.qty} × ${p.name}${i.size ? " (size " + i.size + ")" : ""} — ${money(p.price * i.qty)}`;
      });
      const msg = `Hello ${SITE.name}, I'd like to order:\n${lines.join("\n")}\nTotal: ${money(cart.total())}`;
      window.open(`https://wa.me/${SITE.whatsapp}?text=${encodeURIComponent(msg)}`, "_blank", "noopener");
    });
    renderCart();
    window.__openCart = open;
  }

  /* ---------- Forms ---------- */
  function validateField(field) {
    const input = $("input, select, textarea", field); if (!input) return true;
    let ok = true;
    if (input.type === "radio") ok = !input.required || $$(`input[name="${input.name}"]`, field).some((r) => r.checked);
    else if (input.type === "checkbox") ok = !input.required || input.checked;
    else ok = input.checkValidity();
    field.classList.toggle("invalid", !ok);
    return ok;
  }

  async function submitForm(form, type) {
    const data = Object.fromEntries(new FormData(form).entries());
    if (SITE.apiBase) {
      // Stored as a Submission in the backend — see the admin panel's Submissions page.
      const res = await fetch(`${SITE.apiBase}/submissions`, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify({ type, ...data }) });
      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error(res.status === 429 ? "too many attempts, wait a minute" : body.message || "Server responded " + res.status);
      }
      return data;
    }
    data._type = type; data._submitted = new Date().toISOString();
    if (SITE.formEndpoint) {
      const res = await fetch(SITE.formEndpoint, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify(data) });
      if (!res.ok) throw new Error("Server responded " + res.status);
    } else {
      // Demo mode: keep submissions in this browser. See admin.html.
      const all = store.get("ennavizion_submissions", []); all.push(data); store.set("ennavizion_submissions", all);
      await new Promise((r) => setTimeout(r, 400));
    }
    return data;
  }

  function initForms() {
    $$("form[data-form]").forEach((form) => {
      const type = form.dataset.form;
      $$(".field", form).forEach((f) => f.addEventListener("change", () => f.classList.contains("invalid") && validateField(f)));
      if (type === "enroll" || type === "donate") return; // handled by initEnroll / initDonate
      form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const status = $(".form-status", form);
        const fields = $$(".field", form);
        const allOk = fields.map(validateField).every(Boolean);
        if (type === "newsletter") {
          const input = $("input", form);
          if (!input.checkValidity()) { toast("Enter a valid email address to subscribe."); input.focus(); return; }
        } else if (!allOk) { $(".field.invalid input, .field.invalid select, .field.invalid textarea", form)?.focus(); return; }
        const btn = $('button[type="submit"]', form); const label = btn.textContent;
        btn.disabled = true; btn.textContent = "Sending…";
        try {
          await submitForm(form, type);
          if (type === "newsletter") toast("Subscribed. Watch your inbox for match-day updates.");
          else if (status) { status.className = "form-status ok"; status.textContent = "Message sent. We reply within one working day."; }
          form.reset();
        } catch (err) {
          if (status) { status.className = "form-status err"; status.textContent = `Your message didn't send (${err.message}). Check your connection and try again, or email ${SITE.email}.`; }
          else toast("Subscription failed. Try again in a moment.");
        } finally { btn.disabled = false; btn.textContent = label; }
      });
    });
  }

  /* ---------- Shared bits used on several pages ---------- */
  function photo(src, label, extraClass = "") {
    return `<div class="photo ${extraClass}">${src ? `<img src="${esc(src)}" alt="${esc(label)}" loading="lazy" onerror="this.remove()">` : ""}<span class="ph-label">${src ? "" : esc(label)}</span></div>`;
  }

  function leagueRows(limit) {
    // The backend appends an explicit "is Ennavizion" flag; data.js rows fall back to the name.
    const rows = LEAGUE.table.map(([team, p, w, d, l, gf, ga, us]) => ({ team, p, w, d, l, gf, ga, gd: gf - ga, pts: w * 3 + d, us: us ?? team.startsWith(SITE.name) }))
      .sort((a, b) => b.pts - a.pts || b.gd - a.gd || b.gf - a.gf);
    return (limit ? rows.slice(0, limit) : rows).map((r, i) => `
      <tr class="${r.us ? "us" : ""}">
        <td class="pos">${i + 1}</td><td class="team">${esc(r.team)}</td>
        <td>${r.p}</td><td>${r.w}</td><td>${r.d}</td><td>${r.l}</td><td>${r.gd > 0 ? "+" : ""}${r.gd}</td><td class="pts">${r.pts}</td>
      </tr>`).join("");
  }
  const TABLE_HEAD = '<thead><tr><th class="pos" scope="col"><span class="visually-hidden">Position</span></th><th class="team" scope="col">Team</th><th scope="col" title="Played">P</th><th scope="col" title="Won">W</th><th scope="col" title="Drawn">D</th><th scope="col" title="Lost">L</th><th scope="col" title="Goal difference">GD</th><th scope="col" title="Points">Pts</th></tr></thead>';

  function matchHTML(m, played) {
    const when = played ? fmtDate(m.date, { day: "numeric", month: "short" }) : `${fmtDate(m.date, { weekday: "short", day: "numeric", month: "short" })}<br>${m.time}, ${esc(m.venue)}`;
    return `<li class="match"><span class="when">${when}</span><span class="home">${esc(m.home)}</span>${played ? `<span class="score">${m.hs} – ${m.as}</span>` : '<span class="vs">vs</span>'}<span class="away">${esc(m.away)}</span></li>`;
  }

  function postCard(p) {
    return `<a class="post-card" href="post.html?id=${encodeURIComponent(p.id)}">
      ${photo(p.image || "", p.imageAlt || p.category)}
      <div class="post-meta">${fmtDate(p.date)} | ${esc(p.category)} | ${p.minutes} min read</div>
      <h3>${esc(p.title)}</h3><p>${esc(p.excerpt)}</p></a>`;
  }

  // Renders IMPACT (js/data.js) as a strip of placeholder stat tiles. Every value is an
  // "XX+"-style placeholder, not a real figure — replace with confirmed numbers before launch.
  function impactHTML() {
    return IMPACT.map((s) => `<div class="impact-stat"><strong>${esc(s.value)}</strong><span>${esc(s.label)}</span></div>`).join("");
  }

  // Placeholder testimonial card. TESTIMONIALS lives in js/data.js and is flagged there as
  // unverified — the photo is an Unsplash stand-in for presentation only, not a photo of the
  // actual person quoted; do not treat either as confirmation the story is real.
  function testimonialHTML(t) {
    return `<figure class="wrap quote">
      ${photo(t.image || "", t.role, "avatar")}
      <blockquote>${esc(t.quote)}</blockquote>
      <figcaption><strong>${esc(t.name)}</strong><span>${esc(t.role)}</span></figcaption>
      <a class="btn btn-line mt-2" href="blog.html">Read player stories</a>
    </figure>`;
  }

  /* ---------- Page renderers ---------- */
  const pages = {
    home() {
      const t = $("#home-table"); if (t) t.innerHTML = TABLE_HEAD + "<tbody>" + leagueRows(5) + "</tbody>";
      const f = $("#home-fixtures"); if (f) f.innerHTML = LEAGUE.fixtures.slice(0, 3).map((m) => matchHTML(m)).join("");
      const b = $("#home-posts"); if (b) b.innerHTML = POSTS.slice(0, 3).map(postCard).join("");
      const s = $("#home-season"); if (s) s.textContent = LEAGUE.season;
      const im = $("#home-impact"); if (im) im.innerHTML = impactHTML();
      const tm = $("#home-testimonial"); if (tm) { if (TESTIMONIALS.length) tm.outerHTML = testimonialHTML(TESTIMONIALS[0]); else tm.remove(); }
      // Derived from CENTERS/PROGRAMS (js/data.js) so the hero strip never drifts out of
      // sync with the actual centre count/cities or program count.
      const hc = $("#stat-centres"); if (hc) hc.textContent = CENTERS.length + " centres";
      const hcity = $("#stat-cities"); if (hcity) hcity.textContent = joinAnd(CENTERS.map((c) => c.name.split("—")[0].trim()));
      const hp = $("#stat-programs"); if (hp) hp.textContent = PROGRAMS.length;
    },

    about() {
      const el = $("#about-centre-count"); if (el) el.textContent = CENTERS.length;
    },

    community() {
      const im = $("#community-impact"); if (im) im.innerHTML = impactHTML();
    },

    donate() { initDonate(); },

    programs() {
      const list = $("#program-list");
      if (list) list.innerHTML = PROGRAMS.map((p) => `
        <div class="program">
          <div><h3>${esc(p.name)}</h3><span class="ages">${esc(p.ages)}</span></div>
          <p>${esc(p.text)}</p>
          <div class="fee"><strong>${money(p.fee)}</strong><small>${esc(p.period)}</small><div class="mt-2" style="margin-top:.6rem"><a class="btn btn-green btn-sm" href="enroll.html?program=${p.id}">Enrol</a></div></div>
        </div>`).join("");
      const c = $("#centers");
      if (c) c.innerHTML = CENTERS.map((x) => `<div class="panel"><h3>${esc(x.name)}</h3><p class="muted">${esc(x.pitch)}</p><p><strong>Training days:</strong> ${esc(x.days)}</p><a href="contact.html?topic=visit&center=${encodeURIComponent(x.name)}">Book a visit</a></div>`).join("");
    },

    league() {
      $("#season-name").textContent = LEAGUE.season;
      const tc = $("#team-count"); if (tc) tc.textContent = LEAGUE.table.length;
      $("#league-table").innerHTML = TABLE_HEAD + "<tbody>" + leagueRows() + "</tbody>";
      $("#fixtures").innerHTML = LEAGUE.fixtures.map((m) => matchHTML(m)).join("") || '<li class="muted">New fixtures are published every Monday.</li>';
      $("#results").innerHTML = LEAGUE.results.map((m) => matchHTML(m, true)).join("");
      initTabs($("#league-tabs"));
    },

    gallery() {
      const grid = $("#gallery-grid");
      let shown = GALLERY.map((g, i) => ({ ...g, i }));
      const draw = () => {
        grid.innerHTML = shown.map((g) => `<figure style="margin:0"><button type="button" data-lb="${g.i}" aria-label="View larger: ${esc(g.caption)}">${photo(g.src, g.caption)}</button><figcaption>${esc(g.caption)}</figcaption></figure>`).join("");
      };
      draw();
      $$("[data-filter]").forEach((b) => b.addEventListener("click", () => {
        $$("[data-filter]").forEach((x) => x.setAttribute("aria-pressed", x === b));
        const cat = b.dataset.filter;
        shown = GALLERY.map((g, i) => ({ ...g, i })).filter((g) => cat === "all" || g.category === cat);
        draw();
      }));
      // Lightbox
      const lb = $("#lightbox"); let idx = 0; let lastFocus;
      const visible = () => shown.map((g) => g.i);
      const show = (i) => { idx = i; const g = GALLERY[i]; $(".lb-photo", lb).innerHTML = photo(g.src, g.caption); $("p", lb).textContent = g.caption; };
      const step = (d) => { const v = visible(); const pos = v.indexOf(idx); show(v[(pos + d + v.length) % v.length]); };
      grid.addEventListener("click", (e) => { const b = e.target.closest("[data-lb]"); if (!b) return; lastFocus = b; show(Number(b.dataset.lb)); lb.classList.add("open"); $(".lightbox-close", lb).focus(); });
      const close = () => { lb.classList.remove("open"); lastFocus?.focus(); };
      $(".lightbox-close", lb).addEventListener("click", close);
      $(".lightbox-prev", lb).addEventListener("click", () => step(-1));
      $(".lightbox-next", lb).addEventListener("click", () => step(1));
      lb.addEventListener("click", (e) => { if (e.target === lb) close(); });
      document.addEventListener("keydown", (e) => {
        if (!lb.classList.contains("open")) return;
        if (e.key === "Escape") close(); if (e.key === "ArrowLeft") step(-1); if (e.key === "ArrowRight") step(1);
      });
    },

    blog() {
      const grid = $("#post-grid"), search = $("#post-search");
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
      if (!p) { $("#post-title").textContent = "Article not found"; $("#post-body").innerHTML = '<p>This article may have moved. <a href="blog.html">See all news</a>.</p>'; return; }
      document.title = `${p.title} | ${SITE.name}`;
      $("#post-title").textContent = p.title;
      $("#post-meta").textContent = `${fmtDate(p.date)} | ${p.category} | ${p.minutes} min read`;
      // bodyHtml comes from the admin panel's rich-text editor (trusted, admin-written HTML);
      // data.js posts are plain paragraphs and get escaped.
      $("#post-body").innerHTML = p.bodyHtml || p.body.map((para) => `<p>${esc(para)}</p>`).join("");
      $("#post-more").innerHTML = POSTS.filter((x) => x.id !== p.id).slice(0, 3).map(postCard).join("");
    },

    shop() {
      const grid = $("#product-grid");
      const tags = ["All", ...new Set(PRODUCTS.map((p) => p.tag))];
      $("#shop-filters").innerHTML = tags.map((t) => `<button class="tab" type="button" data-tag="${esc(t)}" aria-pressed="${t === "All"}">${esc(t)}</button>`).join("");
      const sized = (p) => p.tag === "Kit" || p.tag === "Training";
      // "stock" is a placeholder field (js/data.js) — it only ever renders this honest,
      // generic label, never a fake countdown or urgency claim.
      const draw = (tag) => {
        grid.innerHTML = PRODUCTS.filter((p) => tag === "All" || p.tag === tag).map((p) => `
          <article class="product">
            ${photo(p.image || "", p.name)}
            <div class="product-body">
              <div class="product-body-top">
                <span class="tag">${esc(p.tag)}</span>
                <span class="stock-status ${p.stock ? "in" : "out"}">${p.stock ? "Available to order" : "Currently unavailable"}</span>
              </div>
              <h3>${esc(p.name)}</h3>
              ${p.colors ? `<p class="muted product-meta">Colour: ${esc(p.colors.join(", "))}</p>` : ""}
              ${sized(p) ? `<label class="visually-hidden" for="size-${p.id}">Size for ${esc(p.name)}</label><select id="size-${p.id}" ${p.stock ? "" : "disabled"}><option value="">Choose size</option>${["Youth S", "Youth M", "Youth L", "S", "M", "L", "XL"].map((s) => `<option>${s}</option>`).join("")}</select>` : ""}
              <div class="price">${money(p.price)}</div>
              <button class="btn btn-green btn-block btn-sm" type="button" data-add="${p.id}" ${p.stock ? "" : "disabled"}>${p.stock ? "Add to cart" : "Unavailable"}</button>
              <p class="muted product-sku">SKU: ${esc(p.sku || p.id)}</p>
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
        const sel = $(`#size-${p.id}`);
        if (sel && !sel.value) { toast("Choose a size first."); sel.focus(); return; }
        cart.add(p.id, sel ? sel.value : "");
        toast(`Added ${p.name} to your cart.`);
      });
    },

    contact() {
      const params = new URLSearchParams(location.search);
      const topic = params.get("topic"); const center = params.get("center");
      if (topic && $("#c-topic")) $("#c-topic").value = topic;
      if (center && $("#c-message")) $("#c-message").value = `I'd like to visit the ${center} centre.`;
      $$("[data-site]").forEach((el) => { el.textContent = SITE[el.dataset.site]; });
      $$("[data-href]").forEach((el) => {
        const k = el.dataset.href;
        el.href = k === "email" ? "mailto:" + SITE.email : k === "phone" ? "tel:" + SITE.phone.replace(/\s/g, "") : "https://wa.me/" + SITE.whatsapp;
      });
    },

    enroll() { initEnroll(); }
  };

  function initTabs(root) {
    if (!root) return;
    const tabs = $$('[role="tab"]', root);
    tabs.forEach((t) => {
      t.addEventListener("click", () => {
        tabs.forEach((x) => { const on = x === t; x.setAttribute("aria-selected", on); x.tabIndex = on ? 0 : -1; $("#" + x.getAttribute("aria-controls")).hidden = !on; });
      });
      t.addEventListener("keydown", (e) => {
        const i = tabs.indexOf(t);
        if (e.key === "ArrowRight") { tabs[(i + 1) % tabs.length].focus(); tabs[(i + 1) % tabs.length].click(); }
        if (e.key === "ArrowLeft") { tabs[(i - 1 + tabs.length) % tabs.length].focus(); tabs[(i - 1 + tabs.length) % tabs.length].click(); }
      });
    });
  }

  /* ---------- Enrolment (3 steps) ---------- */
  function initEnroll() {
    const form = $("#enroll-form"); if (!form) return;
    const box = $("#program-options");
    const pre = new URLSearchParams(location.search).get("program");
    box.innerHTML = PROGRAMS.map((p, i) => `
      <div class="radio-card"><input type="radio" id="prog-${p.id}" name="program" value="${esc(p.name)}" ${i === 0 ? "required" : ""} ${p.id === pre ? "checked" : ""}>
      <label for="prog-${p.id}"><strong>${esc(p.name)}</strong>${esc(p.ages)}<br><small>${money(p.fee)} ${esc(p.period)}</small></label></div>`).join("");
    $("#centre-select").innerHTML = '<option value="">Choose a centre</option>' + CENTERS.map((c) => `<option>${esc(c.name)}</option>`).join("");

    const steps = $$(".form-step", form), bar = $$(".steps-bar li");
    let cur = 0;
    const go = (n) => {
      cur = n;
      steps.forEach((s, i) => (s.hidden = i !== n));
      bar.forEach((b, i) => { b.classList.toggle("active", i === n); b.classList.toggle("done", i < n); });
      if (n === steps.length - 1) buildReview();
      $("h2", steps[n])?.focus();
      form.scrollIntoView({ behavior: "smooth", block: "start" });
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
        ["Date of birth", d.dob ? fmtDate(d.dob) : ""], ["Position", d.position], ["Current club", d.current_club || "None"],
        ["Program", d.program], ["Centre", d.centre],
        ["Parent / guardian", d.guardian_name], ["Phone", d.guardian_phone], ["Email", d.guardian_email]
      ];
      $("#review").innerHTML = "<dl>" + rows.map(([k, v]) => `<dt>${k}</dt><dd>${esc(v || "—")}</dd>`).join("") + "</dl>";
    }

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!stepValid(cur)) return;
      const btn = $('button[type="submit"]', form); const status = $("#enroll-status");
      btn.disabled = true; btn.textContent = "Sending…";
      try {
        const data = await submitForm(form, "enrolment");
        form.hidden = true; $(".steps-bar").hidden = true;
        status.className = "form-status ok";
        status.innerHTML = `<h3 style="margin-top:0">Application sent</h3><p>Thank you, ${esc(data.guardian_name)}. We've received ${esc(data.player_first)}'s application for ${esc(data.program)}. We'll call ${esc(data.guardian_phone)} within three working days to book a trial session.</p><a class="btn btn-green btn-sm" href="index.html">Back to home</a>`;
        status.focus();
      } catch (err) {
        status.className = "form-status err";
        status.textContent = `The application didn't send (${err.message}). Your answers are still here — try again, or call ${SITE.phone}.`;
      } finally { btn.disabled = false; btn.textContent = "Send application"; }
    });
    go(0);
  }

  /* ---------- Donate ---------- */
  // Paystack is now live (backend/app/Services/PaystackService.php): submitting this form
  // starts a hosted-checkout transaction server-side and redirects the browser there — no
  // card data or secret keys ever touch this file. When the donor comes back, the
  // ?reference=xxx Paystack appends gets verified server-side before anything is shown as
  // paid. Until PAYSTACK_SECRET_KEY is set in the backend's .env, the API honestly rejects
  // the request instead of pretending it worked — see the catch block below.
  function initDonate() {
    const form = $("#donate-form"); if (!form) return;
    const status = $("#donate-status");
    const box = $("#amount-options");
    box.innerHTML = DONATION_AMOUNTS.map((n, i) => `
      <div class="radio-card"><input type="radio" id="amt-${n}" name="amount" value="${n}" ${i === 0 ? "required" : ""}>
      <label for="amt-${n}"><strong>${money(n)}</strong></label></div>`).join("") + `
      <div class="radio-card"><input type="radio" id="amt-custom" name="amount" value="custom">
      <label for="amt-custom"><strong>Custom amount</strong></label></div>`;
    const customField = $("#custom-amount-field");
    form.addEventListener("change", (e) => {
      if (e.target.name !== "amount") return;
      const isCustom = e.target.value === "custom";
      customField.hidden = !isCustom;
      $("#custom-amount").required = isCustom;
    });
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const allOk = $$(".field", form).map(validateField).every(Boolean);
      if (!allOk) { $(".field.invalid input, .field.invalid select, .field.invalid textarea", form)?.focus(); return; }
      const btn = $('button[type="submit"]', form); const label = btn.textContent;
      btn.disabled = true; btn.textContent = "Redirecting to secure checkout…";
      status.className = "form-status"; status.textContent = "";
      try {
        if (!SITE.apiBase) throw new Error("Online donations aren't open yet.");
        const payload = Object.fromEntries(new FormData(form).entries());
        const res = await fetch(`${SITE.apiBase}/donations/initialize`, {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify(payload)
        });
        const result = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(result.message || "Could not start checkout.");
        location.href = result.authorization_url; // leaves the site for Paystack's hosted page
      } catch (err) {
        status.className = "form-status err";
        status.textContent = `${err.message} Try again, or email ${SITE.email}.`;
        status.focus();
        btn.disabled = false; btn.textContent = label;
      }
    });

    checkPaystackReturn(form, status);
  }

  async function checkPaystackReturn(form, status) {
    const reference = new URLSearchParams(location.search).get("reference");
    if (!reference) return;
    form.hidden = true;
    status.className = "form-status";
    status.innerHTML = "<p>Verifying your payment…</p>";
    status.focus();
    try {
      const res = await fetch(`${SITE.apiBase}/donations/verify/${encodeURIComponent(reference)}`, { headers: { Accept: "application/json" } });
      const result = await res.json().catch(() => ({}));
      if (!res.ok || result.status !== "success") throw new Error(result.message || "We couldn't confirm this payment.");
      status.className = "form-status ok";
      status.innerHTML = `<h3 style="margin-top:0">Thank you, ${esc(result.donor_name || "friend")}.</h3><p>Your donation of ${money(result.amount)} was received. A confirmation has been recorded.</p><a class="btn btn-green btn-sm" href="index.html">Back to home</a>`;
    } catch (err) {
      form.hidden = false;
      status.className = "form-status err";
      status.textContent = `${err.message} If you were charged, email ${SITE.email} with your payment reference so we can confirm it manually.`;
    }
    status.focus();
  }

  /* ---------- Scroll reveal ---------- */
  // Adds .reveal/.stagger classes purely at runtime — if this never runs (no JS, or an
  // unsupported browser) nothing is ever hidden, so content always degrades safely.
  // Run last, after page-specific rendering, so grids are already populated when queried.
  function initReveal() {
    if (!("IntersectionObserver" in window)) return;
    if (matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("in-view");
        io.unobserve(entry.target);
      });
    }, { threshold: 0.15, rootMargin: "0px 0px -60px 0px" });
    $$("main > section").forEach((sec) => {
      if (sec.classList.contains("hero")) return; // hero has its own load-in sequence, not scroll reveal
      sec.classList.add("reveal");
      io.observe(sec);
      const grid = $(".grid, .gallery, .values, .program-list, .pathway, .impact-grid", sec);
      if (grid) grid.classList.add("stagger");
    });
  }

  /* ---------- Boot ---------- */
  document.addEventListener("DOMContentLoaded", async () => {
    await contentReady;
    renderLayout();
    initChrome();
    initCart();
    initForms();
    const page = document.body.dataset.page;
    if (pages[page]) pages[page]();
    initReveal();
  });
})();
