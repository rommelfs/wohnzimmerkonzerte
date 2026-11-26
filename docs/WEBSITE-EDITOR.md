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

## Konzerttexte und Medien ab Version 1.7

Unter **Konzerte → Texte & Medien** stehen die Inhalte unabhängig vom klassischen oder Blockeditor bereit. In der Konzertliste führt die Zeilenaktion **Texte & Medien** direkt zum jeweiligen Abend. Im klassischen Konzerteditor führt zusätzlich der Hinweis unter dem Titel zur Box **Texte, Galerie & Video**. Dort können Redakteure unabhängig vom allgemeinen Design arbeiten:

- **Kurzer Seitentitel / Untertitel:** optional, z. B. „Toby Beard“ und „Autumn Lights Tour · Wohnzimmerkonzert“. Der vollständige Beitragstitel, Permalink und bestehende Bestellungen bleiben erhalten. Ohne eigenen Seitentitel wird weiter der Beitragstitel ausgegeben.
- **Ankündigung / Einleitung** und **Persönlicher Rückblick:** vor dem Termin erscheint die Einleitung, danach der Rückblick. Ohne Rückblick bleibt die Einleitung sichtbar. Der vorhandene große Inhaltseditor wird zusätzlich ausgegeben und nicht verändert. Dort bereits gespeicherte Videos bleiben erhalten.
- **Titel / öffentlicher Hinweis der Infobox:** optional je Konzert und für alle Zustände dieses Konzerts; leere Felder verwenden die zum jeweiligen Zustand passenden allgemeinen Website-Texte.
- **Fotogalerie:** mehrere Bilder aus der Mediathek hinzufügen, mit Pfeilen sortieren und einzeln entfernen. Die Bildunterschrift und der Alternativtext werden direkt am jeweiligen Medium gepflegt. Ein Klick auf ein Galeriefoto öffnet die Bildansicht mit Vor-/Zurück-Buttons, Bildzähler und Bildunterschrift. Pfeiltasten oder Wischen wechseln das Bild; Escape oder der Schließen-Button kehren zur Galerie zurück. „Original öffnen“ zeigt die Originaldatei in einem zusätzlichen Tab. Ohne JavaScript bleiben die direkten Bildlinks erhalten.
- **Video:** optional ein zusätzliches Video aus der eigenen Mediathek auswählen. Für bereits im Editor eingebettete Videos muss es hier nicht noch einmal hinzugefügt werden. Ein eigenes Vorschaubild ist möglich; andernfalls wird das aktuelle Konzertbild verwendet. Bereits vorhandene individuelle Poster bleiben erhalten. Fehlt jedes Konzertbild, wird die lokale Bühnenillustration verwendet. Es wird kein externer Videodienst benötigt.

Die beiden Bilder unter **Gestaltung dieses Konzerts** bleiben Alternativen für vor/nach dem Termin. Eine Galerie ist davon unabhängig. Die technische Box „Individuelle Felder“ wird im klassischen Konzerteditor ausgeblendet; ihre gespeicherten Werte werden nicht gelöscht.

Allgemeine Beschriftungen sind nun auch für die WordPress-Rolle **Redakteur** unter **Konzerte → Website-Texte** erreichbar. Diese Seite erlaubt ausschließlich Textänderungen. Gestaltung, rechtliche Links, Zahlungsdaten und Versandkonfiguration bleiben in den jeweiligen berechtigten Einstellungen.

Unter **Einstellungen → Rommelfangen Concerts** kann die **Konzertstartseite für Logo und Sprunglinks** gewählt werden. Automatisch wird zuerst eine passende WordPress-Startseite, sonst die älteste veröffentlichte Seite mit `[rommelfangen_homepage]` verwendet. Bei mehreren Konzertstartseiten die gewünschte Seite ausdrücklich auswählen.

Rückblicke haben ein eigenes Artikellayout mit kleinerer Überschrift, Datum/öffentlichem Ort, Fotos und Text. Einlass und die Kalenderaktion werden dort nicht mehr angeboten. Die weiterführende Aktion führt zur Konzertübersicht. Wenn der Newsletter-Bereich aktiviert ist, steht die Anmeldung zusätzlich direkt unter dem Konzertartikel.

