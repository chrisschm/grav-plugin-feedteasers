# Architecture

This document explains how the plugin is built and *why* certain decisions were made. It's aimed
at contributors who want to change code, not at end users configuring the plugin (see `README.md`
for that). *(Eine deutsche Kurzfassung findest du am Ende dieser Datei.)*

## Purpose

Renders RSS/Atom feeds as clickable teaser cards (thumbnail + text excerpt), with optional tab
switching between multiple feeds. Clicking a card opens the original article in a new tab.
Originally built to replace a WordPress feature on migration to Grav.

## Design goals

These apply to any future change (see also `CONTRIBUTING.md`):

- Must stay installable via GPM without any manual step by the end user.
- No external PHP dependencies — no third-party Composer packages. RSS/Atom parsing relies
  exclusively on built-in PHP extensions (`SimpleXMLElement`, `cURL`/stream fallback).
- Must remain usable by site owners without IT/Twig knowledge.

## File layout

```
user/plugins/feedteasers/
├── feedteasers.php                       # events, caching, rendering
├── feedteasers.yaml                      # default configuration
├── blueprints.yaml                       # Admin panel form
├── composer.json                         # metadata only, no third-party "require"
├── classes/FeedParser.php                # dependency-free RSS 2.0 / Atom 1.0 parser
├── languages/{de,en}.yaml                # Admin panel translations
├── templates/partials/feedteasers.html.twig
├── assets/{feedteasers.css,feedteasers.js}
└── images/fallback.png                   # generic placeholder image (188×188px)
```

## Two integration paths

1. `{{ feed_teasers() }}` — a Twig function (registered via `onTwigInitialized`), usable only
   inside actual theme templates.
2. `[feedteasers]` — bracket syntax directly in Markdown page content, for users without Twig
   knowledge. Optional parameters, e.g. `[feedteasers show_tabs=false items_per_feed=3]`.
   Implemented via `onPageContentProcessed` + `getRawContent()`/`setRawContent()` (replacement
   happens *after* Markdown processing), deliberately self-built instead of depending on the
   community "Shortcode Core" plugin.

Both paths call into the same rendering logic — if you change one, check whether the other needs
the same fix.

## Configurable options (Admin panel)

| Option | Purpose |
|---|---|
| `feeds` | List of name + feed URL |
| `cache_time` | Cache duration in seconds (Grav's own `Cache` class) |
| `items_per_feed` | Number of items per feed |
| `teaser_length` | Character length of the text excerpt (truncated server-side) |
| `show_tabs` | Toggle tab switching when multiple feeds are configured |
| `card_min_width` | CSS minimum card width (CSS custom property; validated against the regex `^[0-9.]+(px\|rem\|em\|%)$` — prevents CSS injection via the Admin field) |
| `image_fallback` | Fallback image: path/URL/Grav stream, default `plugin://feedteasers/images/fallback.png` |
| `request_timeout` | HTTP timeout for feed fetching |

If you add a new configurable option, it needs an entry in `blueprints.yaml`, a default in
`feedteasers.yaml`, and — if it's user-facing text — translation keys in `languages/*.yaml`
(see "Admin panel translations" below).

## Image resolution (`FeedParser.php`), fixed order

1. `<enclosure>` tag (RSS) with `type="image/..."`
2. `media:thumbnail` / `media:content` (Media RSS namespace)
3. First `<img>` tag in the HTML content (regex fallback)
4. Configured fallback image

Atom links require explicitly checking for `rel="alternate"` — otherwise the wrong link type can
be picked up. HTTP fetching uses `cURL` (preferred) or `file_get_contents` with a stream context
as a fallback. A single broken feed throws an exception that is caught (logged, empty array
returned) — it must never take the whole page down.

## Admin panel translations

Form labels, help texts, and tab titles in `blueprints.yaml` (`form.fields`) use
`PLUGIN_FEEDTEASERS.*` language keys, resolved via `languages/{de,en}.yaml`. Yes/No options use
Grav's built-in `PLUGIN_ADMIN.YES` / `.NO`.

Important exception: the blueprint's top-level `name` and `description` (visible in the plugin
overview and at the top of the configuration dialog) are **not** auto-translated by Admin/Admin
Next, unlike `label`/`help`/`title` inside `form.fields`. `description:` therefore intentionally
stays as plain German text in `blueprints.yaml`, matching common practice in Grav core plugins,
rather than referencing a language key.

