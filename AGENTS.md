# Copilot Instructions for sunnah-web

## Project Overview

Frontend for [sunnah.com](https://sunnah.com), a hadith collection website built on the **Yii 2** PHP MVC framework. Serves multilingual hadith content (Arabic, English, Urdu, Bangla, Bosnian, Indonesian) organized by collections, books, and chapters, plus a narrator (rijal) browsing module.

## Content Rules

All user-facing text — pages, error messages, view templates, new collections, narrator data — **must be Islamically appropriate**. No haram references (no boyfriend/girlfriend, no music/dating references, etc.). Use halal alternatives. This is non-negotiable for any change here.

## Architecture

### Request flow

1. Web server points to `public/` → `public/index.php` bootstraps Yii 2.
2. URL routing lives in `application/config/main.php` (extensive pattern-based rules — order matters, see Gotchas).
3. All frontend logic lives in a single Yii module: `application/modules/front/`.
4. Backing data: two MySQL databases (`db` = main `hadithdb`, `searchdb` = query logs) + Elasticsearch for full-text search.

### Key directories

- `application/config/main.php` — central config: routes, DB connections, log targets, modules, Memcached.
- `application/modules/front/controllers/` — four controllers: `Index`, `Collection`, `Narrator`, `Search`.
- `application/modules/front/models/` — domain models (`Hadith`, `Book`, `Collection`, `Chapter`, `Narrator`, `HadithMatch`, `Util`, `ContactForm`) and language-specific hadith subclasses (`EnglishHadith`, `ArabicHadith`, `UrduHadith`, `BanglaHadith`, `BosnianHadith`, `IndonesianHadith`).
- `application/modules/front/models/data/narrator_maps.php` — curated Arabic→English maps for narrator pages (`word_dict`, `jarh_tadil`, `residence`, `profession`, `descriptor`).
- `application/modules/front/views/` — view templates organized by controller; `views/layouts/` holds the page chrome (`main`, `home`, `nav_menu`, `side_panel`, `searchbox`, `suite`, `_posthog`).
- `application/components/` — CDN caching helpers and the search-engine abstraction.
- `application/controllers/SController.php` — shared base controller (breadcrumbs, page title, `auto_version()`).
- `public/` — web root with static assets (CSS, JS, images) **plus a handful of bare-PHP files** that bypass Yii (`share.php`, `report.php`, `processer.php`, `recaptchalib.php`, `search_redirect.php`) — see Gotchas.
- `db/` — SQL seed files (`00-samplegitdb.sql`, `01-hadithTable.sql`, `02-collections.sql`, `03-bookdata.sql`) mounted into the MySQL container by `docker-compose.yml`.

### Hadith model hierarchy

- `Hadith extends ActiveRecord` is the base; per-language subclasses each override `tableName()` (e.g. `{{EnglishHadithTable}}`, `{{ArabicHadithTable}}`) and implement `process_text()` for language-specific text munging (PBUH → ﷺ image/glyph, sanad markup, paragraph breaks).
- `EnglishHadith` and `ArabicHadith` additionally override `populateReferences()` and `populatePermalink()` — these produce `canonicalReference`, `inbookReference`, `translationReference`, `sunnahReference`, `arabicReference`, and `permalink`. View templates read those properties directly.
- `ArabicHadith` parses shortcodes via `Thunder\Shortcode` (`[matn]`, `[prematn]`, `[postmatn]`, `[commentary]`, `[narrator id=…]`, `[quran sura=…]`) — sets `shortcode_parsed = true` when applied.
- `Book::fetchHadith()` returns `[englishEntries, arabicEntries, pairs, lastUpdated]`; collection views depend on this exact shape.
- Hadith display code expects processed model instances. If manually hydrating hadith rows outside the usual `Util` / `Book` helpers, call `process_text()` and `populate($util, $collection, $book)` before rendering.
- Adding a new language = new `XYZHadith extends Hadith`, new DB table, new entry in `Book::fetchLangHadith()`'s switch, new column in `BookData` (`xyzBookID`, `xyzBookNum`, `xyzBookName`), new option in `views/layouts/side_panel.php`, and a `langLoaded[xyz]` entry in `public/js/sunnah.js`.

### URNs are the universal hadith ID

- `englishURN` and `arabicURN` are integer primary keys per hadith and the **only stable identifiers** for cross-referencing.
- `matchtable` (model: `HadithMatch`) maps `arabicURN ↔ englishURN`.
- Canonical fallback permalink is `/urn/<n>`; the colon form `/<collection>:<hadithNumber>` is the user-facing canonical URL when the book is verified.

### Book.status is semantic, not a workflow flag

- `status == 4` → fully verified collection: bold reference numbers, uses `canonicalReference` + `inbookReference`, may have a `reference_template`.
- `status == 6` → single-column display (used by Musnad Ahmad — `.col1` CSS class in `AllHadith`).
- `status < 4` → "in progress"; references rendered as `englishReference` / `arabicReference` / `sunnahReference` and shown non-bold (provisional).
- Many view templates and `EnglishHadith::populateReferences()` / `ArabicHadith::populateReferences()` branch on this field.

### Util is the cached-lookup god-class

Always call `Util` (`new Util()` or `$this->util` in `SController`) instead of writing direct queries:

- `getCollection($name)` — single `Collection` row, cached as `collection:<name>`.
- `getBook($collection, $bookID = null, $language = null)` — full book list or a single `Book`, cached as `<col>books`, `<col>books_arabic`, `<col>books_english`.
- `getChapter($col, $bookID, $babID)` / `getChapterDataForBook(...)` — cached as `chapter:<col>_<book>_<bab>` / `chapters:<col>_<book>`.
- `getHadith($urn, $language)` — cached as `<lang>urn:<urn>`; runs `process_text()` + `populate()` before caching.
- `getCollectionsInfo($mode, $display_only)` — cached as `collectionsInfo_<bool>`.
- `getHadithCount()`, `getNextURNInCollection`, `getPreviousURNInCollection`, `getURNByNumber`, `getVerifiedHadithNumber`, `get_permalink`, `customSelect`, `getCarouselHTML`, `getRamadanURNs`/`getDhulhijjahURNs`/`getMuharramURNs`, `getMatchingUrns`.

Follow the same `<scope>:<key>` cache-key shape if you add new helpers. Narrator caches are versioned (`narrator:hadith_clusters:v2:…`) — bump the version when the shape changes.

### CDN / caching components

Three layers, picked per route:

- **`CdnHeaders`** (`application/components/CdnHeaders.php`) — low-level helper. Sets `Cache-Control`, `Cloudflare-CDN-Cache-Control`, `Cache-Tag: sunnah` (configurable), and **strips the CSRF cookie** (`$response->cookies->remove(csrfParam, false)`). Stripping is critical: an emitted `Set-Cookie` defeats the Cloudflare edge cache.
- **`CdnEdgeCache extends ActionFilter`** — headers only, no origin caching. Used by `SearchController` (Memcached fragmentation on free-form queries hurts more than it helps; Cloudflare's URL-based key is sufficient).
- **`CdnOriginAndEdgeCache extends yii\filters\PageCache`** — Yii origin page cache **plus** Cloudflare headers. Used by `IndexController`, `CollectionController`, `NarratorController`.

All cacheable routes share the tag `sunnah`, so one Cloudflare **Purge by Tag** (or `purge_everything` on non-Enterprise plans) invalidates the whole site.

When adding request-dependent behavior to origin-cached routes, account for Yii `PageCache` `variations` so cached output cannot bleed across request states.

POST endpoints (`contact`, `ajaxhadithcount`, `flush-cache`, `selection-data`, narrator AJAX list/cluster routes) must be listed in each controller's `behaviors()['except']` array — otherwise the cache header logic will strip their CSRF cookie and break form submission.

### Search

Flow: `SearchController::actionSearch` → `KeywordSearchEngine` (dispatcher) → `EnglishKeywordSearchEngine` (only impl) → `ElasticConnection::sendRequest("/english/search?q=…&size=…&from=…&collection=…&mode=…")` → `SearchResultset`.

- ES hits carry the matched payload under `hit._source.en` / `hit._source.ar`. **Field names inside those payloads are the same as `EnglishHadith` / `ArabicHadith` property names** — that's a cross-system contract maintained with the search indexer (`search/main.py` in the sibling repo). Renaming model properties breaks search hydration.
- Collection/book metadata for hydration still comes from `Util` (DB-cached), not ES.
- Suggestions ("did you mean") come from `hit.suggest.english[0].options` (fallback `suggest.arabic[0].options`).
- Query logging writes to `searchdb.search_queries` only when `YII_ENV !== 'dev'`.

### Narrator subsystem

Substantial newer module. Backed by `Narrators`, `narrator_ahadith`, `clusters` tables, plus FK to `ArabicHadithTable.gk_hadith_id` and `Collections.gk_collection_id`.

- All narrator SQL uses `MAX_EXECUTION_TIME(3000)` hints — keep new queries within that budget.
- AJAX endpoints: `/narrator/<nid>/hadith/list` (partial render of `_hadith_results`) and `/narrator/<nid>/hadith/cluster/<clusterId>` (partial render of `_hadith_cluster_links`). Driven by inline JS registered in `views/narrator/hadith.php`.
- Arabic→English translation uses `narrator_maps.php` dictionaries with a character-level fallback (`transliterateArabicName`); structural particles (`بن`, `أبو`, `بنت`, `مولى`, `ذو`, `أم`, `عبد ال…`) are handled explicitly.
- All narrator-page styles are scoped under `.narrator-page` in `public/css/narrator.css` — keep them scoped, and do **not** add `.narrator-page` rules from elsewhere.
- Material Symbols and Newsreader webfonts load only when `_pageType === "narrator"` (see `views/layouts/main.php`).

## Configuration

Environment config is in `.env.local` (copy from `.env.local.sample`). It's an INI file parsed in `application/config/main.php` and `public/index.php`. Key vars:

- `yiiPath` — **path to the directory containing `vendor/yiisoft/yii2/`**. Both `public/index.php` (autoload) and `main.php` (vendor path) read this. Wrong value = blank 500. Yii is loaded from `$yiiPath/vendor`, **not** the project's own `vendor/`.
- `MYSQL_HOST` / `MYSQL_DATABASE` / `MYSQL_USER` / `MYSQL_PASSWORD` — main `db` connection.
- `searchdb_*` — search-log connection.
- `elastic_host` / `elastic_port` / `solr_username` / `solr_password` — ES connection (the `solr_*` names are historical).
- `cacheHost` / `cachePort` / `cacheTTL` / `searchCacheTTL` — Memcached for prod (dev uses `DummyCache`).
- `cookieValidationKey` — required by Yii for cookie validation.
- `recaptcha_v3_site_key` / `recaptcha_v3_secret_key` (used by Yii contact form) and `recaptcha_private_key` (used by `processer.php`, v2).
- `smtpServer` / `smtpUser` / `smtpPassword` / `smtpPort` — outbound mail.
- `classy_campaign_id` — donation widget on `/donate`.
- `showCarousel` — `ramadan` | `dhulhijjah` | `ashura` to enable the homepage seasonal carousel.

## Development Setup

### Docker (recommended)

```bash
cp .env.local.sample .env.local
docker-compose up --build
```

`db/0[0-3]*.sql` are mounted into the MySQL container's `/docker-entrypoint-initdb.d/` and run on first boot. Access at `http://localhost`.

### Windows (native)

Requires PHP 8.3+ (Dockerfile uses 8.5; `composer.json` minimum is `>=8.3.0`), IIS, MySQL, Composer. Run `composer install` in the project root. Point IIS to `public/` with `index.php` as default document and a `.php` handler mapping.

## Common Tasks

- **Add a public page** — add a URL rule in `application/config/main.php` (above the catch-all `<collectionName:\w+>` and `<collectionName:.*>`), add the action method to the appropriate controller (`IndexController` for sitewide pages), add a view in `views/<controller>/<action>.php`. If GET-only, leave `CdnOriginAndEdgeCache` to handle it; if POST, add the action ID to the filter's `except` list.
- **Add a hadith collection** — INSERT into `Collections`, populate `BookData`, populate `EnglishHadithTable` + `ArabicHadithTable` (+ `matchtable` for cross-refs), and add any special-case routes if the URL pattern is non-standard (see Gotchas).
- **Add a translation language** — see "Hadith model hierarchy" above.
- **Flush all caches** — `/yiiadmin/flushcache` (Yii cache only) + Cloudflare Purge by Tag `sunnah` (or `purge_everything`).
- **Inspect 404s** — separate log file `runtime/logs/404.log` (not the main `app.log`).
- **Inspect hadith-count mismatch warnings** — `runtime/logs/hadithcount.log` (posted from `views/collection/dispbook.php` via `/ajax/log/hadithcount`).

## Code Formatting

`php-cs-fixer` is mentioned in the README but **no `.php-cs-fixer.dist.php` is checked in** — formatting is currently aspirational/manual. Match the surrounding style (tabs in older files, 4-space in newer narrator code).

## Testing

There is **no working test runner in this repo**:

- `.travis.yml` still references PHP 5.6 and an `application/tests/phpunit.xml` that no longer exists.
- `composer.json` lists Codeception in `require-dev`, but `application/tests/` is absent.

The global "every code change must have a unit test" rule (in dev-repo `AGENTS.global.md`) **cannot be honored** here until Codeception is scaffolded. When making a change:

1. At minimum run `php -l` on changed PHP files when possible.
2. Test manually in the Docker container (`docker-compose up --build`) and exercise the affected routes.
3. Note the gap in the PR description.
4. If introducing significant new logic (e.g. another `Util` helper, a new search engine, more narrator translation), strongly consider adding a Codeception unit suite at the same time.

## Conventions

- All user-facing routes live in `application/config/main.php`. Order matters — see Gotchas.
- Hadith permalinks use the colon URL pattern `<collectionName>:<hadithNumber>` (e.g. `/bukhari:1`) for verified books; `/urn/<n>` is the URN-based fallback.
- Per-language hadith models follow the `XYZHadith extends Hadith` pattern with `tableName() === '{{XYZHadithTable}}'`.
- Cache keys follow `<scope>:<identifier>` (e.g. `collection:bukhari`, `arabicurn:104350`, `narrator:hadith_clusters:v2:narrator:7:collections:1-2:links:7:clusters:30:offset:0`). Bump the `v<n>` suffix when the cached value shape changes.
- Controllers commonly set `$this->view->params['_pageType']`, breadcrumbs via `$this->pathCrumbs(...)`, and optional metadata such as `_ogDesc`, `book`, `collection`, and `lastUpdated`; keep these values current when adding pages or display modes.
- View partials live next to their parent view and are prefixed with `_` (e.g. `_hero.php`, `_hadith_results.php`, `_hadith_cluster_links.php`). Use `$this->renderPartial(...)` for AJAX endpoints.
- Views output a mix of trusted stored HTML and escaped user-facing values. Escape new dynamic values with `htmlspecialchars()` or Yii helpers unless the value is intentionally pre-rendered HTML.
- Prefer parameter binding, Yii query builders, or ActiveRecord conditions for new database access. Existing raw SQL exists mostly in utility/cache paths; do not expand it casually.
- CSS asset cache-busting: wrap CSS/JS paths in `$this->context->auto_version(...)` — appends `?ver=<mtime>` for files under `DOCUMENT_ROOT` (no-op in `YII_DEBUG` mode or for non-rooted paths).

## Third-Party Integrations (in `views/layouts/`)

Loaded on most pages:

- Google Analytics (`G-PD11DFYVJC`).
- **PostHog** (`views/layouts/_posthog.php`) — session recording **enabled** with `maskAllInputs: false`. Privacy-sensitive; do not log secrets, account state, or anything else you wouldn't want replayed.
- StatCounter (project `7148282`).
- jQuery 3.5.1, jQuery UI 1.12.1, Font Awesome 6.5.1.
- Classy donation widget (only on `/donate`).
- Google reCAPTCHA: v3 for the Yii `ContactForm` (`kekaadrenalin/yii2-module-recaptcha-v3`), v2 for the bare `public/report.php` → `processer.php` flow.

## Gotchas

- **Route ordering** — the catch-alls `<collectionName:\w+>` and `<collectionName:.*>` are at the bottom of the `rules` array in `main.php` and will swallow anything appended below them. New routes go *above* the catch-alls.
- **Special-cased collections** — `nawawi40`, `qudsi40`, `shahwaliullah40` all rewrite to `collectionName=forty` with different `ourBookID`s; `hisn` and `virtues` ignore `ourBookID`; `nasai/35b` and `shamail/8b` use negative `ourBookID` (`-35`, `-8`); `<collection>/introduction` uses `ourBookID = -1`. Don't accidentally shadow these.
- **Legacy in-controller redirects** — `CollectionController::handleOldRiyadussalihinLinks()` issues 301s for the old Riyadus-Salihin book numbering. New legacy mappings should follow that pattern.
- **CSRF cookie strip** — `CdnHeaders::attach()` removes the CSRF cookie on cacheable 200/301 responses. Any new POST endpoint must be in the `except` list of its controller's `behaviors()['class' => 'app\components\Cdn…']` block, otherwise its form submission breaks.
- **Bare-PHP public files** — `public/share.php`, `report.php`, `processer.php`, `search_redirect.php` **do not go through Yii**. They `parse_ini_file('.env.local')` directly, use `mysqli_*` and the unmaintained PEAR `Mail` package, and have hand-rolled IP / input handling. Prefer adding new endpoints as Yii controller actions. If you must touch these files, double-check input sanitization and don't introduce new dependencies on them.
- **`auto_version()` is path-sensitive** — only rewrites paths that start with `/` AND exist under `$_SERVER['DOCUMENT_ROOT']`. Relative or external URLs pass through unchanged. Don't rely on it for asset versioning in absolute-URL contexts.
- **`$yiiPath` mismatch = blank 500** — Yii is loaded from `$yiiPath/vendor`, not the project's local `vendor/`. After `composer install` you still need a separate Yii install referenced by `.env.local`.
- **Narrator CSS scope** — `public/css/narrator.css` is namespaced under `.narrator-page`. Don't add narrator-page rules from other CSS files, and don't add `.narrator-page` class to non-narrator templates (it pulls in heavy custom-property overrides and dark-mode redefinitions).
- **`.travis.yml` is stale** (PHP 5.6, missing tests path) — don't trust it as a build reference.
- **`composer.lock` may pin different versions than `composer.json` describes** — `composer install` reproduces the lock; only run `composer update` when intentionally bumping deps.

## Search Engine Contract (with `sunnah-com/search`)

The sibling `search` repo indexes hadith into Elasticsearch with `_source` documents whose keys mirror `EnglishHadith` / `ArabicHadith` property names. Renaming or removing a property here without coordinating with `search/main.py`'s SELECT will silently break search-result rendering (records will be skipped with a `Yii::warning("Search hit [...] missing _source....")`). Any model-shape change should be paired with a search reindex.
