# Website gestalten und Ticketshop betreiben

Ab Version 1.2 lässt sich die vom Plugin gestaltete Website unter **Einstellungen → Rommelfangen Concerts** bearbeiten. Immer im Dashboard der Konzertwebsite arbeiten, z. B. rommelfangen.de. Alle Einstellungen sind websitebezogen und bleiben bei `git pull`, Deaktivierung und Themewechsel erhalten.

## Nach dem Update

```sh
cd /var/www/html/wp-content/plugins/wohnzimmerkonzerte
git pull --ff-only
```

Website-/CDN-Cache leeren, falls einer aktiv ist. Die bestehende Startseite mit `[rommelfangen_homepage]` weiterverwenden. Kein Neuimport, keine erneute Aktivierung und kein Kopieren von Theme-Dateien nötig. Vorhandene Social-/Gutschein-Links, Konzerte, Bilder und Ticketprodukt-Zuordnungen bleiben erhalten. Die neuen Gestaltungsfelder erhalten zunächst Standardwerte.

## Marke und Erscheinungsbild

Unter **Marke & Erscheinungsbild** ändern:

- Name, Untertitel und Footer-Text.
- Logo und allgemeines Ersatzbild aus der WordPress-Mediathek. „Entfernen“ stellt die jeweilige Text-/Bildvorgabe wieder her.
- Hintergrund-, Flächen-, Text-, Sekundärtext-, Akzent-, Button- und Linienfarben. Bei hellen Hintergründen dunkle Schrift und eine passende Button-Textfarbe wählen.
- Überschriftenschrift, Grundschriftgröße, Seitenbreite, Eckenradius und Bereichsabstände.
- Bildausschnitt oder vollständiges Bild, Format und Neigung des großen Highlight-Bildes.
- Helle oder dunkle Darstellung nativer Formularfelder und Datumsanzeige.

Die Schriftarten sind lokal verfügbare Systemschriften; das Plugin lädt keinen externen Schriftendienst.

Mit **Eigenes Design auch für Warenkorb und Kasse verwenden** erhalten auch diese WooCommerce-Seiten den Header, Footer und die Farben der Konzertwebsite. Die eigentlichen Formulare bleiben die normalen WooCommerce-Formulare einschließlich Gutscheinen und Zahlungsanbindungen. Die Option kann ausgeschaltet werden, wenn Warenkorb und Kasse weiter vom vorhandenen Theme gestaltet werden sollen. Normale Beiträge und sonstige Seiten verwenden weiterhin das vorhandene Theme.

## Startseitenbereiche

Seit Version 1.6 sind Newsletter-Anmeldung und FAQ/Hinweise eigene sortierbare Bereiche. Leere dynamische Listen lassen sich automatisch ausblenden. Vorbereitet sind Konzert-Highlight, kommende Konzerte, freier Text/Bildbereich, Über uns, Rückblicke, Stories, Gästestimmen, Ticketshop-Verweis und Geschenkgutscheine.

1. Bereich aufklappen und Texte/Bilder ändern.
2. Über den Griff ziehen oder mit **Nach oben / Nach unten** verschieben. Beides verändert die Reihenfolge auf der Startseite.
3. Mit **Bereich anzeigen** ein- oder ausblenden. Ausgeblendete Inhalte bleiben gespeichert.
4. Mit **Bereich hinzufügen** weitere freie oder dynamische Bereiche anlegen. Auch derselbe Inhaltstyp darf mehrfach vorkommen.
5. Mit **Bereich entfernen** den Bereich aus dem Layout entfernen. Das löscht keine Konzerte, Beiträge, Bilder oder Gästestimmen.
6. Abschließend **Website-Einstellungen speichern**.

Jeder Bereich hat eine Sprungmarke, Dachzeile, Überschrift, Texteditor, eigenes Bild, Anordnung (Text/Bild, Bild/Text, untereinander), Hintergrund-, Text- und Akzentfarbe sowie einen ergänzenden Button. Für Listen sind Spaltenzahl und maximale Eintragszahl einstellbar. Leere Farbfelder übernehmen das Website-Design. Bilder im Text können zusätzlich über den normalen Medien-Button eingefügt werden.

