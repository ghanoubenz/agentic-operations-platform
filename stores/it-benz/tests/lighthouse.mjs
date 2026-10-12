// Mobile Lighthouse audit of key pages. Usage: node lighthouse.mjs [baseUrl]
// Staging numbers are indicative only: no CDN/page cache, local Docker, emulated 4G + slow CPU.
import lighthouse from 'lighthouse';
import * as chromeLauncher from 'chrome-launcher';
import fs from 'node:fs';

const base = process.argv[2] || 'http://localhost:8080';
const pages = ['/', '/boutique/', '/produit/exemple-ecran-27-pouces-qhd/', '/categorie/souris-claviers/'];
const chrome = await chromeLauncher.launch({ chromeFlags: ['--headless=new', '--no-sandbox'], chromePath: process.env.CHROME_PATH });
const rows = [];
for (const p of pages) {
  const { lhr } = await lighthouse(base + p, { port: chrome.port, output: 'json', logLevel: 'error', formFactor: 'mobile', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] });
  const a = lhr.audits;
  rows.push({
    page: p,
    performance: Math.round(lhr.categories.performance.score * 100),
    accessibility: Math.round(lhr.categories.accessibility.score * 100),
    bestPractices: Math.round(lhr.categories['best-practices'].score * 100),
    seo: Math.round(lhr.categories.seo.score * 100),
    LCP: a['largest-contentful-paint'].displayValue,
    CLS: a['cumulative-layout-shift'].displayValue,
    TBT: a['total-blocking-time'].displayValue,
  });
}
await chrome.kill();
console.table(rows);
fs.writeFileSync(new URL('../docs/lighthouse-staging.json', import.meta.url), JSON.stringify({ date: new Date().toISOString(), base, rows }, null, 2));
