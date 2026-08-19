# Contributing to feedteasers

Thank you for considering a contribution! *(Eine deutsche Kurzfassung findest du am Ende dieser Datei.)*

## This GitHub repository is a read-only mirror

Development happens on **Codeberg**. This GitHub repository is automatically mirrored from there and is
**read-only** — issues and pull requests opened here will not be reviewed and will be closed/redirected.

- Main repository: https://codeberg.org/chschmidt/grav-plugin-feedteasers
- Report a bug or request a feature: https://codeberg.org/chschmidt/grav-plugin-feedteasers/issues/new/choose
- Submit code changes: open a pull request against Codeberg

## Design goals (please keep these in mind for any change)

- Must stay installable via GPM without any manual steps by the end user.
- No external PHP dependencies (no third-party Composer packages). RSS/Atom parsing relies exclusively on
  built-in PHP extensions (`SimpleXMLElement`, `cURL` with a stream-context fallback).
- Must remain usable by site owners without IT/Twig knowledge (see the `[feedteasers]` shortcode).

If a change would require adding a Composer dependency or would break the shortcode/no-Twig-knowledge
use case, please open an issue first to discuss it before investing time in a PR.

## Before opening a pull request

1. **Target branch:** please branch from and target `main`. (`develop` is used internally for staging
   larger changes and isn't part of the external contribution workflow — you don't need to worry about it.)
2. **PHP version:** the plugin supports PHP >= 8.0 (see `composer.json`). Please avoid syntax or
   functions that require a newer PHP version unless you also raise the requirement in `composer.json`
   *and* discuss it in an issue first — this affects every user on shared/older hosting.
3. **Syntax check:** there is currently no automated lint/test step in CI for pull requests. Please run a
   PHP syntax check yourself on any changed PHP file before submitting:
   ```bash
   php -l path/to/changed-file.php
   ```
4. **Manual testing:** there is no automated test suite yet. Please briefly describe in the PR description
   how you tested your change (e.g. which Grav version, which feed type — RSS vs. Atom — you tried it
   against, and whether you tested both the Twig function and the `[feedteasers]` shortcode if relevant).
5. Keep changes focused — smaller, single-purpose PRs are much easier to review than large ones.

## Translations

