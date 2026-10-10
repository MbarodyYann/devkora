// Mesure réelle du débordement horizontal dans Chromium (Playwright), appelée par OverflowTest.php.
// Usage : node tests/Browser/overflow.mjs <url de base> <largeur> <pages JSON> [compte de démo]
// Pour chaque page, en clair puis en sombre : la page ne défile pas horizontalement, et aucun élément visible
// ne sort de l'écran sans être contenu par un ancêtre qui le fait défiler ou le rogne dans l'écran.
import { chromium } from 'playwright';

const [base, width, pagesJson, persona] = process.argv.slice(2);
// NOJS=1 : sans JavaScript, comme une page dont le script n'est pas encore arrivé en 3G.
const javaScriptEnabled = process.env.NOJS !== '1';
const pages = JSON.parse(pagesJson);
const browser = await chromium.launch();
const report = [];

for (const scheme of ['light', 'dark']) {
    const context = await browser.newContext({ viewport: { width: Number(width), height: 740 }, colorScheme: scheme, javaScriptEnabled });
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
        // Bande au-dessus de la barre (12 px sur mobile) : rien de la page ne doit y apparaître.
        const navTopCovered = await page.evaluate(() => {
            const bar = document.querySelector('header').parentElement;
            const top = bar.getBoundingClientRect().top;
            return [1, 6, 11].every((dy) => [4, innerWidth / 2, innerWidth - 4].every((x) => bar.contains(document.elementFromPoint(x, top + dy))));
        });
        const navAlpha = await page.evaluate(() => {
            const color = getComputedStyle(document.querySelector('header')).backgroundColor;
            const alpha = color.match(/\/\s*([\d.]+)\s*\)$/) || color.match(/^rgba\(.*,\s*([\d.]+)\)$/);
            return color === 'rgba(0, 0, 0, 0)' || color === 'transparent' ? 0 : (alpha ? Number(alpha[1]) : 1);
        });

        // Barre d'action collante (mobile) : une fois visible, opaque, bouton plein, et c'est elle qu'on touche (F1).
        await page.evaluate(() => window.scrollTo(0, 250));
        await page.waitForTimeout(400);
        const actionBar = await page.evaluate(() => {
            const bar = document.querySelector('[data-action-bar]');
            if (!bar || getComputedStyle(bar).display === 'none' || bar.getBoundingClientRect().height === 0) return null;
            let opacity = 1;
            for (let el = bar; el; el = el.parentElement) opacity *= Number(getComputedStyle(el).opacity);
            const surface = bar.firstElementChild;
            const color = getComputedStyle(surface).backgroundColor;
            const alpha = color.match(/\/\s*([\d.]+)\s*\)$/) || color.match(/^rgba\(.*,\s*([\d.]+)\)$/);
            const button = bar.querySelector('a, button');
            const r = surface.getBoundingClientRect();
            const hit = [[r.left + 8, r.top + r.height / 2], [r.right - 8, r.top + r.height / 2], [r.left + r.width / 2, r.top + 6]]
                .every(([x, y]) => bar.contains(document.elementFromPoint(x, y)));
            return { opacity, bgAlpha: alpha ? Number(alpha[1]) : 1, buttonOpacity: Number(getComputedStyle(button).opacity), hit };
        });

        report.push({ path, scheme, navAlpha, navTopCovered, actionBar, ...result });
    }

    await context.close();
}

await browser.close();
console.log(JSON.stringify(report));