Known gap: the frontend string "Aktuell keine Beiträge verfügbar." in
`templates/partials/feedteasers.html.twig` is not yet translatable (frontend/user-facing scope,
deliberately out of scope for the admin-i18n work so far).

**Translation contributions:** since these language files are simple key/value YAML, they're a
good fit for community translation via [Codeberg Translate](https://translate.codeberg.org/engage/grav-plugin-feedteasers/)
(hosted Weblate), configured with `languages/en.yaml` as the source language. This only covers the
`PLUGIN_FEEDTEASERS.*` keys resolved through `form.fields` — it does **not** cover the
non-translated top-level `name`/`description` described above, since those aren't part of the
Weblate component's scope. See `CONTRIBUTING.md` for the contributor-facing workflow.

## Notable past bugs (useful context before touching related code)

1. **Missing `onTwigTemplatePaths`** → Twig crash on direct `{% include %}`.
   Fix: `$this->grav['twig']->twig_paths[] = __DIR__ . '/templates';`
2. **`mergeConfig(null, true)` → TypeError** on current Grav versions (strict typing, `null` no
   longer accepted for `$page`). Fix: direct, page-independent config access instead of
   `mergeConfig()`.
3. **Missing `url()` around the fallback image** — found in the already-published GPM release
   v1.0.1. A `plugin://` stream string ends up verbatim in the `src` attribute without a `url()`
   call → broken image. **Fixed**: the URL is now resolved through `url()` before being used, and
   an explicit check was added around this. See `CHANGELOG.md` for the release it landed in.

## Live status (at time of writing)

Version 1.0.5 is live on the official Grav GPM. See `CHANGELOG.md` for the current released
version and `README.md` for user-facing configuration docs. This file describes architecture and
rationale, not release status — please keep it in sync when the design changes, but don't
duplicate version numbers here.

---

## Auf Deutsch (Kurzfassung)

Diese Datei richtet sich an Contributor, die am Code arbeiten wollen (Endnutzer-Doku steht in
`README.md`). Kernpunkte: keine externen Composer-Abhängigkeiten, GPM-fähig ohne Nutzereingriff,
bedienbar ohne Twig-Kenntnisse. Zwei Einbindungswege (`{{ feed_teasers() }}` und
`[feedteasers]`-Shortcode) nutzen dieselbe Rendering-Logik — bei Änderungen an einem Weg prüfen,
ob der andere ebenfalls betroffen ist.

Die Bildermittlung in `FeedParser.php` folgt einer festen Reihenfolge (`<enclosure>` →
Media-RSS-Namespace → erstes `<img>` im HTML → konfiguriertes Fallback-Bild), Atom-Links brauchen
eine explizite `rel="alternate"`-Prüfung. Ein einzelner kaputter Feed darf nie die ganze Seite
zum Absturz bringen (Exception wird abgefangen, geloggt, leeres Array zurückgegeben).

Die oberste Ebene von `blueprints.yaml` (`name`/`description`) wird von Admin Next **nicht**
automatisch übersetzt — bewusst als deutscher Klartext belassen, nur Felder innerhalb von
`form.fields` nutzen Sprachschlüssel. Übersetzungen dieser Sprachschlüssel laufen über
[Codeberg Translate](https://translate.codeberg.org/engage/grav-plugin-feedteasers/)
(Basissprache: `languages/en.yaml`) — Details zum Contributor-Workflow stehen in
`CONTRIBUTING.md`, nicht hier.

Drei dokumentierte Altbugs (fehlendes `onTwigTemplatePaths`, `mergeConfig(null, true)`-TypeError,
fehlendes `url()` um das Fallback-Bild) sind im Abschnitt "Notable past bugs" oben als Kontext
festgehalten. Alle drei sind mittlerweile behoben, u. a. wurde beim Fallback-Bild inzwischen eine
explizite URL-Prüfung ergänzt — bei Änderungen an verwandtem Code trotzdem `CHANGELOG.md`
gegenprüfen, falls sich der Stand seither weiterentwickelt hat.