Im Footer wird eine konfigurierte Datenschutz-URL bevorzugt, ersatzweise die veröffentlichte WordPress-Datenschutzseite. Fehlende Links für Impressum und Datenschutz werden berechtigten Administratoren im Konzertbereich angezeigt. Es werden keine rechtlichen Inhalte oder Ziele erfunden. Ein Hinweis zeigt außerdem den WooCommerce-Modus „Demnächst verfügbar“ an. Die tatsächliche Veröffentlichung des Shops bleibt eine bewusste Betriebsentscheidung.

## Hintergrund ab Version 1.7.1

Unter **Einstellungen → Rommelfangen Concerts → Marke & Erscheinungsbild → Hintergrundwirkung** stehen **Sanfte Lichtverläufe** (Standard) und **Ruhige Farbfläche** zur Auswahl. Die Lichtverläufe verwenden die eingestellten Akzent- und Textfarben über den vorhandenen Flächenfarben. Eigene Farben bleiben erhalten. Die Darstellung ist statisch, benötigt keine Bilddateien oder externen Dienste und fügt keine Effekte zur Druckansicht hinzu. Browser ohne Unterstützung für die Farbmischung zeigen die eingestellten Farbflächen.

## Galerie-Bildansicht ab Version 1.7.2

Die Bildansicht hält die Fotos einer Galerie zusammen: Vor-/Zurück-Buttons und Pfeiltasten blättern, auf Touchscreens ist horizontales Wischen möglich. Nach dem letzten Foto folgt wieder das erste. Bildzähler und Bildunterschrift zeigen die aktuelle Position. Escape, Schließen-Button oder ein Klick außerhalb der Bildansicht schließen sie; der Tastaturfokus kehrt zum geöffneten Vorschaubild zurück. Bei einer Galerie mit nur einem Foto entfallen die Blätterbuttons. Ein nicht geladenes Bild blockiert das weitere Blättern nicht.

Die Beschriftungen stehen unter **Konzerte → Website-Texte**. Im Zählertext die Platzhalter `{current}` und `{total}` erhalten. Es werden keine externen Galerie-Dienste oder Bibliotheken geladen.

## Logo in Kopf- und Fußbereich ab Version 1.9

Unter **Einstellungen → Rommelfangen Concerts → Marke & Erscheinungsbild** stehen eigene Bildauswahlen für Kopf- und Fußbereich bereit. Ohne eigenes gültiges Footer-Logo wird das Kopfbereich-Logo verwendet.

**Logo-Darstellung** bietet drei Möglichkeiten:

- **Bildzeichen mit zusätzlichem Website-Namen:** für Symbole ohne eigene Schrift; Name und Untertitel bleiben als bearbeitbarer Text daneben sichtbar.
- **Nur Logo (Schriftzug ist im Bild enthalten):** für die Haupt- oder horizontale Wortmarke; zusätzlicher Text wird ausgeblendet.
- **Nur Website-Name und Untertitel:** klassische Textdarstellung ohne Logo.

Die maximale Logo-Breite für Desktop, Smartphone und Footer sowie die maximale Höhe lassen sich einstellen. Bilder werden proportional und vollständig dargestellt. Ein fehlendes Logo führt zum sichtbaren Website-Namen zurück. Der Link besitzt auch in der reinen Bilddarstellung einen zugänglichen Namen und führt zur Konzertstartseite. Bestehende Logo-plus-Text-Einstellungen bleiben erhalten.

Für die dunkle Konzertwebsite empfiehlt sich ein transparentes horizontales Logo mit heller Schrift. Startwerte: Kopfbereich 280 px, maximale Höhe 140 px, Smartphone 220 px, Footer 200 px. Dateien aus dem Logo-Paket zuerst in die Mediathek hochladen; das Paket wird nicht automatisch in echte Website-Inhalte importiert.

### Abstand zwischen Logo und Menü ab Version 1.9.1

Unter **Marke & Erscheinungsbild** steuert der Schieberegler **Abstand zwischen Logo und Menü (Desktop)** den horizontalen Abstand von 16 bis 160 px; Standard sind 56 px. Der Wert wird während des Schiebens angezeigt und mit **Website-Einstellungen speichern** übernommen. Das Menü schließt direkt an das Logo an. Bei wenig Platz bricht es darunter um; auf Smartphones bleibt der vertikale Abstand bei 24 px. Kopf- und Fußbereich behalten ihre getrennten Logo-Auswahlen.

Für die horizontale Logo-Variante bieten sich 320 px maximale Breite und 110 px maximale Höhe an, für Smartphones 220 px Breite. Vorhandene Größen und gewählte Logo-Dateien werden durch das Update nicht verändert.
