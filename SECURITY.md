# Security Policy

*(Eine deutsche Kurzfassung findest du am Ende dieser Datei.)*

## Reporting a vulnerability

Please **do not** report security vulnerabilities through public GitHub or Codeberg issues,
discussions, or pull requests. Codeberg/Forgejo does not currently offer a private vulnerability
reporting feature comparable to GitHub's, so please report privately by email instead:

**security@jcs-net.de**

Please include, as far as you can:

- A description of the vulnerability and its potential impact
- Steps to reproduce (affected plugin version, Grav version, PHP version, feed type if relevant)
- Any proof-of-concept code or example feed/payload that triggers the issue

You should receive an acknowledgement within a few days. This is a small, solo-maintained
open-source project without a dedicated security team, so please allow reasonable time for a fix
before any public disclosure. I'll coordinate a disclosure timeline with you once the report is
confirmed.

## Supported versions

Only the latest released version of the plugin (as published via GPM) is supported with security
fixes. Please make sure you're on the current version before reporting, and update to the fixed
version as soon as a patch is released.

## Scope

This plugin fetches and parses external RSS/Atom feeds without third-party dependencies. Reports
particularly welcome around:

- XML parsing issues (e.g. XXE, entity expansion) in `classes/FeedParser.php`
- Unsafe handling of feed content when rendered in templates (e.g. XSS via feed titles/descriptions)
- Handling of the `card_min_width` admin field or other user-configurable values that end up in
  CSS/HTML output

General Grav core or hosting/infrastructure vulnerabilities are out of scope here — please report
those to the Grav project or your hosting provider directly.

---

## Auf Deutsch (Kurzfassung)

**Sicherheitslücken bitte nicht** als öffentliches Issue auf GitHub oder Codeberg melden, sondern
per E-Mail an **security@jcs-net.de**. Bitte möglichst mit Beschreibung, Auswirkung,
Reproduktionsschritten (Plugin-/Grav-/PHP-Version, Feed-Typ) und ggf. einem Proof-of-Concept.

Unterstützt wird nur die jeweils aktuelle, über GPM veröffentlichte Version. Da es sich um ein
Solo-Projekt ohne dediziertes Security-Team handelt, bitte etwas Zeit für einen Fix einplanen,
bevor öffentlich darüber gesprochen wird — ich melde mich zeitnah zurück und stimme einen
Offenlegungszeitpunkt mit dir ab.
