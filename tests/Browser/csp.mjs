// Content Security Policy de production éprouvée dans Chromium (Playwright), appelée par CspTest.php.
// Usage : node tests/Browser/csp.mjs <url de base> <pages JSON> [compte de démo]
// Pour chaque page : l'en-tête CSP est bien reçu, puis on utilise ce qui exécute du JavaScript
// (Copier le lien, bascule du thème, recherche Livewire, vote, menus) et on relève toute violation
// (événement securitypolicyviolation, message de la console) et toute erreur JavaScript.
import { chromium } from 'playwright';

const [base, pagesJson, persona] = process.argv.slice(2);
const pages = JSON.parse(pagesJson);
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: base });

let current = [];
await context.exposeFunction('__cspViolation', (v) => current.push(v));
await context.addInitScript(() => {
    document.addEventListener('securitypolicyviolation', (e) => {
        window.__cspViolation(`${e.violatedDirective} : ${e.blockedURI || 'en ligne'} (${e.sourceFile || ''}:${e.lineNumber || ''})`);
    });
});

const page = await context.newPage();
page.on('console', (m) => {
    if (/Content Security Policy|Refused to/i.test(m.text())) current.push(m.text());
});
page.on('pageerror', (e) => current.push(`Erreur JavaScript : ${e.message}`));

const report = [];

if (persona) {
    await page.goto(`${base}/connexion`);
    await page.getByRole('button', { name: new RegExp(`Entrer comme ${persona}`) }).click();
    await page.waitForLoadState('networkidle');
}

async function clickIfVisible(locator, label, actions) {
    const target = locator.first();
    if ((await target.count()) && (await target.isVisible()) && (await target.isEnabled())) {
        await target.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(150);
        actions.push(label);
    }
}

for (const path of pages) {
    current = [];
    const actions = [];
    const response = await page.goto(base + path, { waitUntil: 'networkidle' });
    const csp = (await response.allHeaders())['content-security-policy'] ?? null;

    await clickIfVisible(page.getByRole('button', { name: 'Copier le lien' }), 'copier', actions);
    await clickIfVisible(page.getByRole('radio', { name: 'Sombre' }), 'sombre', actions);
    await clickIfVisible(page.getByRole('radio', { name: 'Clair' }), 'clair', actions);

    const search = page.locator('#filtre-q');
    if ((await search.count()) && (await search.isVisible())) {
        await search.fill('paiement');
        await page.waitForTimeout(600);
        await page.waitForLoadState('networkidle');
        actions.push('recherche');
    }

    await clickIfVisible(page.getByRole('button', { name: /^Voter pour/ }), 'vote', actions);
    await clickIfVisible(page.getByRole('button', { name: /Publier/ }), 'menu', actions);

    // Alpine a bien démarré (sans 'unsafe-eval' refusé, il ne démarrerait pas).
    const alpine = await page.evaluate(() => typeof window.Alpine === 'object');

    report.push({ path, status: response.status(), csp, alpine, actions, violations: current, title: await page.title() });
}

await browser.close();
process.stdout.write(JSON.stringify(report));
