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
2. **PHP version:** the plugin supports PHP >= 7.4 (see `composer.json`). Please avoid syntax or
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

## Configuration & code overview

- `feedteasers.php` — plugin events, caching, rendering
- `classes/FeedParser.php` — the dependency-free RSS 2.0 / Atom 1.0 parser (image resolution order,
  `cURL`/stream fallback, per-feed exception handling)
- `blueprints.yaml` — Admin panel form (labels/help/titles are translatable via `PLUGIN_FEEDTEASERS.*`
  keys in `languages/*.yaml`; the top-level `name`/`description` are **not** auto-translated by
  Admin/Admin Next, so they intentionally stay as plain German text, matching common practice for Grav
  core plugins)
- `templates/partials/feedteasers.html.twig` — output template

See the plugin's own documentation/README for the full list of configuration options.

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

**Design-Ziele:** GPM-fähig ohne manuellen Eingriff, keine externen Composer-Abhängigkeiten (nur
eingebaute PHP-Erweiterungen), bedienbar auch ohne Twig-Kenntnisse (`[feedteasers]`-Shortcode). Bei
größeren Änderungen, die daran rütteln würden, bitte vorher ein Issue eröffnen.

**Vor einem Pull Request:**
- Ziel-Branch ist immer `main` (nicht `develop` — der ist intern für größere Umbauten).
- Unterstützt wird PHP >= 7.4 (siehe `composer.json`). Neuere PHP-Syntax bitte nur nach Rücksprache
  in einem Issue verwenden, das betrifft sonst Nutzer auf älteren/Shared-Hostings.
- Es gibt aktuell **keinen automatisierten Lint/Test-Schritt** in der CI. Bitte selbst `php -l` auf
  geänderten PHP-Dateien laufen lassen.
- Kurz in der PR-Beschreibung angeben, wie manuell getestet wurde (Grav-Version, RSS oder Atom, Twig-Funktion
  und/oder Shortcode).
- Lieber kleinere, fokussierte PRs als große Sammel-Änderungen.

**Lizenz:** MIT. Mit einem Pull Request stimmst du zu, dass dein Beitrag unter derselben Lizenz steht.
