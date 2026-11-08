// Serves every page with the Content-Security-Policy from _headers and reports violations.
const { chromium } = require("playwright");
const fs = require("fs");
const [base, headersFile] = process.argv.slice(2);
const csp = fs.readFileSync(headersFile, "utf8").match(/Content-Security-Policy: (.*)/)[1].trim();
const PAGES = ["index", "about", "programs", "league", "community", "donate", "enroll", "contact", "shop", "gallery", "blog", "post", "privacy", "safeguarding", "terms", "404"];
(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  await ctx.route("**/*.html*", async (route) => {
    const res = await route.fetch();
    route.fulfill({ response: res, headers: { ...res.headers(), "content-security-policy": csp } });
  });
  let bad = 0;
  for (const p of PAGES) {
    const page = await ctx.newPage();
    const v = [];
    page.on("console", (m) => { if (/Content Security Policy|Refused to/i.test(m.text())) v.push(m.text().slice(0, 160)); });
    await page.goto(`${base}/${p}.html`, { waitUntil: "networkidle" });
    if (v.length) { bad += v.length; console.log(p, v); }
    await page.close();
  }
  await browser.close();
  console.log(bad ? `${bad} CSP violation(s)` : "no CSP violations");
})();
