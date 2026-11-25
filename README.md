# Rommelfangen Living Room Concerts

Eigenständiges WordPress-Plugin für Wohnzimmerkonzerte, nach dem Installationsprinzip von [taka-platform](https://github.com/rommelfs/taka-platform). Das Repository wird direkt nach **`wp-content/plugins/wohnzimmerkonzerte`** geklont und dort mit **`git pull --ff-only`** aktualisiert. Es funktioniert auf einer Einzelwebsite und in WordPress Multisite.

## Funktionen

- Eigene Konzertverwaltung mit Beginn, Einlass, Ortsangabe und Ticketprodukt.
- Startseite per `[rommelfangen_homepage]`: nächstes Konzert als Highlight, andernfalls letzter Abend. Kommende Konzerte, Stories und Gästestimmen direkt erreichbar.
- Eigene responsive Vorlagen für die Konzertstartseite, Konzertdetails und das Konzertarchiv. Ein zusätzliches Konzert-Theme ist nicht erforderlich. Das vorhandene Theme gestaltet normale Beiträge und Seiten; Warenkorb und Kasse können optional das Konzertdesign verwenden.
- Ticketverkauf und Bestandsverwaltung über WooCommerce, PayPal Payments und Überweisung.
- WooCommerce-Rabattcodes sowie PW WooCommerce Gift Cards für Geschenkgutscheine mit Restguthaben.
- Konzertbenachrichtigungen mit freiwilliger Anmeldung, Double-Opt-in, Abmeldung und WooCommerce-Kundenanbindung. Bearbeitbare HTML-Mails mit Bildern, Links und Textalternative, persönlichen Platzhaltern und einstellbarem Absender zu kommenden und vergangenen Konzerten. Versand über den vorhandenen WordPress-Mailweg, ohne Newsletter-Dienst.
- Galerien, Videos, Stories, Tags und Social-Media-Links. Ohne Inhalte erscheinen passende Leerzustände und eine eigene Bühnenillustration.
- Einstellungen, Konzerte und Ticketzuordnungen beziehen sich auf die aktuelle Website. Website-Einstellungen bleiben beim Themewechsel erhalten.

## Konzertbetrieb

Ab Version 1.6: Konzertübersicht mit Gästeliste, CSV und Einlassstatus, gezielte Bestellermails, private Buchungsinformationen nach Zahlung, Überweisungsfristen, eigene Wartelisten und Kalenderdownloads. [Betriebsabläufe](docs/KONZERTBETRIEB.md) · [Arbeitskatalog und Abnahmen](docs/ARBEITSKATALOG.md).

## Website im Backend gestalten

Ab Version 1.2 unter **Einstellungen → Rommelfangen Concerts** Marke, Farben, Schriften, Bilder, Navigation und sämtliche Plugin-Beschriftungen bearbeiten. Startseitenbereiche lassen sich hinzufügen, ausblenden und per Ziehen oder Tasten sortieren. **Über uns** und **Vergangene Konzerte** sind vorbereitet. Jedes Konzert hat getrennte Design-/Bildoptionen vor und nach dem Konzertbeginn.

Der eigene Ticketshop wählt den nächsten Abend bzw. das übergebene Konzert vor und verwendet einen gemeinsamen WooCommerce-Warenkorb für Tickets mehrerer Konzerte. Über **Tickets** ist er sofort erreichbar. Für eine eigene Shop-Seite `[rommelfangen_tickets]` verwenden und diese in den Einstellungen auswählen. Warenkorb und Kasse können dasselbe Konzertdesign verwenden.

**[Bedienungsanleitung für Website-Editor und Ticketshop](docs/WEBSITE-EDITOR.md)**

## Installation in deiner bestehenden Multisite

Die vollständige Anleitung einschließlich Domainzuordnung, GitHub-Zugriff, Einrichtung ohne WP-CLI und Migration steht unter **[Multisite installieren und aktualisieren](docs/MULTISITE.md)**.

Voraussetzungen: Netzwerkadministrator-Zugang, SSH und Git auf dem Host, PHP 8.2+, WordPress 7.0+, MySQL/MariaDB und HTTPS. WordPress und Drittanbieter-Erweiterungen werden separat verwaltet.

Nach eingerichteter Website und GitHub-Zugriff:

```sh
cd /pfad/zur/multisite/wp-content/plugins
git clone git@github.com:rommelfs/wohnzimmerkonzerte.git wohnzimmerkonzerte
cd /pfad/zur/multisite

# Nur fehlende Erweiterungen installieren; bestehende Installationen weiterverwenden.
wp plugin install woocommerce woocommerce-paypal-payments pw-woocommerce-gift-cards
wp plugin activate woocommerce --url=https://rommelfangen.de
wp plugin activate wohnzimmerkonzerte --url=https://rommelfangen.de
wp plugin activate woocommerce-paypal-payments pw-woocommerce-gift-cards --url=https://rommelfangen.de
```

Im Dashboard von **rommelfangen.de** eine Seite mit `[rommelfangen_homepage]` veröffentlichen. Unter **Einstellungen → Lesen** als statische Startseite auswählen. Unter **Einstellungen → Rommelfangen Concerts** Social-Media-, Gutschein- und rechtliche Links eintragen. Das vorhandene Theme bleibt aktiv; kein Theme-Verzeichnis kopieren und keine Dateien nach dem Update synchronisieren.

Die Aktivierung erfolgt gezielt pro Website. Eine bewusste Netzwerkaktivierung ist ebenfalls möglich; dann ist die Konzertverwaltung auf allen Websites verfügbar. Für euren einzelnen Konzertauftritt ist die Aktivierung nur auf rommelfangen.de vorgesehen.

## Updates

```sh
cd /pfad/zur/multisite/wp-content/plugins/wohnzimmerkonzerte
git status --short
git pull --ff-only
```

Zuvor Dateien und Datenbank sichern, Änderungen auf der Testwebsite prüfen. Bei lokalen Codeänderungen zuerst vergleichen und committen; bei Fehlern abbrechen, keine erzwungenen Resets. Bei aktiviertem Cache anschließend den Website-Cache leeren.

Das Plugin prüft benötigte Weiterentwicklungen seiner URL-Regeln pro Website automatisch beim nächsten Laden. Nach einem normalen Update ist keine erneute Aktivierung nötig. Für Konzertbenachrichtigungen legt es drei eigene Tabellen pro Website an; es verändert weder die Netzwerk-Konfiguration noch `.htaccess`.

Konzerte, Bestellungen, Einstellungen, Uploads, Zugangsdaten sowie TAKA und andere Plugins liegen außerhalb dieses Repositorys. WordPress, WooCommerce und Erweiterungen weiterhin separat aktualisieren. Alle Websites, die dieses Plugin aktiv verwenden, erhalten dieselbe Codeversion.

## Redaktion und Verkauf

Siehe **[Betrieb, Tickets, Gutscheine und Abnahme](docs/BETRIEB.md)**. WooCommerce-Einrichtung, Zahlungskonten und echte Buchungstests vor dem Livegang abschließen. Das Plugin baut auf den vorhandenen WooCommerce-Zahlungs- und Gutschein-Erweiterungen auf; diese sind nicht im Repository enthalten.

## Wechsel vom ursprünglichen Repository-Aufbau

Version 1.0 lag im WordPress-Hauptverzeichnis und enthielt `wohnzimmer-core` plus das Theme `wohnzimmer`. **Bei einer bereits installierten Version 1.0 zunächst die [Migration](docs/MULTISITE.md#wechsel-von-version-10) durchführen; im alten Root-Checkout noch kein `git pull` ausführen.** Die neue Version liegt ausschließlich im Plugin-Verzeichnis. Konzertdaten und Ticketprodukt-IDs bleiben erhalten.

## Prüfung

```sh
# Im Plugin-Repository, ohne WordPress:
php tests/selection.php
find . -name '*.php' -not -path './vendor/*' -exec php -l {} \;

# Nur auf einer isolierten WordPress-Testinstallation:
wp eval-file wp-content/plugins/wohnzimmerkonzerte/tests/wordpress.php --url=https://test.example.test
```

GitHub Actions prüft PHP-Syntax, Highlight-Auswahl und Multisite-Isolation. Die Multisite-Testanleitung steht in [tests/MULTISITE.md](tests/MULTISITE.md). Externe Zahlungen und E-Mail-Zustellung werden dabei nicht durchgeführt.
