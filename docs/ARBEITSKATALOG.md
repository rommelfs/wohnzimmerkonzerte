# Arbeitskatalog nach Projektprüfung

Ziel: zuverlässiger Betrieb kleiner Wohnzimmerkonzerte auf der bestehenden WordPress-/WooCommerce-Installation. Bestehende Inhalte und Einwilligungsnachweise bleiben erhalten. Der technische Zielumfang wurde in Version 1.6 umgesetzt und in Version 1.7 um die redaktionellen und visuellen Korrekturen aus der PDF-Prüfung ergänzt. Reale Produktionsabnahmen und eigene redaktionelle Inhalte bleiben davon getrennt.

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

## Redaktionelle und visuelle Nachprüfung vom 26. November 2026 (Version 1.7)

Die beiden bereitgestellten PDFs haben weitere Lücken gezeigt. Die frühere technische Abnahme deckte die Auffindbarkeit redaktioneller Funktionen und die Wirkung echter Konzertinhalte nicht ausreichend ab. Dafür gelten jetzt zusätzliche Kriterien:

| Paket | Ziel / Abnahme | Umsetzung |
| --- | --- | --- |
| E1 | Konzerttexte verständlich bearbeiten | Eigene Einleitung/Rückblick, kurzer Seitentitel/Untertitel und Infoboxtexte unter „Konzerte → Texte & Medien“, unabhängig vom gewählten WordPress-Editor; vorhandene Inhalte bleiben erhalten. Globale Texte auch für Redakteure unter „Konzerte → Website-Texte“. |
| E2 | Mehrere Bilder und verständliche Statusbilder | Separate sortierbare Galerie mit Bildunterschriften und Links zur vollen Größe. Vorher-/Nachher-Bilder ausdrücklich als zeitabhängige Alternativen erklärt. |
| E3 | Videovorschau ohne externen Dienst | Eigenes Poster bzw. Konzertbild als Ersatz; auch für vorhandene WordPress-Videos und Video-Playlists. Bestehende individuelle Poster bleiben erhalten. |
| E4 | Vergangene Konzerte als Rückblick gestalten | Kleinere Überschrift, optionaler Touruntertitel, volle Artikelbreite, keine Kalender-/Einlassaktion und direkte Newsletter-Anmeldung. |
| E5 | Navigation auf Konzertunterseite | Konfigurierbare/automatisch ermittelte Konzertstartseite für Logo und Sprunglinks; Konzertübersicht statt leerem Ticketshop als Rückblickziel. |
| E6 | Rechtliche Links und Shopfreigabe sichtbar machen | Datenschutzseite als vorhandenes Ersatz-Ziel; Hinweise auf fehlende Links und aktiven WooCommerce-Modus „Demnächst verfügbar“. Keine automatische Live-Freigabe oder erfundene Rechtstexte. |
| E7 | Redaktion von technischen Feldern entlasten | Doppelte technische Metadatenbox im klassischen Konzerteditor ausblenden, gespeicherte Werte erhalten. |

`tests/editorial.php` prüft die zusätzlichen Text-/Medienabläufe, Eingabebereinigung, vorhandene Inhalte, private Adressen, Rechte und Unterseiten-Verlinkung. Der Multisite-Runner führt diese Prüfung zusätzlich zu den bisherigen Tests aus. Redaktionelle Texte, die Auswahl echter zusätzlicher Fotos und die bewusste Shopfreigabe werden auf der Live-Installation gepflegt; sie werden durch das Update nicht automatisch veröffentlicht.

Abnahme dieser Runde: Der vollständige Multisite-Verbundtest bestand erneut einschließlich der zusätzlichen Integrationsprüfungen für Redaktion und Medien. Die Browserprüfung bestand mit 26 Prüfungen bei Desktop- und 390-Pixel-Mobilansicht, einschließlich tatsächlicher Video-Wiedergabe, Mediatheksauswahl, Galerie-Sortierung und Speichern als Redakteur. Fünf weitere Browserprüfungen bestätigten die Einbindung im Blockeditor, das Speichern im klassischen Editor sowie abgewiesene ungültige und anonyme Speicheranfragen. PHP-Syntax, JavaScript-Syntax und die sieben Auswahltests bestanden. Der Live-Zugriff lieferte am 26. November erneut HTTP 403; Live-Shopfreigabe und echte Inhalte werden dadurch nicht als geprüft behauptet.

## Galerie-Navigation (Version 1.7.2)

E2 ist um eine zusammenhängende Bildansicht ergänzt: Vor-/Zurück-Buttons, Pfeiltasten, Touch-Wischen, Bildzähler, Bildunterschriften und Schließen mit Rückgabe des Fokus. Einzelbild-Galerien, mehrere getrennte Galerien, Ladefehler und direkte Bildlinks ohne JavaScript bleiben unterstützt. Die Browserprüfung auf Desktop und Mobil einschließlich echter Touch-Ereignisse bestand; `tests/editorial.php` prüft zusätzlich die ausgelieferten Galerie-Daten und das lokale Script. Keine externen Dienste.

