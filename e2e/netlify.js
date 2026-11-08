// Netlify Forms mode. Maps *.netlify.app to the local server so the site behaves exactly as
// on Netlify, and intercepts the form POST to check what Netlify would receive.
// Usage: node netlify.js 5500
const { chromium } = require("playwright");
const assert = require("assert/strict");
const port = process.argv[2] || "5500";
const base = `http://e2e-test.netlify.app:${port}`;
const isoYearsAgo = (y) => { const d = new Date(); d.setFullYear(d.getFullYear() - y); d.setDate(d.getDate() - 10); return d.toISOString().slice(0, 10); };
const results = [];
const test = async (name, fn) => { try { await fn(); results.push(["PASS", name]); } catch (e) { results.push(["FAIL", name, e.message.split("\n")[0]]); } };

(async () => {
  const browser = await chromium.launch({ args: ["--host-resolver-rules=MAP *.netlify.app 127.0.0.1"] });
  const page = await browser.newPage();
  const posts = [];
  await page.route(`${base}/`, (route) => {
    if (route.request().method() !== "POST") return route.continue();
    posts.push(Object.fromEntries(new URLSearchParams(route.request().postData())));
    route.fulfill({ status: 200, body: "ok" });
  });

  await test("application goes to Netlify Forms with a reference and no medical notes", async () => {
    await page.goto(`${base}/enroll.html`, { waitUntil: "networkidle" });
    assert.equal(await page.locator("#e-notes").count(), 0, "medical notes field removed on Netlify");
    await page.fill("#e-first", "Net"); await page.fill("#e-last", "Lify"); await page.fill("#e-dob", isoYearsAgo(11)); await page.selectOption("#e-pos", "Defender");
    await page.click("[data-next]:visible");
    await page.locator("#prog-development").check({ force: true });
    await page.selectOption("#centre-select", "Jos training ground");
    await page.click("[data-next]:visible");
    await page.fill("#g-name", "Guardian"); await page.fill("#g-phone", "08010001111"); await page.fill("#g-email", "g@example.com"); await page.check("#g-consent");
    await page.getByRole("button", { name: "Send application" }).click();
    await page.getByText("Application received").waitFor();
    const p = posts.find((x) => x["form-name"] === "enrolment");
    assert.ok(p, "posted to Netlify");
    assert.match(p.reference, /^ENR-/);
    assert.equal(p.player_first, "Net"); assert.equal(p.program, "Development"); assert.equal(p.centre, "Jos training ground");
    assert.ok(!("medical_notes" in p));
  });

  await test("contact and newsletter go to Netlify Forms", async () => {
    await page.goto(`${base}/contact.html?topic=sponsor`, { waitUntil: "networkidle" });
    assert.equal(await page.inputValue("#c-topic"), "sponsor");
    await page.fill("#c-name", "Sponsor"); await page.fill("#c-email", "s@example.com"); await page.fill("#c-message", "We'd like to sponsor kit.");
    await page.getByRole("button", { name: "Send message" }).click();
    await page.getByText("Message received").waitFor();
    await page.fill("#nl-email", "fan@example.com");
    await page.getByRole("button", { name: "Subscribe" }).click();
    await page.getByText("Subscribed").waitFor();
    assert.ok(posts.some((x) => x["form-name"] === "contact" && x.topic === "sponsor"));
    assert.ok(posts.some((x) => x["form-name"] === "newsletter" && x.email === "fan@example.com"));
  });

  await test("a failed delivery is shown as a failure", async () => {
    await page.unroute(`${base}/`);
    await page.route(`${base}/`, (route) => route.request().method() === "POST" ? route.fulfill({ status: 500, body: "" }) : route.continue());
    await page.goto(`${base}/contact.html`, { waitUntil: "networkidle" });
    await page.fill("#c-name", "X"); await page.fill("#c-email", "x@example.com"); await page.selectOption("#c-topic", "other"); await page.fill("#c-message", "Testing a failure case.");
    await page.getByRole("button", { name: "Send message" }).click();
    await page.getByText("Your message didn't send").waitFor();
  });

  await test("live site: sample team profiles are shown, every one labelled", async () => {
    const p2 = await browser.newPage();
    await p2.goto(`${base}/about.html`, { waitUntil: "networkidle" });
    await p2.getByText("They are not Ennavizion staff").waitFor();
    assert.equal(await p2.locator(".team-card").count(), 7);
    assert.equal(await p2.locator(".team-badge", { hasText: "Demo profile" }).count(), 7);
    await p2.close();
  });

  await test("gallery and shop show pictures", async () => {
    const p4 = await browser.newPage();
    for (const [page, sel] of [["gallery", "#gallery-grid img"], ["shop", "#product-grid img"]]) {
      await p4.goto(`${base}/${page}.html`, { waitUntil: "networkidle" });
      await p4.locator(sel).first().scrollIntoViewIfNeeded();
      assert.ok(await p4.locator(sel).count() > 0, page + " has images");
    }
    await p4.close();
  });

  await test("preview deploy: seven labelled demo profiles with loaded images and alt text", async () => {
    const p3 = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    await p3.goto(`http://preview-1--ennavizion.netlify.app:${port}/about.html`, { waitUntil: "networkidle" });
    await p3.getByText("Sample profiles").waitFor();
    assert.equal(await p3.locator(".team-card").count(), 7);
    assert.equal(await p3.locator(".team-badge", { hasText: "Demo profile" }).count(), 7);
    const imgs = await p3.locator(".team-card img").evaluateAll((els) => els.map((e) => ({ ok: e.complete && e.naturalWidth > 0, alt: e.alt })));
    assert.ok(imgs.every((i) => i.ok), "all images load");
    assert.ok(imgs.every((i) => i.alt.length > 10), "all images have alt text");
    await p3.close();
  });

  await browser.close();
  for (const r of results) console.log(r.join("  "));
  console.log(`${results.filter((r) => r[0] === "PASS").length}/${results.length} passed`);
})();
