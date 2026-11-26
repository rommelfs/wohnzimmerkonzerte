# Multisite-Integration testen

Nur eine isolierte, wegwerfbare Testinstallation verwenden. Der Runner verlangt `WP_ENVIRONMENT_TYPE=local` oder `development`, legt drei temporäre Websites an und aktiviert das Konzertplugin vorübergehend netzwerkweit. Er entfernt seine Websites abschließend wieder. Nicht auf der produktiven Multisite oder einer gemeinsam verwendeten Entwicklungsumgebung ausführen.

Voraussetzungen: Python 3, PHP, WP-CLI als PHAR, WordPress Multisite, installiertes WooCommerce und dieses Repository unter `wp-content/plugins/wohnzimmerkonzerte`. Zu Beginn darf das Konzertplugin nicht netzwerkweit aktiv sein. E-Mails werden während der Tests über `pre_wp_mail` unterdrückt.

```sh
python3 tests/multisite.py \
  --wordpress=/absoluter/pfad/zur/test-multisite \
  --wp-cli=/absoluter/pfad/zu/wp-cli.phar \
  --network-url=https://network.example.test
```

Die Domain des Netzwerks muss der Testinstallation entsprechen. Die zusätzliche gemappte `.example.test`-Domain wird nur durch WP-CLI adressiert und benötigt für diese Tests kein öffentliches DNS oder Zertifikat.

Geprüft werden gezielte Website-Aktivierung, inaktive Nachbarwebsite, Domain-Mapping und Konzert-Permalinks, Shortcode-Rendering, getrennte Konzerte/Einstellungen, automatische URL-Regeln nach einem Update, WooCommerce-Tickets mit Bestandsabzug und Rückbuchung, Netzwerkaktivierung, neue Websites und datenerhaltende Deaktivierung. Zusätzlich werden Konzertbenachrichtigungen mit Double-Opt-in, Abmeldung, Checkout (klassisch und Blocks), Datenschutzexport/-löschung und dem Hintergrundversand inklusive Fehler- und Wiederholungsfällen, vergangenen Konzerten, bearbeitbaren Nachrichten, mehreren Aufträgen pro Konzert und einer datenerhaltenden Migration sowie echte MIME-Erzeugung für HTML-/Textalternativen, Platzhalter und die Isolation des Modul-Absenders (`tests/notifications-format.php`) geprüft (`tests/notifications.php`). Der Mailweg wird abgefangen; es werden keine externen E-Mails versendet.

GitHub Actions führt diese Prüfung mit MySQL aus. Lokale SQLite-Prüfungen dienen zusätzlich der Entwicklung; Zahlungs-/Gutschein-Anbieter werden damit nicht vollständig getestet. Visuelle Prüfung mit dem produktiven Theme und echte Sandbox-Zahlungen bleiben Teil der Abnahme.

Ab Version 1.2 führt der Runner zusätzlich `tests/editor-shop.php` aus. Geprüft werden bereinigte Gestaltungsdaten (einschließlich JSON-Formularübertragung), Abschnittsreihenfolge/Sichtbarkeit, eigener Über-uns-Inhalt, getrennte Statusbilder/-farben, Shop-Vorauswahl, Ticketformular-Nonce, gemeinsamer Warenkorb, Mengenlimits, gemischte Überweisungsreservierungen, Stornierung und Verkaufssperren. Die dafür angelegten Bilder, Konzerte, Produkte und Bestellungen werden wieder entfernt.

Ab Version 1.6 kommen drei Prüfgruppen hinzu: `tests/operations.php` prüft historische Ticketidentität, Bestelldaten, Empfängerkreise, private Buchungsinformationen, Einlass, Teil-Erstattungen ohne Auszahlung, Überweisungsfristen, Versandpause und Kalender. `tests/catalogue.php` prüft erhaltene Layouts, Navigation, Kaufvorauswahl und die separate Warteliste. `tests/consent.php` prüft die unveränderte Gültigkeit alter Einwilligungen und die ausdrücklich bestätigte Erweiterung des Themenumfangs. Auch eine ältere Versandtabellenstruktur wird mit vorhandenen Empfängern und Versandzuständen migriert.

Ab Version 1.7 prüft `tests/editorial.php` ergänzend Textwechsel vor/nach einem Konzert, erhaltene Altinhalte, Titel/Untertitel, Galerie und Bildunterschriften, Videoposter einschließlich alter Playlists, Unterseiten-Navigation, Datenschutzseiten und Redaktionsrechte.