## Einfachere Newsletter-Anmeldung (Version 1.8)

Der Bestätigungslink schließt die Anmeldung nun unmittelbar ab. Der zweite Klick auf der Website entfällt ausdrücklich; das Risiko automatischer Linkprüfungen ist eine bewusste Produktentscheidung. Texte auf Formular, Rückmeldeseite, HTML-Mail und Textalternative beschreiben denselben Ablauf. Alte noch gültige Links funktionieren weiter. Offene Einträge werden nicht pauschal bestätigt.

Ergänzt sind eine bewusste einmalige Bestätigungserinnerung nach 24 Stunden (bis Tag fünf), sichtbare Übergabestatus und eine einstellbare Admin-Benachrichtigung nach erfolgreichem Abschluss. Die bestehende Einwilligung wird beim Erinnern nicht erweitert. Es gibt weder automatische Nachfassmails noch rückwirkenden Admin-Versand. `tests/registration.php` prüft Schema-Upgrade, Altlinks, Ein-Klick-Abschluss, Ablauf und Konkurrenzschutz der Erinnerung sowie Admin-Mails und deren Fehlerfälle.

Abnahme: vollständiger WordPress-/WooCommerce-Multisite-Verbundtest bestanden, einschließlich der 18 neuen Integrationsprüfungen für Anmeldung, Erinnerung und Admin-Mails. Zusätzlich wurde der echte öffentliche Formular-/Mail-Link-Ablauf auf Desktop und Mobil im Browser geprüft, ebenso die Erinnerung über die Adminoberfläche, ein ungültiger Nonce und das Speichern einer geänderten Admin-Zieladresse. Alle Mails und Zahlungen blieben lokal abgefangen. Die Testwebsite und das dafür angelegte Admin-Konto wurden anschließend entfernt. Reale offene Anmeldungen wurden weder aktiviert noch angeschrieben.

## Automatische Erinnerung nach einer Stunde (Version 1.8.1)

Die Erinnerung wird nun automatisch eine Stunde nach der noch offenen Anmeldung eingeplant. Ein site-lokaler WordPress-Cron-Auftrag verarbeitet bis zu 20 fällige Einträge pro Lauf und plant verbleibende Arbeit erneut ein. Er berücksichtigt auch passende bereits vorhandene offene Anmeldungen. Bestätigte, abgemeldete, bereits erinnerte oder mehr als fünf Tage alte Einträge bleiben ausgeschlossen. Ein gemeinsamer atomarer Einmal-Schutz verhindert doppelte Mails durch parallele automatische und manuelle Aufrufe. Fehler werden nicht automatisch erneut versendet. Der ursprüngliche Link bleibt bis zu seinem eigenen Ablauf gültig; die Erinnerung hat einen zusätzlichen 48-Stunden-Link. Der erste erfolgreiche Abschluss verbraucht beide Links. Bei Deaktivierung werden die Cron-Aufträge auch auf anderen Netzwerk-Websites entfernt.

Die tatsächliche Ausführung hängt vom WordPress-/Hosting-Cron ab und kann bei fehlendem Traffic später erfolgen. Zusätzliche Integrationstests prüfen die Stundenfrist, die Wiederaufnahme bestehender offener Anmeldungen, den automatischen Versand, wiederholte Aufrufe und die Netzwerk-Deaktivierung.

Abnahme: vollständiger Multisite-Verbundtest einschließlich automatischer Stundenfrist, Original-/Erinnerungslink, Einmal-Schutz und Cron-Bereinigung bei Netzwerk-Deaktivierung bestanden. Mails wurden im Test abgefangen; produktiver Versand startet erst nach Aktualisierung und Ausführung des Hosting-/WordPress-Crons.

## Logo-Platzierung (Version 1.9)

Das freigegebene Logo kann als transparente PNG-Datei in der Mediathek gewählt werden. Kopf- und Fußbereich haben eigene Bildauswahlen, Größen und einen gemeinsamen Darstellungsmodus (Bildzeichen mit Text, Logo mit eingebautem Schriftzug oder nur Text). Die mobile Breite ist getrennt einstellbar. Doppelter Schriftzug wird vermieden, Bildverhältnisse und zugänglicher Linkname bleiben erhalten. Fehlende Bilder fallen auf ein gültiges Header-Logo beziehungsweise den Website-Namen zurück. `tests/branding.php` deckt Moduswechsel, Grenzen, Alt-Einstellungen und Ersatzdarstellung ab; die Browserprüfung verwendet die echten Logo-Dateien aus dem Paket.

Abnahme: vollständiger Multisite-Verbundtest und zusätzliche Branding-Prüfungen bestanden. Die Browserprüfung mit den tatsächlichen PNG-Exporten bestätigt getrennte Header-/Footer-Logos, gespeicherte Größen, reine Logo- und kombinierte Textansicht, zugänglichen Linknamen und Darstellung ohne horizontales Überlaufen bei 390 bzw. 320 Pixeln. Testbilder und temporäre Website-Einstellungen wurden danach entfernt bzw. wiederhergestellt. Das separate Logo-Paket enthält transparente Raster-PNGs, eine PDF-/PNG-Übersicht, eine Farbpalette und eine Anleitung; es ist kein SVG-/AI-Vektorpaket.

