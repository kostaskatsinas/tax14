import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';

const base = process.env.SITE_URL || 'http://127.0.0.1:8080';
await fs.mkdir('artifacts', {recursive: true});
const browser = await chromium.launch({headless: true});
const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
const errors = [];
page.on('pageerror', error => errors.push(error.message));
try {
  // Allow a freshly spawned development server to become available.
  for (let attempt = 0; attempt < 30; attempt++) {
    try { await page.goto(base); break; } catch (error) { if (attempt === 29) throw error; await new Promise(r => setTimeout(r, 500)); }
  }
  for (const path of ['/', '/about/', '/experience/', '/education/', '/contact/', '/annual-reports/', '/annual-reports/sample-annual-report-2026/']) {
    const response = await page.goto(base + path);
    assert.equal(response.status(), 200, path);
    assert.equal(await page.locator('main').count(), 1, 'One main landmark: ' + path);
    assert.equal(await page.locator('h1').count(), 1, 'One page title: ' + path);
    assert.equal(await page.locator('header nav').count(), 1, 'Header navigation: ' + path);
    assert.ok(await page.locator('body').evaluate(el => el.scrollWidth <= innerWidth + 1), 'No desktop overflow: ' + path);
    for (const link of await page.locator('main a[href], footer a[href]').evaluateAll(nodes => [...new Set(nodes.map(n => n.href))])) {
      if (link.startsWith(base) && !link.includes('#')) assert.ok((await page.request.get(link)).ok(), 'Working link: ' + link);
    }
  }
  await page.goto(base);
  const heroBounds = await page.locator('.hero').boundingBox();
  const headerBounds = await page.locator('.site-header').boundingBox();
  assert.ok(headerBounds.x >= 20 && headerBounds.x + headerBounds.width <= 1420, 'Header has horizontal breathing room');
  assert.ok(heroBounds.width > 1000, 'Homepage uses the intended wide layout');
  await page.screenshot({path: 'artifacts/home-desktop.png', fullPage: true});
  await page.keyboard.press('Tab');
  assert.match(await page.locator(':focus').innerText(), /skip to content/i);
  await page.keyboard.press('Enter');
  await page.goto(base + '/annual-reports/');
  assert.deepEqual(await page.locator('.tax14-report-year').allTextContents(), ['2026', '2025', '2024']);
  const pdfURL = await page.locator('.tax14-pdf-button').first().getAttribute('href');
  const pdfResponse = await page.request.get(pdfURL);
  assert.match(pdfResponse.headers()['content-type'], /application\/pdf/);
  const pdf = await pdfResponse.body();
  assert.equal(pdf.subarray(0, 5).toString(), '%PDF-');
  await fs.writeFile('artifacts/upload-test.pdf', pdf);
  await page.screenshot({path: 'artifacts/reports-desktop.png', fullPage: true});
  for (const width of [375, 768]) {
    await page.setViewportSize({width, height: 900});
    for (const path of ['/', '/experience/', '/education/', '/annual-reports/', '/contact/']) {
      await page.goto(base + path);
      assert.ok(await page.locator('body').evaluate(el => el.scrollWidth <= innerWidth + 1), 'No responsive overflow: ' + width + path);
    }
    await page.goto(base);
    await page.screenshot({path: `artifacts/home-${width}.png`, fullPage: true});
  }
  await page.setViewportSize({width: 375, height: 850});
  await page.goto(base);
  const menu = page.getByRole('button', {name: 'Open menu'});
  await menu.focus(); await page.keyboard.press('Enter');
  const overlay = page.locator('.wp-block-navigation__responsive-container.is-menu-open');
  await overlay.waitFor({state: 'visible'});
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('.is-menu-open').count(), 0, 'Escape closes mobile navigation');
  await menu.click();
  await overlay.getByRole('link', {name: 'Annual Reports', exact: true}).click();
  await page.waitForURL('**/annual-reports/');

  await page.setViewportSize({width: 1440, height: 1000});
  await page.goto(base + '/wp-login.php');
  await page.locator('#user_login').fill(process.env.WP_TEST_USER || 'tax14admin');
  await page.locator('#user_pass').fill(process.env.WP_TEST_PASSWORD || 'local-test-only-927');
  await page.locator('#wp-submit').click();
  await page.waitForURL('**/wp-admin/');
  await page.goto(base + '/wp-admin/post-new.php?post_type=annual_report');
  await page.locator('#title').fill('Browser upload report 2027');
  await page.locator('#tax14-year').fill('2027');
  await page.locator('#tax14-select-pdf').click();
  await page.getByRole('tab', {name: 'Upload files'}).click();
  await page.locator('.media-modal input[type=file]').setInputFiles('artifacts/upload-test.pdf');
  const select = page.getByRole('button', {name: 'Use this PDF', exact: true});
  await select.waitFor();
  await select.click();
  assert.ok(Number(await page.locator('#tax14-pdf').inputValue()) > 0);
  await page.locator('#publish').click();
  await page.waitForLoadState('domcontentloaded');
  await page.locator('#message').filter({hasText: 'published'}).waitFor();
  await page.screenshot({path: 'artifacts/report-admin.png', fullPage: true});
  await page.goto(base + '/annual-reports/');
  assert.equal(await page.locator('.tax14-report-year').first().textContent(), '2027');

  // Inspect Gutenberg's actual block validation and save a page edit.
  const pages = await (await page.request.get(base + '/wp-json/wp/v2/pages?slug=about')).json();
  await page.goto(base + '/wp-admin/post.php?post=' + pages[0].id + '&action=edit');
  await page.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks()?.length > 0);
  assert.deepEqual(await page.evaluate(() => {
    const invalid = [];
    const walk = blocks => blocks.forEach(block => { if (block.isValid === false) invalid.push(block.name); walk(block.innerBlocks || []); });
    walk(wp.data.select('core/block-editor').getBlocks()); return invalid;
  }), [], 'Starter blocks are valid in Gutenberg');
  await page.evaluate(async () => {
    wp.data.dispatch('core/block-editor').insertBlocks(wp.blocks.createBlock('core/paragraph', {content: 'Verified WordPress editor update.'}));
    await wp.data.dispatch('core/editor').savePost();
  });
  await page.goto(base + '/about/');
  assert.ok(await page.getByText('Verified WordPress editor update.').count());
  for (const slug of ['home', 'experience', 'education']) {
    const editablePages = await (await page.request.get(base + '/wp-json/wp/v2/pages?slug=' + slug)).json();
    await page.goto(base + '/wp-admin/post.php?post=' + editablePages[0].id + '&action=edit');
    await page.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks()?.length > 0);
    assert.deepEqual(await page.evaluate(() => {
      const invalid = [];
      const walk = blocks => blocks.forEach(block => { if (block.isValid === false) invalid.push(block.name); walk(block.innerBlocks || []); });
      walk(wp.data.select('core/block-editor').getBlocks()); return invalid;
    }), [], 'Native blocks remain editable on ' + slug);
  }
  const missing = await page.goto(base + '/missing-page-test/');
  assert.equal(missing.status(), 404);
  assert.deepEqual(errors, [], 'No browser JavaScript exceptions');
  console.log('PASS pages, navigation, keyboard, responsive layouts, links, PDFs, admin upload, and Gutenberg editing');
} finally {
  await browser.close();
}