Admin panel translations are managed via [Codeberg Translate](https://translate.codeberg.org/engage/grav-plugin-feedteasers/)
(a hosted Weblate instance), **not** through regular pull requests. If you'd like to add or
improve a translation, please use that web interface instead of editing `languages/*.yaml`
directly.

- Weblate pushes translation changes to a dedicated `translate` branch, not `main`. Maintainers
  periodically bring finished/sufficiently complete language files over to `main` by hand — as a
  translator you don't need to open a pull request or worry about branches yourself.

- The source/base language is `languages/en.yaml`. If you're adding a *new* translatable string
  (not just translating an existing one), it needs to be added there first as part of a regular
  code PR — only then does it show up in Weblate for translators to pick up.
- The top-level `name`/`description` fields in `blueprints.yaml` are intentionally **not** part
  of this translation setup (see `docs/ARCHITECTURE.md` for why) and stay as plain German text.

### Maintainers: the `translate` branch

Codeberg Translate is attached to a dedicated `translate` branch as its **repository branch**
(not merely its push branch) — this distinction matters: changing only the push-branch setting
would still leave Weblate's pull requests targeting `main`. Automatically generated Weblate
commits/PRs (e.g. when a new, initially empty language file is created for a requesting
translator) therefore land exclusively on `translate` and never touch `main` directly. They can
be left there indefinitely or closed without any risk to `main`.

This makes manual, file-based sync in both directions necessary. A periodic `git merge` between
`main` and `translate` was deliberately rejected (permanent divergence, cherry-picking too
costly):

- **`main` → `translate`:** whenever `languages/en.yaml` or `languages/de.yaml` changes on `main`
  (new strings, renamed keys), copy the file(s) over to `translate`, commit, and push — otherwise
  translators end up working against a stale source.
- **`translate` → `main`:** only sufficiently complete/correct language files are brought over
  individually (not the branch as a whole), by copying the file, committing, and pushing.
  **Before copying:** trigger *"Commit pending changes"* in Codeberg Translate's repository
  maintenance (and wait/refresh briefly) — otherwise the most recent translations already saved
  in the browser but not yet turned into a Git commit by Weblate would be missed.

The `translate` branch is intentionally never merged back into `main` as a whole via
`git merge`/pull request and diverges permanently.

## Configuration & code overview

- `feedteasers.php` — plugin events, caching, rendering
- `classes/FeedParser.php` — the dependency-free RSS 2.0 / Atom 1.0 parser (image resolution order,
  `cURL`/stream fallback, per-feed exception handling)
- `blueprints.yaml` — Admin panel form (labels/help/titles are translatable via `PLUGIN_FEEDTEASERS.*`
  keys in `languages/*.yaml`; the top-level `name`/`description` are **not** auto-translated by
  Admin/Admin Next, so they intentionally stay as plain German text, matching common practice for Grav
  core plugins)
- `templates/partials/feedteasers.html.twig` — output template

See [`docs/README.md`](docs/README.md) for the full contributor documentation index (architecture,
design decisions, security policy, code of conduct). For the end-user configuration reference, see
the [Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki).

## Release process (for context, maintainer-only)

Releases are tagged on Codeberg; the GitHub mirror publishes a matching GitHub Release automatically via
`.github/workflows/release-from-tag.yml`. You don't need to do anything here as a contributor — just
mention in your PR if you think a change warrants a version bump.

## License

This project is licensed under the MIT License. By submitting a pull request, you agree that your
contribution is provided under the same license.

---

## Auf Deutsch (Kurzfassung)

**Dieses GitHub-Repository ist nur ein Lese-Mirror.** Die eigentliche Entwicklung findet auf
[Codeberg](https://codeberg.org/chschmidt/grav-plugin-feedteasers) statt. Bitte Bugs/Feature-Wünsche und
Pull Requests dort einreichen.

**Übersetzungen:** Admin-Panel-Übersetzungen laufen über [Codeberg Translate](https://translate.codeberg.org/engage/grav-plugin-feedteasers/)
(Weblate), nicht über normale Pull Requests. Bitte dafür die Weblate-Oberfläche nutzen statt
`languages/*.yaml` direkt zu bearbeiten. Weblate pusht dabei auf einen eigenen Branch
`translate`, nicht auf `main` — als Übersetzer:in müssen Sie sich um Branches oder Pull Requests
nicht kümmern, die Maintainer übernehmen fertige Sprachdateien manuell nach `main`. Neue (noch nicht
vorhandene) Textbausteine müssen zuerst per regulärem Code-PR in `languages/en.yaml` (Basissprache)
landen, bevor sie in Weblate zur Übersetzung erscheinen.

**`translate`-Branch (nur für Maintainer):** Codeberg Translate ist als *Repository-Branch* (nicht
nur als Push-Branch) an `translate` angebunden, nicht an `main` — automatisch erzeugte
Weblate-Commits/PRs landen daher ausschließlich dort. Der Sync in beide Richtungen erfolgt bewusst
manuell/dateibasiert statt per `git merge`: Änderungen an `languages/en.yaml`/`de.yaml` auf `main`
müssen von Hand nach `translate` nachgezogen werden; umgekehrt werden nur fertige Sprachdateien
einzeln übernommen — vorher in Codeberg Translate „Commit pending changes“ auslösen, sonst fehlen
zuletzt im Browser gespeicherte, aber noch nicht committete Übersetzungen. Der Branch wird nie als
Ganzes zurückgemerged.

**Design-Ziele:** GPM-fähig ohne manuellen Eingriff, keine externen Composer-Abhängigkeiten (nur
eingebaute PHP-Erweiterungen), bedienbar auch ohne Twig-Kenntnisse (`[feedteasers]`-Shortcode). Bei
größeren Änderungen, die daran rütteln würden, bitte vorher ein Issue eröffnen.

**Vor einem Pull Request:**
- Ziel-Branch ist immer `main` (nicht `develop` — der ist intern für größere Umbauten).
- Unterstützt wird PHP >= 8.0 (siehe `composer.json`). Neuere PHP-Syntax bitte nur nach Rücksprache
  in einem Issue verwenden, das betrifft sonst Nutzer auf älteren/Shared-Hostings.
- Es gibt aktuell **keinen automatisierten Lint/Test-Schritt** in der CI. Bitte selbst `php -l` auf
  geänderten PHP-Dateien laufen lassen.
- Kurz in der PR-Beschreibung angeben, wie manuell getestet wurde (Grav-Version, RSS oder Atom, Twig-Funktion
  und/oder Shortcode).
- Lieber kleinere, fokussierte PRs als große Sammel-Änderungen.

**Lizenz:** MIT. Mit einem Pull Request stimmst du zu, dass dein Beitrag unter derselben Lizenz steht.