## Kompakter Kopfbereich (Version 1.9.1)

Das Menü steht mit einstellbarem Abstand direkt neben dem Logo. Der neue Schieberegler reicht von 16 bis 160 px, mit 56 px Standard. Auf schmalen Ansichten rückt die Navigation unter das Logo, ohne dessen gewählte Größe zu verkleinern. Mobile vertikale Abstände und die getrennten Kopf-/Fußlogos bleiben erhalten. Integrationstests prüfen Grenzen, ausgegebene CSS-Werte und erhaltene Alt-Einstellungen. Im Browser wurden Tastaturbedienung und Speichern des Reglers, tatsächlicher Pixelabstand, 1024/768/390/320 px Breite und eine umfangreiche Navigation geprüft.

## Wirksame Logo-Größen (Version 1.9.2)

Reproduziert: 480 px gewünschte Breite ergaben durch eine gleichzeitig gespeicherte 110-px-Höhenbegrenzung nur rund 330 px sichtbare Breite. Die Breite ist nun die maßgebliche Größe; die Höhe folgt proportional. Eine zusätzliche Höhenbegrenzung ist ausdrücklich optional und zunächst ausgeschaltet. Desktop, Smartphone und Footer bleiben unabhängig einstellbar; der Desktopbereich reicht bis 640 px. Die gespeicherten alten Höhenwerte bleiben erhalten, begrenzen aber ohne Aktivierung nicht mehr unbemerkt die Darstellung.

Die Browserprüfung misst tatsächliche Bildbreiten nach Änderungen und nach Speichern über die WordPress-Oberfläche sowie die unabhängig wachsende Footer-Breite. Responsive Ansichten und die ausdrücklich aktivierte Höhenbegrenzung werden separat geprüft. Bilddateien und Menüabstand bleiben erhalten.

Abnahme: vollständiger Multisite-Verbundtest, PHP-Syntax und Größenprüfungen im echten Browser bestanden. Gemessen wurden unter anderem 480 px tatsächliche Breite bei einem alten gespeicherten 110-px-Höhenwert, geänderte Breiten über das WordPress-Formular, unabhängige Footer-Größe, mobile Ansichten bis 320 px und die bewusst aktivierte Höhenbegrenzung. Testbilder und temporäre Website-Einstellungen wurden anschließend entfernt bzw. wiederhergestellt.

## Größeres Konzertfoto im Startseiten-Highlight (Version 1.9.3)

Lange Konzerttitel beanspruchten durch die Mindestbreite der CSS-Grid-Spalten zu viel Platz und verkleinerten das Konzertfoto. Beide Spalten erhalten nun gleich viel Platz; die Konzertüberschrift ist auf maximal 56 px begrenzt. Unterhalb einschließlich 900 px stehen Text und Foto untereinander. Die konfigurierten Bildformate und Zuschnitte bleiben wirksam.

Browser-Abnahme: Das Toby-Beard-Highlight wurde mit dem langen Originaltitel und Konzertfoto von 320 bis 1840 px geprüft: gleich breite Desktop-Spalten, einspaltige mobile Ansicht, kein horizontales Überlaufen, unverändertes Bildformat und keine JavaScript-Ausnahmen. Temporäre Inhalte und Einstellungen wurden anschließend entfernt beziehungsweise wiederhergestellt.

Der vollständige WordPress-/WooCommerce-Multisite-Verbundtest sowie PHP-Syntaxprüfung und `git diff --check` bestanden ebenfalls.

## Mehr Bildfläche bei Gastgebern und Rückblicken (Version 1.9.4)

„Über uns“ teilt Bild und Text im Verhältnis 60:40 auf; Bildposition, gestapeltes Layout und Text ohne Bild bleiben unterstützt. Konzertlisten lassen keine leeren Spalten für fehlende Konzerte stehen. Ein einzelnes kommendes oder vergangenes Konzert nutzt eine breite Karte mit größerer WordPress-Bildgröße und Text neben dem Foto. Bis 900 px werden beide Bereiche einspaltig.

Browserprüfung von 320 bis 1840 px: Bildgrößen, mobiles Stapeln, kein horizontales Überlaufen, umgekehrte Bildposition und gestapelte Gastgeberansicht bestanden. Temporäre Testinhalte wurden entfernt und ursprüngliche Einstellungen wiederhergestellt. Integrationstests prüfen ein beziehungsweise zwei Konzerte, die Trennung kommender und vergangener Termine sowie die Beibehaltung einer ausdrücklich gewählten einspaltigen Liste.

Abnahme: vollständiger WordPress-/WooCommerce-Multisite-Verbundtest einschließlich der neuen Listenfälle sowie PHP-Syntaxprüfung und `git diff --check` bestanden.
