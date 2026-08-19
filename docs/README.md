# Contributor Documentation Index

This is the starting point for anyone who wants to work on the `feedteasers` **code** — plugin
contributors and maintainers. If you're a site administrator looking for installation or
configuration help, see the
[Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki) instead; nothing here
duplicates that.

## Start here

- [`../CONTRIBUTING.md`](../CONTRIBUTING.md) — how to propose changes, the PR checklist, the
  translation workflow (including the maintainer-only `translate`-branch mechanics), and the
  design constraints any change needs to respect.
- [`ARCHITECTURE.md`](ARCHITECTURE.md) — how the plugin is built and why: file layout, the two
  integration paths (`{{ feed_teasers() }}` vs. `[feedteasers]`), SSRF protection, Admin-panel
  i18n, and notable past bugs.

## Policies

- [`../SECURITY.md`](../SECURITY.md) — how to report a vulnerability, supported versions, scope.
- [`../CODE_OF_CONDUCT.md`](../CODE_OF_CONDUCT.md) — community standards (Contributor
  Covenant 2.1).

## Project history

- [`../CHANGELOG.md`](../CHANGELOG.md) — released versions.

## Continuous integration

Every push is checked by Forgejo Actions on Codeberg (PHP/Twig/JS syntax) — see
[`../.forgejo/workflows/lint.yml`](../.forgejo/workflows/lint.yml). Not yet wired up for pull
requests from external contributors; see `CONTRIBUTING.md` for the current manual PHP-syntax-check
step to run instead.

---

## Auf Deutsch (Kurzfassung)

Ausgangspunkt für alle, die am Code arbeiten wollen (Contributor/Maintainer). Anwender-Doku
(Installation, Konfiguration, Einbindung, Fehlerbehebung) gibt es stattdessen im
[Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki) — hier keine Duplikate.

- [`../CONTRIBUTING.md`](../CONTRIBUTING.md) — Vorgehen für Änderungen, PR-Checkliste,
  Übersetzungs-Workflow (inkl. `translate`-Branch-Mechanik für Maintainer), Design-Vorgaben.
- [`ARCHITECTURE.md`](ARCHITECTURE.md) — Aufbau und Design-Entscheidungen.
- [`../SECURITY.md`](../SECURITY.md), [`../CODE_OF_CONDUCT.md`](../CODE_OF_CONDUCT.md) —
  Richtlinien.
- [`../CHANGELOG.md`](../CHANGELOG.md) — veröffentlichte Versionen.
- CI: [`../.forgejo/workflows/lint.yml`](../.forgejo/workflows/lint.yml) prüft jeden Push
  (PHP/Twig/JS-Syntax), aktuell noch nicht für externe Pull Requests aktiviert.
