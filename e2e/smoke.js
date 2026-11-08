// Smoke test: every page, three widths. Reports console errors, failed requests,
// horizontal overflow, and saves screenshots. Usage: node smoke.js <baseUrl> <outDir> [shotPages]
const { chromium } = require("playwright");
const fs = require("fs");
const [base, out, shotList = "index,about,programs,league,enroll,donate,contact"] = process.argv.slice(2);
const PAGES = ["index", "about", "programs", "league", "community", "donate", "enroll", "contact", "shop", "gallery", "blog", "post", "privacy", "safeguarding", "terms", "404"];
const WIDTHS = [320, 390, 1440];
fs.mkdirSync(out, { recursive: true });

(async () => {
  const browser = await chromium.launch();
  let problems = 0;
  for (const w of WIDTHS) {
    const ctx = await browser.newContext({ viewport: { width: w, height: 900 }, reducedMotion: "reduce" });
    for (const p of PAGES) {
      const page = await ctx.newPage();
      const errs = [];
      page.on("console", (m) => { if (m.type() === "error") errs.push("console: " + m.text()); });
      page.on("pageerror", (e) => errs.push("pageerror: " + e.message));
      page.on("requestfailed", (r) => { if (!r.url().includes("unsplash")) errs.push("failed: " + r.url() + " " + r.failure()?.errorText); });
      page.on("response", (r) => { if (r.status() >= 400 && !r.url().endsWith("favicon.ico")) errs.push(`http ${r.status()}: ${r.url()}`); });
      await page.goto(`${base}/${p}.html`, { waitUntil: "networkidle" });
      await page.waitForTimeout(300);
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
      if (overflow > 0) errs.push(`horizontal overflow ${overflow}px`);
      const text = await page.evaluate(() => document.body.innerText);
      for (const bad of ["undefined", "NaN", "null", "[object Object]", "Coach Name", "placeholder", "XX+"]) {
        if (new RegExp(`\\b${bad.replace(/[[\]+]/g, "\\$&")}`, "i").test(text)) errs.push(`visible text contains "${bad}"`);
      }
      if (errs.length) { problems += errs.length; console.log(`[${w}] ${p}:\n  ` + errs.join("\n  ")); }
      if (shotList.split(",").includes(p) && (w === 390 || w === 1440)) await page.screenshot({ path: `${out}/${p}-${w}.png`, fullPage: true });
      await page.close();
    }
    await ctx.close();
  }
  await browser.close();
  console.log(problems ? `${problems} problem(s)` : "no problems found");
})();
