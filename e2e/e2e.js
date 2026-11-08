// End-to-end checks against the real local API (php artisan serve on :8000).
// Usage: node e2e.js http://localhost:5500   (backend mode)
//        node e2e.js http://127.0.0.2:5500 static   (no-backend / Netlify mode)
const { chromium } = require("playwright");
const assert = require("assert/strict");
const [base, mode = "backend"] = process.argv.slice(2);
const results = [];
const test = async (name, fn) => {
  try { await fn(); results.push(["PASS", name]); } catch (e) { results.push(["FAIL", name, e.message.split("\n")[0]]); }
};
const isoYearsAgo = (y, extraDays = 10) => { const d = new Date(); d.setFullYear(d.getFullYear() - y); d.setDate(d.getDate() - extraDays); return d.toISOString().slice(0, 10); };

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  const errors = [];
  page.on("pageerror", (e) => errors.push(e.message));

  if (mode === "static") {
    await test("static: application form replaced by email alternative, no fake success", async () => {
      await page.goto(`${base}/enroll.html`, { waitUntil: "networkidle" });
      assert.equal(await page.locator("#enroll-form").count(), 0);
      await page.getByText("Online applications aren't open yet").waitFor();
      assert.ok(await page.locator('a[href^="mailto:info@ennavizion.com"]').first().isVisible());
    });
    await test("static: contact form replaced, newsletter hidden, donations closed", async () => {
      await page.goto(`${base}/contact.html`, { waitUntil: "networkidle" });
      assert.equal(await page.locator('form[data-form="contact"]').count(), 0);
      assert.equal(await page.locator('form[data-form="newsletter"]').count(), 0);
      await page.goto(`${base}/donate.html`, { waitUntil: "networkidle" });
      assert.equal(await page.locator("#donate-form").count(), 0);
      await page.getByText("Online donations aren't open yet").waitFor();
    });
    await test("static: old browser-stored submissions are deleted", async () => {
      await page.evaluate(() => localStorage.setItem("ennavizion_submissions", "[{\"player_first\":\"X\"}]"));
      await page.reload({ waitUntil: "networkidle" });
      assert.equal(await page.evaluate(() => localStorage.getItem("ennavizion_submissions")), null);
    });
    await test("static: league shows the exact empty-state messages", async () => {
      await page.goto(`${base}/league.html`, { waitUntil: "networkidle" });
      await page.getByText("The league table will appear once results are published.").waitFor();
      await page.getByRole("tab", { name: "Fixtures" }).click();
      await page.getByText("No upcoming fixtures have been published.").waitFor();
      await page.getByRole("tab", { name: "Results" }).click();
      await page.getByText("Match results will appear here when available.").waitFor();
    });
    await test("static: shop closed and not in navigation; no WhatsApp button with no number", async () => {
      await page.goto(`${base}/index.html`, { waitUntil: "networkidle" });
      assert.equal(await page.locator('#nav-list a[href="shop.html"]').count(), 0);
      assert.equal(await page.locator(".wa-float").count(), 0);
      assert.ok(!(await page.locator(".cart-btn").isVisible()));
      await page.goto(`${base}/shop.html`, { waitUntil: "networkidle" });
      await page.getByText("The academy shop isn't open yet").waitFor();
    });
  } else {
    await test("navigation: every nav link opens a page that exists", async () => {
      await page.goto(`${base}/index.html`, { waitUntil: "networkidle" });
      const hrefs = await page.locator("#nav-list a, .footer-grid a, .footer-legal a").evaluateAll((as) => [...new Set(as.map((a) => a.getAttribute("href")).filter((h) => h && !h.startsWith("mailto") && !h.startsWith("tel") && !h.startsWith("http")))]);
      for (const h of hrefs) {
        const r = await page.request.get(`${base}/${h}`);
        assert.equal(r.status(), 200, `${h} → ${r.status()}`);
      }
      assert.ok(hrefs.length >= 10);
    });

    await test("application: age checks block a 4-year-old and a mismatched programme", async () => {
      await page.goto(`${base}/enroll.html`, { waitUntil: "networkidle" });
      await page.fill("#e-first", "Ada"); await page.fill("#e-last", "Test");
      await page.fill("#e-dob", isoYearsAgo(4)); await page.selectOption("#e-pos", "Midfielder");
      await page.click("[data-next]:visible");
      await page.getByText("programmes are for players aged 6 to 18").waitFor();
      await page.fill("#e-dob", isoYearsAgo(11));
      await page.click("[data-next]:visible");
      await page.locator("#prog-foundation").check({ force: true });
      await page.getByText("Foundation is for ages 6–9").waitFor();
    });

    let reference;
    await test("application: full submission returns a reference and next steps", async () => {
      await page.locator("#prog-development").check({ force: true });
      if (await page.locator("#centre-select").count()) await page.selectOption("#centre-select", { index: 1 });
      await page.fill("#e-notes", "E2E: allergic to peanuts");
      await page.click("[data-next]:visible");
      await page.fill("#g-name", "E2E Guardian"); await page.fill("#g-phone", "+234 801 000 1111"); await page.fill("#g-email", `e2e+${Date.now()}@example.com`);
      await page.check("#g-consent");
      await page.getByText("Check the details").waitFor();
      const btn = page.getByRole("button", { name: "Send application" });
      await btn.dblclick(); // a parent double-clicking must still create one application
      await page.getByText("Application received").waitFor();
      reference = await page.locator("#enroll-status .ref").innerText();
      assert.match(reference, /^ENR-\d{4}-[A-Z0-9]{6}$/);
      await page.getByText("What happens next").first().waitFor();
      assert.equal(await page.evaluate(() => sessionStorage.getItem("ennavizion_application_draft")), null, "draft cleared");
    });

    await test("application: stored once on the server, medical notes not in plain data", async () => {
      const { execSync } = require("child_process");
      const out = execSync(`php artisan tinker --execute "$s=App\\Models\\Submission::where('reference','${reference}')->first(); echo json_encode(['count'=>App\\Models\\Submission::where('fingerprint',$s->fingerprint)->count(),'data_has_notes'=>str_contains(json_encode($s->data),'peanuts'),'sensitive'=>$s->sensitive['medical_notes']??null]);"`, { cwd: process.env.BACKEND_DIR || require("path").join(__dirname, "..", "backend") }).toString();
      const r = JSON.parse(out.trim().split("\n").pop());
      assert.equal(r.count, 1); assert.equal(r.data_has_notes, false); assert.match(r.sensitive, /peanuts/);
    });

    await test("application: server error is shown honestly, not as success", async () => {
      await page.route("**/api/submissions", (route) => route.fulfill({ status: 500, contentType: "application/json", body: "{}" }));
      await page.goto(`${base}/enroll.html`, { waitUntil: "networkidle" });
      await page.fill("#e-first", "Err"); await page.fill("#e-last", "Case"); await page.fill("#e-dob", isoYearsAgo(12)); await page.selectOption("#e-pos", "Forward");
      await page.click("[data-next]:visible");
      await page.locator("#prog-development").check({ force: true });
      if (await page.locator("#centre-select").count()) await page.selectOption("#centre-select", { index: 1 });
      await page.click("[data-next]:visible");
      await page.fill("#g-name", "G"); await page.fill("#g-phone", "08010001111"); await page.fill("#g-email", "g@example.com"); await page.check("#g-consent");
      await page.getByRole("button", { name: "Send application" }).click();
      await page.getByText("The application didn't send").waitFor();
      assert.equal(await page.getByText("Application received").count(), 0);
      assert.ok(await page.locator("#enroll-form").isVisible(), "answers kept");
      await page.unroute("**/api/submissions");
    });

    await test("application: draft survives a reload but never stores medical notes", async () => {
      await page.goto(`${base}/enroll.html`, { waitUntil: "networkidle" });
      await page.fill("#e-first", "Draft"); await page.fill("#e-notes", "secret medical").catch(() => {});
      await page.reload({ waitUntil: "networkidle" });
      assert.equal(await page.inputValue("#e-first"), "Draft");
      const draft = await page.evaluate(() => sessionStorage.getItem("ennavizion_application_draft"));
      assert.ok(!draft.includes("secret medical"));
      await page.evaluate(() => sessionStorage.clear());
    });

    await test("contact: message sends and shows a real confirmation", async () => {
      await page.goto(`${base}/contact.html?topic=league`, { waitUntil: "networkidle" });
      assert.equal(await page.inputValue("#c-topic"), "league");
      await page.fill("#c-name", "E2E Coach"); await page.fill("#c-email", "coach@example.com"); await page.fill("#c-message", "We'd like to enter a team next season.");
      await page.getByRole("button", { name: "Send message" }).click();
      await page.getByText("Message received").waitFor();
    });

    await test("donations: closed while Paystack isn't configured", async () => {
      await page.goto(`${base}/donate.html`, { waitUntil: "networkidle" });
      await page.getByText("Online donations aren't open yet").waitFor();
      assert.equal(await page.locator("#donate-form").count(), 0);
    });

    await test("league: table, results and fixtures render from the API", async () => {
      await page.goto(`${base}/league.html`, { waitUntil: "networkidle" });
      const rows = await page.locator("#league-table tbody tr").count();
      assert.ok(rows > 0, "table rows");
      const txt = await page.locator("main").innerText();
      assert.ok(!/undefined|NaN|null/.test(txt), "no broken values");
      await page.getByRole("tab", { name: "Results" }).click();
      assert.ok(await page.locator("#results .match").count() > 0 || await page.getByText("Match results will appear here").count() > 0);
    });

    await test("mobile menu: closed menu is not tabbable; opens, traps, closes with Escape", async () => {
      const m = await browser.newPage({ viewport: { width: 375, height: 800 } });
      await m.goto(`${base}/index.html`, { waitUntil: "networkidle" });
      assert.equal(await m.evaluate(() => document.getElementById("main-nav").inert), true);
      await m.getByRole("button", { name: "Open menu" }).click();
      assert.equal(await m.evaluate(() => document.activeElement.closest("#main-nav") !== null), true, "focus moves into menu");
      assert.equal(await m.evaluate(() => document.querySelector("main").inert), true, "page behind is inert");
      await m.keyboard.press("Escape");
      assert.equal(await m.getByRole("button", { name: "Open menu" }).getAttribute("aria-expanded"), "false");
      assert.equal(await m.evaluate(() => document.activeElement.classList.contains("menu-toggle")), true, "focus returns to button");
      await m.getByRole("button", { name: "Open menu" }).click();
      await m.locator("#nav-list a", { hasText: "League" }).click();
      await m.waitForURL(/league\.html/);
      assert.equal(await m.locator(".menu-toggle").getAttribute("aria-expanded"), "false");
      await m.close();
    });
  }

  assert.deepEqual(errors, [], "page errors");
  await browser.close();
  for (const r of results) console.log(r.join("  "));
  console.log(`${results.filter((r) => r[0] === "PASS").length}/${results.length} passed`);
  if (errors.length) console.log("page errors:", errors);
})();
