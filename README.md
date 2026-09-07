# tax14 — accountant website

A complete WordPress block theme and small companion plugin for a professional profile and annual PDF reports. Warm white, dark ink, restrained teal, system fonts, and an editorial layout. No build step is needed to run the website.

**Recurring WordPress software cost: €0/month.** Hosting and the domain are separate. No license keys, premium upgrades, external APIs, paid fonts, stock assets, form services, or subscription accounts are required.

## Dependencies

| Component | Version / requirement | Cost | Purpose |
| --- | --- | --- | --- |
| WordPress core | 6.6+ API baseline; install the current stable, security-maintained release | Free, GPL | Admin, Gutenberg, Site Editor, media handling, users, revisions |
| PHP | 8.1 minimum; use a supported release, preferably 8.3+ | Free | WordPress runtime |
| MySQL / MariaDB | Host meeting current WordPress requirements; recommended MySQL 8.0+ or MariaDB 10.11+ | Free software | Content database |
| Accountant Site | 1.0.0, included custom block theme | Free, GPL-2.0-or-later | Templates and appearance |
| Accountant Annual Reports | 1.0.0, included custom plugin | Free, GPL-2.0-or-later | Reports, contact details, optional starter content, basic metadata |

There are **no third-party WordPress plugins** to install. The companion plugin is needed for report publishing; ordinary profile pages remain native WordPress content if it is deactivated. No optional plugins are bundled. There is no contact form: the Contact page uses email and telephone links, which require no mail service or anti-spam dependency.

The CI workflow downloads the current stable WordPress release and records its exact version in `wordpress-version.txt`. See the latest successful **WordPress verification** run for the tested version. The minimum API baseline is not a claim that every older release has been tested. WordPress core and database files are intentionally excluded from this repository.

