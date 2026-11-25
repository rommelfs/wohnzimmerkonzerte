# Arbeitskatalog nach Projektprüfung

Ziel: zuverlässiger Betrieb kleiner Wohnzimmerkonzerte auf der bestehenden WordPress-/WooCommerce-Installation. Bestehende Inhalte und Einwilligungsnachweise bleiben erhalten. Der technische Zielumfang ist in Version 1.6 umgesetzt; reale Produktionsabnahmen und eigene redaktionelle Inhalte sind ausdrücklich davon getrennt.

| Paket | Ziel / Abnahme | Stand und Nachweis |
| --- | --- | --- |
| A1 | Tickets bleiben nach Papierkorb, Löschung und Neuzuordnung gesperrt | Erledigt; `tests/operations.php`. Duplizierte Produkte erhalten eine neue Identität. |
| A2 | Gültige Überschriften und nächste buchbare Shop-Vorauswahl | Erledigt; `tests/catalogue.php` und Browserprüfung |
| A3 | Mehrere Newsletter-Anmeldungen im gemeinsamen WLAN; verständliche Begrenzung | Erledigt; sechs Anmeldungen im Browser und `tests/notifications.php`; 120 Anfragen/Stunde statt fünf |
| A4 | Alte Einwilligungen nicht still auf Rückblicke/Neuigkeiten ausdehnen | Erledigt; getrennte Verteiler, erneute Bestätigung ohne Unterbrechung der alten Anmeldung; `tests/consent.php` |
| B1 | Newsletter und Bestellermails mit Statusfiltern, auch für Absagen | Erledigt; bezahlte/offene/aktive Besteller, erneute Statusprüfung; `tests/operations.php` und Browserfreigabe |
| B2 | Konzertübersicht, Gästeliste, CSV und mengenbezogener Einlass | Erledigt; bezahlt/offen/frei/erstattet und Einlassmengen; `tests/operations.php` und Browserprüfung |
| B3 | Buchungsdetails sichern; private Adresse nur für bezahlte Besteller | Erledigt; öffentliche Bestellpositionsdaten, bezahlungsabhängige Information; `tests/operations.php` und Browserprüfung |
| B4 | Absage/Änderung und positionsbezogene Erstattungsbearbeitung | Erledigt als bewusst bestätigter Ablauf; Bestellerverteiler und Links zur passenden WooCommerce-Bestellung. Teil-Erstattung mit unveränderter zweiter Konzertposition getestet. Keine automatische echte Auszahlung. |
| B5 | Überweisungsfrist, Erinnerung und bewusst aktivierbare Freigabe | Erledigt; einmalige Erinnerung, automatisches Storno standardmäßig aus; `tests/operations.php` |
| B6 | Versandpause, Testmail und Cron-Status | Erledigt; Browserprüfung und Regressionstest. Tatsächlicher Posteingang bleibt Produktionsabnahme. |
| C1 | Früher, sortierbarer Newsletter; leere Bereiche ausblendbar; gestaltete Rückmeldungen | Erledigt; bestehende Layouts einmalig und ohne Zurücksetzen ergänzen; `tests/catalogue.php` und Browserprüfung |
| C2 | Mobiler Kauf vor großer Bildfläche, klarer Warenkorb und Kundenkonto | Erledigt; dauerhafter Kassenlink bei gefülltem mobilen Warenkorb, keine Versandnotizen bei rein virtuellen Produkten; Browserprüfung |
| C3 | Persönliche Texte/Fotos, konkrete FAQ und Live-Inhalte | Redaktion / Live-Abnahme offen. Editierbare Bereiche sind vorhanden; echte Inhalte werden nicht erfunden oder überschrieben. |
| D1 | Konzertbezogene Warteliste und Kalenderdownload | Erledigt; eigenständige Bestätigung, einmaliger unverbindlicher Verfügbarkeitshinweis, keine Newsletter-Anmeldung; Kalender ohne private Adresse. `tests/catalogue.php`, `tests/operations.php` und Browserprüfung |
| D2 | Exklusive zeitlich reservierte Nachrückangebote | Später bei Bedarf. Die aktuelle Warteliste verspricht ausdrücklich keine Reservierung. |
| D3 | QR-Tickets, Sitzplatzpläne, mehrere Tarife mit gemeinsamem Bestand | Später bei tatsächlichem Bedarf; kein Bestandteil der ersten Betriebsfreigabe |

