// Ressources chargées par une page (après défilement jusqu'en bas, pour les images à la demande).
// Usage : node tests/Browser/weight.mjs <url> <largeur>  → JSON [{ url, type }]
import { chromium } from 'playwright';

const [url, width] = process.argv.slice(2);
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: Number(width), height: 900 } });
const resources = [];
page.on('response', (response) => resources.push({ url: response.url(), type: response.request().resourceType() }));

await page.goto(url, { waitUntil: 'networkidle' });
await page.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 600) {
        window.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 80));
    }
});
await page.waitForLoadState('networkidle');
await browser.close();
console.log(JSON.stringify(resources));