Beim **Konzert-Highlight** stammen Titel, Auszug und Bild automatisch aus dem nächsten veröffentlichten, nicht abgesagten Konzert. Ohne zukünftigen Termin wird der letzte Abend gezeigt. Ohne Konzert verwendet das Highlight die Texte und das Bild des Bereichs. Die Konzerttexte ändern sich unter **Konzerte**, die Beschriftungen der Highlight-Buttons unter **Beschriftungen, Meldungen & Button-Texte**.

Konzert-Designvorgaben können die Highlight-Anordnung und -Farben überschreiben. Mit „Bereichs- / Standardlayout“ bei den Konzert-Designvorgaben gilt die Anordnung des Highlight-Bereichs auch für ein ausgewähltes Konzert. Eine ausdrücklich gewählte Konzert-Anordnung überschreibt sie. Der Titel und Text normaler Bereiche bleiben unabhängig von Konzertdaten editierbar.

**Über uns** ist ein normaler, sortierbarer Bereich: Vorstellung, Geschichte, Teamfoto und z. B. ein Kontaktlink lassen sich dort pflegen. Es werden keine Personen oder Gästestimmen automatisch erfunden.

Die Sprungmarken müssen zur Navigation passen. Wenn du z. B. `ueber-uns` in `team` änderst, unter **Navigation** auch den entsprechenden Link von `#ueber-uns` auf `#team` ändern.

## Konzerte vor und nach dem Abend unterschiedlich gestalten

Unter **Grunddesign für kommende und vergangene Konzerte** zwei Vorgaben festlegen: Hintergrund, Textfarbe, Akzentfarbe, Ersatzbild und Anordnung. Die Anzeige wechselt automatisch anhand des Konzertbeginns in der Zeitzone der Website.

Im einzelnen Konzert unter **Gestaltung dieses Konzerts** diese Vorgaben getrennt überschreiben:

- „Vor dem Konzert“: Ankündigungsbild, eigene Farben und Anordnung.
- „Nach dem Konzert / Rückblick“: Foto vom Abend, andere Farben und Anordnung.

Ein leeres Farb-/Anordnungsfeld übernimmt die Website-Vorgabe. Die Bildreihenfolge lautet: eigenes Statusbild des Konzerts → Beitragsbild → Bereichs-Ersatzbild (im Highlight) → Status-Ersatzbild der Website → allgemeines Ersatzbild → Bühnenillustration. So bleiben vorhandene Beitragsbilder erhalten und lassen sich bei Bedarf je Konzertphase ersetzen.

Titel, Auszug, Beschreibung, Galerien und Videos weiterhin im normalen Konzerteditor pflegen. Das Statusbild gilt für Highlight, Karten und Detailseite. In der Detailseite kann „Bild links / Text rechts“ die Buchungsbox links anordnen; „Untereinander“ zeigt Inhalt und Buchungsbox untereinander.

## Navigation, Links und Beschriftungen

Unter **Navigation** eigene Links hinzufügen, beschriften, sortieren, ausblenden oder entfernen. Erlaubt sind vollständige HTTP(S)-URLs und Sprungmarken. Ticketshop und Warenkorb werden zusätzlich automatisch angeboten.

Unter **Social Media & rechtliche Links** die bereits bekannten Links pflegen. Unter **Beschriftungen, Meldungen & Button-Texte** lassen sich die sichtbaren Plugin-Texte ändern: Highlight-Status, Leerzustände, Shop-Titel, Kauf-Buttons, ausverkaufte/abgesagte Konzerte und rechtliche Link-Beschriftungen. Produktnamen und Preise kommen weiterhin aus WooCommerce, Konzerttexte aus der Konzertverwaltung. WooCommerce-eigene Zahlungs-/Fehlermeldungen werden durch WooCommerce und dessen Übersetzungen verwaltet.