Official references: [WordPress download and host requirements](https://wordpress.org/download/), [WP-CLI core downloads](https://developer.wordpress.org/cli/commands/core/download/), [native post metadata](https://developer.wordpress.org/reference/functions/register_post_meta/).

## Install

Use a fresh or backed-up **self-hosted WordPress** installation. A static host such as GitHub Pages cannot run WordPress PHP or its database. The software does not require a paid WordPress.com plan or service.

1. Obtain the two installation ZIPs from a successful GitHub Actions run: **Actions → WordPress verification → tax14-verification-and-installers**. Unzip the outer artifact download to find `accountant-site.zip` and `accountant-annual-reports.zip`. Alternatively, run `python3 scripts/package.py` in a downloaded copy of this repository; ZIPs are written to `artifacts/`.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**. Upload `accountant-annual-reports.zip` and activate it.
3. Open **Appearance → Themes → Add New Theme → Upload Theme**. Upload `accountant-site.zip` and activate it.
4. Go to **Settings → Permalinks**, choose **Post name**, and save. This provides `/annual-reports/` and report detail URLs.
5. Go to **Tools → Accountant Setup**. Optionally check **Use the Home page as the site homepage**, then select **Create starter content**. It creates missing pages, a primary navigation menu when no published navigation exists, and sample reports for 2026, 2025, and 2024. It does not overwrite existing pages or reports. Samples are clearly labelled and contain no real financial information. The action publishes demo profile pages and reports; use it on staging before launch.
6. Enter shared contact details in the same setup screen and save them.
7. Open **Settings → General** and replace the site title and tagline with the accountant's name or office name and professional title. Set the correct language, timezone, and date format.
8. Review the pages, replace every bracketed placeholder, replace/delete the sample reports, and review the draft policy pages before launch. If this is an existing site, check **Appearance → Editor → Navigation** and add the six requested menu links if an existing navigation was preserved.

For a fresh local development installation with WP-CLI already configured:

```bash
wp plugin activate accountant-annual-reports
wp theme activate accountant-site
wp rewrite structure '/%postname%/'
wp eval 'Tax14\create_starter(true);' --user=YOUR_ADMIN_LOGIN
```

These commands run from the WordPress installation after copying the two folders into its `wp-content`. Replace the admin login; no production credentials belong in this repository.

## Everyday editing

| What to change | Where in WordPress |
| --- | --- |
| Name / brand and professional tagline in header and footer | Settings → General |
| Hero name, title, introduction, profile summary, homepage sections | Pages → Home → Edit |
| Full professional profile, areas of expertise, philosophy, portrait, memberships | Pages → About → Edit |
| Work history | Pages → Experience → Edit |
| Education, certifications, seminars | Pages → Education → Edit |
| Shared contact name, office, email, phone, address, hours | Tools → Accountant Setup |
| Contact introduction and optional LinkedIn link | Pages → Contact → Edit |
| Header, footer, navigation, archive introduction, page templates | Appearance → Editor |
| Page meta description | Page editor → Excerpt |
| Home meta-description fallback | Settings → General → Tagline |

Experience and education are native block groups, not a custom-field framework. Use **List View** to select an entire entry, then **Duplicate**, edit its fields, and move it into newest-first order. Insert more entries from **Patterns → Accountant → Experience entry / Education entry**. Entries support dates, job title, company, location, descriptions, responsibilities, achievements, degree, institution, field, and graduation year as editable text. Optional sections may be removed.

The homepage contains short, separately editable highlights. To reuse an entry across the full page and homepage, select its Group and choose **Create pattern → Synced**, then insert that same synced pattern on the other page. Changes to a synced pattern update every instance. Keep it unsynced when a shorter homepage summary is preferred.

Add a portrait with the native Image block, supply alt text, and select an appropriately sized image. No portrait or credentials are invented. Native WordPress handles responsive image sizes and eligible lazy loading.

## Publish an annual PDF report

**WordPress Admin → Annual Reports → Add New → Enter year/title → Upload PDF → Publish**

1. Enter the report title.
2. Enter the fiscal year, between 1900 and 2100.
3. Optionally enter the company/entity and a description in the editor. Use **Excerpt** for a short archive summary.
4. Select **Choose or upload PDF**. Upload a PDF or select an existing PDF in the Media Library. Select **Use this PDF**.
5. Set the publication date in the standard Publish panel if needed, then select **Publish**.

The report appears in `/annual-reports/`, sorted by fiscal year descending, and among the latest three on the homepage. The archive shows 12 per page and paginates. Reports with the same fiscal year sort by publication date, then ID. Each report has its own page, publication date, description, entity, a PDF viewing link, a download link, and a link back to the archive.

Missing title, invalid year, or missing/non-PDF attachment prevents publishing and saves the report as a draft. Drafts may be incomplete. To replace a report's PDF, edit the report, select its new attachment, and **Update**. Removing a required PDF from an otherwise published report returns it to draft. Deleting an attached PDF directly from the Media Library leaves a clear “PDF currently unavailable” message until the report is updated.

Reports deliberately use WordPress's classic editing screen so title, fields, PDF, and publication status save in one native form. No Classic Editor plugin is required. Profile pages use Gutenberg. Metadata supports WordPress revisions and is available to authorized REST clients.

Uploaded media files are publicly accessible even when a report is a draft; upload approved public documents only. The download link uses the browser's native `download` attribute. Hosting headers and cross-origin media/CDN URLs can cause a browser to display the PDF instead; **Save / Download** in the PDF viewer remains available. No custom file-streaming endpoint exposes filesystem paths.

## Appearance and SEO

Use **Appearance → Editor → Styles** to change the palette, typography, spacing, and buttons without code. Defaults are centralized in `theme.json`; optional stylesheet refinements are in `assets/site.css`. The header and mobile menu use the native Navigation block, including its keyboard controls. The footer year updates automatically.

Core supplies document titles, canonical URLs for singular content, and robots directives. The plugin adds excerpt-based descriptions and Open Graph text metadata. Home uses the site tagline when no excerpt exists. Add a useful excerpt to every page/report; the plugin does not manufacture one from personal placeholders. Basic Person or ProfessionalService JSON-LD is emitted on the homepage only after a contact name or office is provided. It does not invent credentials, reviews, or ratings.

The metadata layer automatically yields to Yoast, Rank Math, AIOSEO, and SEOPress when detected. Other SEO integrations can disable it with `add_filter( 'tax14_enable_seo', '__return_false' );`. No SEO plugin is required. The theme does not add analytics, trackers, remote fonts, maps, or third-party embeds.

## Privacy pages

Starter Privacy Policy and Cookie Policy pages remain drafts and contain only completion prompts. Use **Settings → Privacy** for WordPress's suggested policy content and select the appropriate page. Review the actual site's data handling and installed features before publishing. Footer policy links appear automatically once the corresponding pages are published; the privacy link follows the page chosen in WordPress settings. No legal text or cookie-consent service is invented.

## Architecture

```text
wp-content/themes/accountant-site/
  theme.json                 Design system and editor defaults
  functions.php              Theme setup and presentation styles
  assets/site.css            Responsive layout and focus styling
  parts/                     Editable header and footer
  templates/                 Home, pages, archive, report, index, 404
  patterns/                  Reusable experience and education groups
wp-content/plugins/accountant-annual-reports/
  accountant-annual-reports.php  CPT, metadata, validation, rendering, blocks
  includes/setup.php            Admin settings and idempotent starter setup
  includes/starter.php          Editable native-block starter page content
  includes/seo.php              Optional basic SEO metadata
  assets/admin.js               Native WordPress PDF media picker
  assets/blocks.js              Dynamic block editor previews; no build required
  assets/reports.css            Report styling, also available with other themes
tests/                       WordPress integration and browser tests
scripts/package.py           Installable theme/plugin ZIP creation
.github/workflows/wordpress.yml  Disposable WordPress verification environment
```

A block theme was selected because it makes important layout sections editable in the Site Editor. Report functionality lives entirely in the plugin and survives a theme change. The `[annual_reports limit="3"]` shortcode can display reports with another theme. Report detail links also have a content fallback for other themes. Deactivating/uninstalling the plugin does not delete report posts, attachments, or metadata; reactivate to access them again. Back up before intentional data removal.

## Updates and deployment

1. Back up the database and `wp-content/uploads` using your host's available backup/export tools or a manual database export plus file copy. No paid backup plugin is needed.
2. Apply code changes on staging, run the checks, and review desktop/mobile pages and wp-admin.
3. Build and upload the two ZIPs, choosing WordPress's **Replace current with uploaded** when updating an existing installation. Alternatively replace only the two project folders by SFTP. Preserve uploads, `wp-config.php`, other plugins, and the database.
4. If report URLs changed or return 404, save **Settings → Permalinks** once.
5. Verify the homepage, one report, PDF download, contact links, and the admin workflow. Roll back the two folders and database backup if necessary.

Updating theme files does not overwrite Site Editor template customizations stored in the database. Review customized templates deliberately if a template update needs to be adopted. Do not rerun starter setup as an update mechanism. Keep WordPress and the server runtime on supported security releases. Report publishing uses normal WordPress author/editor capabilities; give routine content editors an appropriate Editor account instead of sharing the administrator account.

## Verification

GitHub Actions provisions a disposable MySQL database and actual WordPress installation. It runs PHP syntax checks, JavaScript/JSON checks, setup/idempotency tests, publication validation, nonce/capability checks, REST rejection tests, and Playwright browser checks. Browser coverage includes page responses, internal links, PDF responses, year ordering, mobile navigation, keyboard access, desktop/tablet/mobile overflow, actual wp-admin PDF upload, and Gutenberg page editing. Screenshots and installers are attached to successful runs.

To run the integration test manually against a **disposable** installed WordPress site:

```bash
wp eval-file /absolute/path/to/tax14/tests/integration.php --path=/path/to/wordpress --user=YOUR_ADMIN_LOGIN
```

The test changes content and settings and is not for production. Browser tests use `SITE_URL`, `WP_TEST_USER`, and `WP_TEST_PASSWORD` environment variables, with disposable CI defaults. They require Playwright and Chromium; see the workflow for exact commands. These are development tools and are not installed on the production website.

Core Web Vitals and email/telephone routing depend on the actual host, uploaded assets, network, and entered contact details. The implementation is lightweight, but no production performance score is claimed without measurements on the deployed host.

## License

Project theme and plugin code are licensed under [GNU GPL version 2 or later](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html). WordPress retains its own GPL license. System fonts are used without bundling font files or requiring font-service accounts.
