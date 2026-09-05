const {test,expect}=require('@playwright/test');
const AxeBuilder=require('@axe-core/playwright').default;

test('installed shell loads its scripts and snapshot without network',async({page,context})=>{
    await page.goto('/?lang=en');
    await page.evaluate(()=>navigator.serviceWorker.ready);
    await expect.poll(()=>page.evaluate(()=>Boolean(localStorage.getItem('cmaw-offline-snapshot-en')))).toBe(true);
    await page.reload();
    await context.setOffline(true);
    await page.goto('/station.php?code=air4thai%3A36t&lang=en');
    await expect(page.locator('#offline-snapshot')).toBeVisible();
    expect(await page.evaluate(()=>typeof window.CMAW)).toBe('object');
});

test('offline page reads saved wrapper and original save time',async({page})=>{
    await page.goto('/?lang=en');
    await expect.poll(()=>page.evaluate(()=>Boolean(localStorage.getItem('cmaw-offline-snapshot-en')))).toBe(true);
    await page.evaluate(()=>{const key='cmaw-offline-snapshot-en';const snapshot=JSON.parse(localStorage.getItem(key));snapshot.snapshot_saved_at='2026-09-01T01:23:00Z';localStorage.setItem(key,JSON.stringify(snapshot));});
    await page.goto('/offline.php?lang=en');
    await expect(page.locator('#offline-snapshot')).toBeVisible();
    await expect(page.locator('#offline-stored')).toContainText('01 Sept');
    await expect(page.locator('#offline-stored')).toContainText('08:23');
});

test('snapshot station selection stays offline during API failure',async({page})=>{
    await page.goto('/?lang=en');
    await expect.poll(()=>page.evaluate(()=>Boolean(localStorage.getItem('cmaw-offline-snapshot-en')))).toBe(true);
    await page.route('**/api/current.php**',route=>route.abort());
    await page.reload();
    await expect(page.locator('#primary-freshness')).toHaveText('OFFLINE');
    await page.locator('#home-station-select').selectOption({index:1});
    await expect(page.locator('#primary-freshness')).toHaveText('OFFLINE');
});

test('all main pages have no script errors or overflow in both languages',async({page})=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    for(const lang of ['en','th'])for(const path of ['/','/stations.php','/station.php?code=dustboy%3A3145','/alerts.php','/offline.php']){
        const response=await page.goto(path+(path.includes('?')?'&':'?')+'lang='+lang);
        expect(response.status()).toBe(200);
        expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
    }
    expect(errors).toEqual([]);
});

test('station list and alerts pass accessibility in light and dark',async({page})=>{
    for(const path of ['/stations.php?lang=en','/alerts.php?lang=th'])for(const theme of ['light','dark']){
        await page.goto(path);await page.evaluate(theme=>{document.documentElement.dataset.theme=theme;},theme);
        const result=await new AxeBuilder({page}).analyze();expect(result.violations).toEqual([]);
    }
});
