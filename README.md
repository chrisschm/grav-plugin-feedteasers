# Feed Teasers Plugin for Grav CMS

Zeigt Beiträge aus externen RSS- oder Atom-Feeds als klickbare Teaser-Kacheln
an (Mini-Teaserbild, Titel, Textanriss). Bei mehreren konfigurierten Feeds
kann optional per Tab-Reiter zwischen ihnen umgeschaltet werden. Ein Klick
auf eine Kachel öffnet den Originalartikel in einem neuen Browser-Tab.

Das Plugin kommt **ohne externe PHP-Abhängigkeiten** aus – RSS/Atom-Parsing
läuft komplett über in PHP eingebaute Funktionen (`SimpleXMLElement`, `cURL`
bzw. Stream-Kontext als Fallback).

## Installation

### GPM (empfohlen)

```
bin/gpm install feedteasers
```

### Manuell / per Zip

1. Diesen Ordner nach `user/plugins/feedteasers` kopieren.
2. Grav-Cache leeren: `bin/grav clear-cache`.
3. Plugin im Admin-Panel unter *Plugins* aktivieren (ist standardmäßig aktiv).

Es sind **keine weiteren Schritte** (kein `composer install`, keine externen
Bibliotheken) notwendig.

## Konfiguration

Im Admin-Panel unter *Plugins → Feed Teasers*:

- **Feeds**: Liste aus Name + Feed-URL (RSS 2.0 oder Atom 1.0).
- **Cache-Dauer**: wie lange ein Feed zwischengespeichert wird, bevor er neu
  geladen wird.
- **Mindestbreite einer Kachel**: CSS-Wert (z.B. `260px`, `18rem`). Bestimmt
  indirekt, wie viele Kacheln pro Zeile nebeneinander passen, bevor umgebrochen
  wird. Ungültige Werte werden ignoriert und durch `260px` ersetzt.
- **Beiträge pro Feed / Textlänge / Tabs / Fallback-Bild / Timeout**: siehe
  Formular-Beschreibungen.

> **Hinweis zur Textlänge:** Der Textanriss wird zusätzlich zur Zeichenzahl
> per CSS auf 3 Zeilen begrenzt (`-webkit-line-clamp`). Bei schmalen Kacheln
> wird der Text also unter Umständen schon durch die Zeilenbegrenzung gekappt,
> bevor die konfigurierte Zeichenzahl überhaupt erreicht wird. Wer mehr Text
> sehen möchte, sollte zuerst die Kachelbreite erhöhen oder die
> Zeilenbegrenzung in `assets/feedteasers.css` anpassen.

## Verwendung

### Auf einer Seite (Markdown-Editor, kein Code-Wissen nötig)

Einfach in den Seiteninhalt schreiben:

```
[feedteasers]
```

Optional mit Parametern für genau diesen Aufruf:

```
[feedteasers show_tabs=false items_per_feed=3]
```

Das funktioniert ohne jede weitere Einstellung, insbesondere ohne die
"Process Twig"-Option auf der Seite zu aktivieren.

### In einer Theme-Template-Datei

Als Twig-Funktion, z.B. in `sidebar.html.twig` oder `base.html.twig`:

```twig
{{ feed_teasers() }}
```

Optional mit Überschreibungen für diesen einen Aufruf:

```twig
{{ feed_teasers({'show_tabs': false, 'items_per_feed': 3}) }}
```

## Bildermittlung

Für das Teaserbild wird in dieser Reihenfolge geprüft:

1. `<enclosure>`-Tag (RSS) mit `type="image/..."`.
2. `media:thumbnail` / `media:content` (Media-RSS-Namespace).
3. Erstes `<img>`-Tag im HTML-Inhalt des Beitrags.
4. Konfiguriertes Fallback-Bild, falls gesetzt (Standard: das mitgelieferte
   `images/fallback.png`, aufgelöst über den `plugin://`-Stream). Leeres Feld
   im Admin-Panel = kein Fallback-Bild, die Kachel bleibt dann ohne Bildbereich.

## Fehlerverhalten

Ein einzelner nicht erreichbarer oder fehlerhafter Feed führt nicht zum
Abbruch der gesamten Ausgabe – er wird übersprungen und der Fehler ins
Grav-Log geschrieben.

### Fehler melden

Fehler können über den [Bugtracker auf Codeberg](https://codeberg.org/chschmidt/grav-plugin-feedteasers/issues) zurückgemeldet werden.

## Demo

Eine aktive Installation kann auf der Startseite auf [JCS-Net.de](https://www.jcs-net.de) betrachtet werden.

## Dokumentation

Eine Dokumentation befindet sich im Aufbau: [Wiki](https://codeberg.org/chschmidt/grav-plugin-feedteasers/wiki)

## Lizenz

MIT