## Ticketshop mit Konzertvorauswahl

Der Shop ist direkt nach dem Update über **Tickets** im Header und die Kauf-Buttons erreichbar. Ohne zusätzliche Seite verwendet er die Adresse `/?rl_ticket_shop=1` auf der aktuellen Website.

Für eine eigene Adresse wie `/tickets/`:

1. Neue Seite „Tickets“ erstellen.
2. Den Shortcode `[rommelfangen_tickets]` als normale Textzeile oder Shortcode-Block einfügen und veröffentlichen.
3. Unter **Marke & Erscheinungsbild → Ticketshop-Seite** diese Seite auswählen und speichern.

Beim Einstieg über den Header ist das nächste kommende Konzert vorausgewählt. Ein Kauf-Button im Highlight oder Konzertdetail übergibt genau dieses Konzert. Besucher können im Shop zu jedem anderen kommenden, nicht abgesagten Konzert wechseln. Ein ausverkaufter oder noch nicht geöffneter Abend wird angezeigt, lässt sich aber nicht kaufen. Vergangene Konzerte werden nicht zum Verkauf angeboten. Ist eine verlinkte Auswahl inzwischen ungültig, erscheint ein Hinweis und der nächste verfügbare Termin wird ausgewählt.

Das normale WooCommerce-Produkt bleibt die Grundlage: pro Konzert ein einfaches, virtuelles Produkt mit Preis, aktivierter Lagerverwaltung und ohne Nachbestellungen. Produkt im Konzert auswählen und veröffentlichen. Das Kontingent dort planen, nicht parallel im Website-Editor.

**In den Warenkorb** ergänzt die gewählte Menge, ohne Tickets anderer Konzerte zu entfernen. Der Shop zeigt die gesamte aktuelle Auswahl. Über **Warenkorb** Mengen ändern/Positionen entfernen und über **Zur Kasse** alle Konzerttickets in einer Bestellung bezahlen. Rabattcodes und Geschenkgutscheine werden wie bisher über WooCommerce bzw. die Gutschein-Erweiterung eingelöst. Es wird keine zweite Warenkorb- oder Zahlungsdatenbank aufgebaut.

Überweisungsbestellungen reservieren die Kontingente aller enthaltenen Konzerte bis zur Bestätigung oder Stornierung. Siehe [Betrieb](BETRIEB.md). Nicht bezahlte Warenkorbinhalte allein reservieren noch keine Plätze.

## Einrichtung und Prüfung vor echten Käufen

- WooCommerce muss auf der Konzertwebsite aktiv sein (eine vorhandene Netzwerkaktivierung funktioniert ebenfalls).
- Warenkorb und Kasse unter WooCommerce richtig zuordnen. Für die erste Einrichtung die klassischen Shortcodes `[woocommerce_cart]` und `[woocommerce_checkout]` verwenden.
- PayPal und Überweisung einrichten; E-Mail-Zustellung testen.
- Ticketshop einschließlich seiner Seiten-/Query-Varianten, Warenkorb und Kasse vom Seiten-/CDN-Cache ausnehmen. Das Plugin sendet für seinen Ticketshop Nicht-Cache-Header; vorgeschaltete Caches müssen diese respektieren bzw. entsprechend konfiguriert werden.
- Zwei Konzertprodukte mit kleinen Testkontingenten in denselben Warenkorb legen; Mengenänderung, Stornierung, Überweisung und Sandbox-Zahlung prüfen.
- Aktuelle und vergangene Konzerte mit eigenen Bildern/Farben prüfen. Layoutreihenfolge, ausgeblendete Bereiche, Mobilansicht und rechtliche Links kontrollieren.

Alle Gestaltungsänderungen sind Inhalte/Einstellungen der jeweiligen Website. Ein `git pull` aktualisiert den Code und überschreibt diese Einstellungen nicht. Das Plugin erzeugt weder echte Konzertdaten noch Zahlungskonten automatisch.