## Erneute Katalogprüfungen

1. Die ursprünglichen fünf reproduzierten Probleme wurden in Regressionstests und Browserabläufen geprüft. Dabei kamen die Sicherung historischer Buchungsinformationen und ein Schutz privater Adressen hinzu.
2. Der erste vollständige Verbundtest deckte einen Fristenfehler durch parallel gespeicherte Bestellobjekte auf. Die Status-Hooks arbeiten nun mit dem von WooCommerce übergebenen Bestellobjekt. Der danach wiederholte Verbundtest bestand.
3. Die erneute Layoutprüfung fand Navigationslinks zu automatisch ausgeblendeten Bereichen. Diese Links werden nun ebenfalls ausgeblendet. Eigene Anker bleiben erhalten.
4. Die Einwilligungsprüfung trennt den ursprünglichen Themenumfang von erweiterten Neuigkeiten. Ein Erweiterungsantrag verändert weder den bisherigen Empfang noch das Namensprofil; erst der bestätigte Link erweitert den Verteiler.
5. Die Migrationen von älteren Mailtabellen und älteren Layouts werden mit erhaltenen Bestellungen/Empfängern/Versandzuständen bzw. eigenen Texten getestet. Entfernte Newsletter-Bereiche werden nach der einmaligen Migration nicht wieder aufgezwungen.

## Laufende Abnahme

In einer **isolierten** WordPress-Multisite mit `WP_ENVIRONMENT_TYPE=local` oder `development`:

```sh
php tests/selection.php
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
python3 tests/multisite.py --wordpress=/pfad/zur/testinstallation --wp-cli=/pfad/wp-cli.phar --network-url=https://network.example.test
```

Der Multisite-Runner führt Website-/Shop-, Mailformat-, Einwilligungs-, Betriebs- und Wartelistentests aus und entfernt seine temporären Websites wieder. Er erzeugt keine externen Zahlungen und unterdrückt echten Mailversand.

## Noch von außen zu prüfen

- Vorhandene echte Inhalte und der aktuelle Live-Auftritt unter `https://rommelfangen.de/wohnzimmerkonzerte/`. Der Cloud-Zugriff war bei der Projektprüfung mit 403 blockiert.
- Tatsächlicher Empfang von Bestätigungs-, Test- und Buchungsmails über den eigenen Postfix-Mailweg; Absender und Antworten im echten Postfach prüfen.
- PayPal-Sandbox, Gutscheinabläufe und echte Rückerstattungen mit den eigenen Konten. Die automatisierten Erstattungstests nutzen ausdrücklich keine externe Auszahlung.
- Tatsächlicher Hosting-Cron, Bankverbindung, Zahlungsfrist und die bewusste Entscheidung über automatische Stornierung. Letztere ist zunächst ausgeschaltet.

Diese Abnahmen sind keine behaupteten erledigten Produktionsänderungen. Es werden keine früheren Käufer automatisch zum Newsletter hinzugefügt und keine echten Erstattungen automatisch ausgelöst.

## Abschlussprüfung vom 25. November 2026

Der vollständige Multisite-Verbundtest mit MariaDB, WordPress 7.0 und WooCommerce 11.3.1 bestand nach den Korrekturen einschließlich Einwilligungsmigration. Die sieben Auswahltests und die PHP-Syntaxprüfung bestanden ebenfalls. Zusätzlich wurden 26 Browserprüfungen auf Desktop und bei 390 Pixel Breite durchgeführt, darunter ein Warenkorb mit zwei Konzerten, Zahlungssimulation ohne externen Zahlungsaufruf, Einlass, CSV, Absage und sechs Anmeldungen über dieselbe IP. Die Testmail-Route wurde mit gültiger Anmeldung und abgewiesenem ungültigem Nonce geprüft. Die dafür angelegte Browser-Testwebsite wurde danach entfernt.
