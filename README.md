# Feed Teasers Plugin for Grav CMS

[![Latest Release](https://img.shields.io/github/v/release/chrisschm/grav-plugin-feedteasers)](https://codeberg.org/chschmidt/grav-plugin-feedteasers/releases) 
[![MIT-Lizenz](https://img.shields.io/badge/License-MIT-blue.svg)](https://de.wikipedia.org/wiki/MIT-Lizenz) 
[![Translation status](https://translate.codeberg.org/widget/grav-plugin-feedteasers/svg-badge.svg)](https://translate.codeberg.org/engage/grav-plugin-feedteasers/)  

**Feed Teasers** displays posts from external RSS or Atom feeds as clickable teaser cards
(thumbnail, title, text excerpt) on a [Grav CMS](https://getgrav.org) site. When multiple feeds
are configured, an optional tab switcher lets visitors switch between them. Clicking a card opens
the original article in a new browser tab.

The plugin has **no external PHP dependencies** — RSS/Atom parsing relies entirely on PHP's
built-in functions (`SimpleXMLElement`, `cURL` with a stream-context fallback).

## Installation

```
bin/gpm install feedteasers
```

No further steps (no `composer install`, no external libraries) are required. For manual/zip
installation, see the [Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki).

## Quick usage

On a page, in the Markdown editor (no code knowledge needed):

```
[feedteasers]
```

In a theme template, as a Twig function:

```twig
{{ feed_teasers() }}
```

Both accept optional parameters to override the configured defaults for that one call, e.g.
`[feedteasers show_tabs=false items_per_feed=3]` or
`{{ feed_teasers({'show_tabs': false, 'items_per_feed': 3}) }}`.

## Documentation

- **For site administrators:** the
  [Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki) is the full manual —
  installation, all configuration options, embedding/parameters, image resolution, and
  troubleshooting/FAQ.
- **For developers and contributors:** start at [`docs/README.md`](docs/README.md) — architecture,
  design decisions, and how to contribute.

## Links

- Report a bug or request a feature:
  [issue tracker](https://codeberg.org/chschmidt/grav-plugin-feedteasers/issues)
- [Security policy](SECURITY.md)
- [Contributing guide](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](CHANGELOG.md)
- Live demo: [jcs-net.de](https://www.jcs-net.de)

## License

MIT

---

## Auf Deutsch (Kurzfassung)

**Feed Teasers** zeigt Beiträge aus externen RSS-/Atom-Feeds als klickbare Teaser-Kacheln (Bild,
Titel, Textanriss) an; bei mehreren konfigurierten Feeds kann optional per Tab-Reiter zwischen
ihnen umgeschaltet werden. Ein Klick auf eine Kachel öffnet den Originalartikel in einem neuen
Browser-Tab. Das Plugin kommt **ohne externe PHP-Abhängigkeiten** aus.

**Installation:** `bin/gpm install feedteasers` (empfohlen), alternativ manuell/per Zip — siehe
[Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki).

**Verwendung:** `[feedteasers]` im Seiteninhalt (kein Twig-Wissen nötig) oder
`{{ feed_teasers() }}` im Template, jeweils mit optionalen Parametern zum Überschreiben der
Standardwerte.

**Dokumentation:** Ein vollständiges Anwender-Handbuch (Installation, alle
Konfigurationsoptionen, Einbindung, Fehlerbehebung/FAQ) gibt es im
[Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki). Entwickler-/
Contributor-Doku beginnt bei [`docs/README.md`](docs/README.md).

**Weitere Links:**
[Fehler melden](https://codeberg.org/chschmidt/grav-plugin-feedteasers/issues),
[Sicherheitsrichtlinie](SECURITY.md), [Mitwirken](CONTRIBUTING.md),
[Verhaltenskodex](CODE_OF_CONDUCT.md), [Changelog](CHANGELOG.md). Demo:
[jcs-net.de](https://www.jcs-net.de).

**Lizenz:** MIT.
