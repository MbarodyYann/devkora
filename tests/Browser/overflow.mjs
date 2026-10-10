// Mesure réelle du débordement horizontal dans Chromium (Playwright), appelée par OverflowTest.php.
// Usage : node tests/Browser/overflow.mjs <url de base> <largeur> <pages JSON> [compte de démo]
// Pour chaque page, en clair puis en sombre : la page ne défile pas horizontalement, et aucun élément visible
// ne sort de l'écran sans être contenu par un ancêtre qui le fait défiler ou le rogne dans l'écran.
import { chromium } from 'playwright';

const [base, width, pagesJson, persona] = process.argv.slice(2);
const pages = JSON.parse(pagesJson);
const browser = await chromium.launch();
const report = [];

for (const scheme of ['light', 'dark']) {
    const context = await browser.newContext({ viewport: { width: Number(width), height: 740 }, colorScheme: scheme });
    const page = await context.newPage();

    if (persona) {
        await page.goto(`${base}/connexion`);
        await page.getByRole('button', { name: new RegExp(`Entrer comme ${persona}`) }).click();
        await page.waitForLoadState('networkidle');
    }

    for (const path of pages) {
        await page.goto(base + path, { waitUntil: 'networkidle' });
        await page.evaluate(() => document.fonts.ready);

        const result = await page.evaluate(() => {
            const viewport = document.documentElement.clientWidth;
            const inside = (r) => r.left >= -0.5 && r.right <= viewport + 0.5;
            const offenders = [];

            for (const el of document.body.querySelectorAll('*')) {
                const rect = el.getBoundingClientRect();
                const style = getComputedStyle(el);
                if (rect.width === 0 || rect.height === 0 || style.visibility === 'hidden' || inside(rect)) continue;
                // Texte réservé aux lecteurs d'écran (sr-only) : rogné à 1 px, invisible.
                if (style.position === 'absolute' && (style.clip !== 'auto' || style.clipPath !== 'none')) continue;

                // Contenu par un ancêtre qui défile ou rogne et qui tient dans l'écran (texte défilant, décor des bandeaux).
                let contained = false;
                for (let a = el.parentElement; a && a !== document.documentElement; a = a.parentElement) {
                    if (getComputedStyle(a).overflowX !== 'visible' && inside(a.getBoundingClientRect())) {
                        contained = true;
                        break;
                    }
                }
                if (! contained) {
                    offenders.push(`${el.tagName.toLowerCase()}.${String(el.className).slice(0, 60)} [${Math.round(rect.left)}, ${Math.round(rect.right)}]`);
                }
            }

            return {
                scrollWidth: document.documentElement.scrollWidth,
                viewport,
                clip: [getComputedStyle(document.documentElement).overflowX, getComputedStyle(document.body).overflowX],
                dark: document.documentElement.classList.contains('dark'),
                title: document.title,
                offenders: offenders.slice(0, 5),
            };
        });

        // Navigation une fois la page défilée : opacité de son fond (08 : aucun texte lisible au travers).
        await page.evaluate(() => window.scrollTo(0, 600));
        await page.waitForTimeout(150);
        const navAlpha = await page.evaluate(() => {
            const color = getComputedStyle(document.querySelector('header')).backgroundColor;
            const alpha = color.match(/\/\s*([\d.]+)\s*\)$/) || color.match(/^rgba\(.*,\s*([\d.]+)\)$/);
            return color === 'rgba(0, 0, 0, 0)' || color === 'transparent' ? 0 : (alpha ? Number(alpha[1]) : 1);
        });

        report.push({ path, scheme, navAlpha, ...result });
    }

    await context.close();
}

await browser.close();
console.log(JSON.stringify(report));
