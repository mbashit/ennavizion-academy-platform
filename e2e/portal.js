// Parent portal, end to end in a real browser against the local backend.
// Needs: php artisan serve (127.0.0.1:8000, MAIL_MAILER=log) and the site on localhost:5500.
// Usage: node portal.js [screenshotDir]
const { chromium } = require("playwright");
const assert = require("assert/strict");
const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

const API = "http://127.0.0.1:8000";
const SITE = "http://localhost:5500";
const BACKEND = process.env.BACKEND_DIR || path.join(__dirname, "..", "backend");
const shots = process.argv[2] || "portal-screenshots";
fs.mkdirSync(shots, { recursive: true });
const email = `e2e-parent-${Date.now()}@example.com`;
const results = [];
const test = async (name, fn) => { try { await fn(); results.push(["PASS", name]); } catch (e) { results.push(["FAIL", name, e.message.split("\n")[0]]); } };
const tinker = (php) => execSync(`php artisan tinker --execute "${php.replace(/"/g, '\\"')}"`, { cwd: BACKEND }).toString().trim();
const isoYearsAgo = (y) => { const d = new Date(); d.setFullYear(d.getFullYear() - y); d.setDate(d.getDate() - 20); return d.toISOString().slice(0, 10); };

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  // Filament gives every field a stable id: data.<field>.
  const field = (name) => page.locator(`[id="data.${name}"]`);
  const pick = async (name, text) => {
    const value = await field(name).evaluate((sel, t) => [...sel.options].find((o) => o.text.includes(t))?.value, text);
    assert.ok(value, `option containing "${text}" in ${name}`);
    await field(name).selectOption(value);
  };
  let reference;

  await test("website links to the parent portal", async () => {
    await page.goto(`${SITE}/enroll.html`, { waitUntil: "networkidle" });
    assert.equal(await page.locator(".portal-link").getAttribute("href"), `${API}/portal/login`);
    await page.getByText("Track your application online").waitFor();
  });

  await test("parent signs up and must verify their email", async () => {
    await page.goto(`${API}/portal/register`);
    await field("name").fill("E2E Parent");
    await field("email").fill(email);
    await field("phone").fill("+234 803 000 0000");
    await field("password").fill("a-strong-password-1");
    await field("passwordConfirmation").fill("a-strong-password-1");
    await field("guardian_confirmed").check();
    await page.screenshot({ path: `${shots}/1-register.png`, fullPage: true });
    await page.getByRole("button", { name: "Sign up" }).click();
    await page.waitForURL(/email-verification\/prompt/);
  });

  await test("email verification link from the real email works", async () => {
    // Locally, Filament's verification email is queued; deliver it (production uses QUEUE_CONNECTION=sync).
    execSync("php artisan queue:work --stop-when-empty --tries=1", { cwd: BACKEND });
    const log = fs.readFileSync(path.join(BACKEND, "storage/logs/laravel.log"), "utf8");
    const links = [...log.matchAll(/http:\/\/127\.0\.0\.1:8000\/portal\/email-verification\/verify\/[^\s\]"<>]+/g)].map((m) => m[0].replace(/&amp;/g, "&"));
    assert.ok(links.length, "verification email was written to the mail log");
    await page.goto(links[links.length - 1]);
    await page.waitForURL(`${API}/portal`);
    await page.getByText("Open applications").waitFor();
  });

  await test("parent adds a player", async () => {
    await page.goto(`${API}/portal/players/create`);
    await field("first_name").fill("Tobi");
    await field("last_name").fill("Parent");
    await field("dob").fill(isoYearsAgo(11));
    await field("position").selectOption("Midfielder");
    await field("medical_notes").fill("Mild asthma — carries an inhaler");
    await page.getByRole("button", { name: "Create", exact: true }).click();
    await page.waitForURL(/\/portal\/players$/);
    await page.getByText("Tobi Parent").waitFor();
  });

  await test("parent applies for a trial and gets a reference", async () => {
    await page.goto(`${API}/portal/trial-applications/create`);
    await pick("player_id", "Tobi Parent");
    await pick("program_id", "Development");
    if (await field("centre").count()) await field("centre").selectOption({ index: 1 });
    await field("consent").check();
    await page.screenshot({ path: `${shots}/2-apply.png`, fullPage: true });
    await page.getByRole("button", { name: "Create", exact: true }).click();
    await page.waitForURL(/\/portal\/trial-applications\/\d+$/);
    reference = (await page.locator("text=/ENR-\\d{4}-[A-Z0-9]{6}/").first().innerText()).trim();
    assert.match(reference, /ENR-\d{4}-[A-Z0-9]{6}/);
    await page.getByText("Submitted").first().waitFor();
  });

  await test("staff schedule the trial; the parent sees it and is emailed", async () => {
    tinker(`$a=App\\Models\\TrialApplication::where('reference','${reference}')->first(); $a->update(['status'=>'trial_scheduled','trial_at'=>now()->addDays(5)->setTime(10,0),'trial_location'=>'Jos training ground','message_to_parent'=>'Please bring boots and water.','staff_notes'=>'internal only']); echo 'ok';`);
    await page.reload();
    await page.getByText("Trial scheduled").first().waitFor();
    await page.getByText("Jos training ground").waitFor();
    await page.getByText("Please bring boots and water.").waitFor();
    assert.equal(await page.getByText("internal only").count(), 0, "staff notes never shown");
    const log = fs.readFileSync(path.join(BACKEND, "storage/logs/laravel.log"), "utf8");
    assert.ok(log.includes(`Update on trial application ${reference}`), "update email sent");
    await page.screenshot({ path: `${shots}/3-application.png`, fullPage: true });
    await page.goto(`${API}/portal`);
    await page.getByText("Jos training ground").waitFor(); // next trial on the dashboard
    await page.screenshot({ path: `${shots}/4-dashboard.png`, fullPage: true });
  });

  await test("a parent is refused at the staff admin panel", async () => {
    const res = await page.goto(`${API}/admin`);
    assert.equal(res.status(), 403);
  });

  await test("mobile: dashboard fits the screen", async () => {
    const m = await browser.newPage({ viewport: { width: 375, height: 800 } });
    await m.context().addCookies(await page.context().cookies());
    await m.goto(`${API}/portal`);
    assert.ok(await m.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), "no horizontal scroll");
    await m.screenshot({ path: `${shots}/5-mobile.png`, fullPage: true });
    await m.close();
  });

  await browser.close();
  for (const r of results) console.log(r.join("  "));
  console.log(`${results.filter((r) => r[0] === "PASS").length}/${results.length} passed`);
})();
