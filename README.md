# Rommelfangen Living Room Concerts

WordPress-Projekt für intime Live-Konzerte. Das Repository liegt **direkt im WordPress-Hauptverzeichnis**, neben `wp-admin`, `wp-includes` und `wp-config.php`. Updates des eigenen Codes erfolgen mit `git pull --ff-only`.

## Enthalten

- Eigenes responsives Theme: dunkles Grün, warme Akzente, lokale Systemschriften, keine externen Schriftanbieter.
- Automatisch das nächste veröffentlichte, nicht abgesagte Konzert als Highlight; andernfalls das letzte Konzert. Unbekannte Termine werden ausgelassen. Ausverkaufte Konzerte bleiben sichtbar.
- Kommende Konzerte direkt auf der Startseite, eigenes Konzertarchiv und Detailseiten.
- Konzertmodul mit Beginn in der WordPress-Zeitzone, Einlass, öffentlicher Ortsangabe und Verknüpfung zum Ticketprodukt.
- WooCommerce-Tickets mit eigenem Bestand je Konzert. Schutz vor Käufen für abgelaufene, abgesagte oder unveröffentlichte Konzerte.
- Stories, Bildergalerien, selbst gehostete Videos, Tags, Gästestimmen und konfigurierbare Social-Media-Links.
- Einbindung von WooCommerce-Rabattcodes sowie PW WooCommerce Gift Cards für echte Geschenkgutscheine mit Restguthaben.

Es sind bewusst keine Künstler, Termine oder Gästestimmen erfunden. Ohne echte Inhalte erscheinen passende Leerzustände und eine eigene Bühnenillustration.

## Installation

PHP 8.2+, WordPress 7.0+, eine WordPress-kompatible MySQL-/MariaDB-Datenbank und HTTPS für den Betrieb. WordPress und Drittanbieter-Plugins sind **nicht** im Repository enthalten.

### Neue Website

```sh
git clone git@github.com:rommelfs/wohnzimmerkonzerte.git /pfad/zur/website
cd /pfad/zur/website
wp core download --skip-content
wp config create --dbname=DEINE_DATENBANK --dbuser=DEIN_DB_BENUTZER --prompt=dbpass
wp core install --url=https://DEINE-DOMAIN --title='Rommelfangen Living Room Concerts' --admin_user=DEIN_ADMIN --admin_email=DEINE_EMAIL --prompt=admin_password
```

WP-CLI ist optional. Alternativ dieses Repository klonen, WordPress-Dateien **ohne Überschreiben des vorhandenen `wp-content`** ergänzen und den Installationsassistenten im Browser öffnen. Mit `--skip-content` liefert WP-CLI nur die Core-Dateien.

### Bestehende WordPress-Website

Zuerst Dateien und Datenbank sichern. Wenn das Hauptverzeichnis schon ein Git-Repository enthält, nicht überschreiben: das bestehende Repository zuerst prüfen. Falls noch keines besteht:

```sh
cd /pfad/zum/wordpress-hauptverzeichnis
git init
git remote add origin git@github.com:rommelfs/wohnzimmerkonzerte.git
git fetch origin
git switch --track origin/main
```

Git bricht bei kollidierenden vorhandenen Dateien ab. In diesem Fall die betroffenen Dateien zuerst sichern und vergleichen. Kein erzwungenes Checkout verwenden.

### Aktivieren und einrichten

```sh
wp plugin install woocommerce woocommerce-paypal-payments pw-woocommerce-gift-cards --activate
wp plugin activate wohnzimmer-core
wp theme activate wohnzimmer
wp option update timezone_string Europe/Berlin
wp option update woocommerce_currency EUR
wp option update woocommerce_manage_stock yes
wp option update woocommerce_hold_stock_minutes 30
wp option update woocommerce_enable_coupons yes
wp rewrite structure '/%postname%/' --hard
wp rewrite flush
```

Danach WooCommerce-Einrichtung im Browser abschließen, Shop-/Warenkorb-/Kassenseiten erstellen, Verkaufsländer festlegen und den Shop erst nach vollständiger Einrichtung freigeben. Für die erste Integration mit dem Gutscheinmodul klassische Warenkorb-/Kassenseiten mit `[woocommerce_cart]` und `[woocommerce_checkout]` verwenden.

1. **Einstellungen → Allgemein:** Website-Titel, Zeitzone und Kontaktadresse prüfen.
2. **WooCommerce → Einstellungen → Zahlungen:** PayPal Payments mit eurem Konto verbinden und Überweisung mit eurer Bankverbindung aktivieren.
3. **Produkte:** Ticketprodukte anlegen; Anleitung unter [Betrieb](docs/BETRIEB.md).
4. **Konzerte:** Beginn, Text, Bild und Ticketprodukt pflegen.
5. **Design → Customizer → Rommelfangen Links:** Social-Media-, Gutscheinprodukt-, Impressums- und Datenschutz-URLs eintragen. Weitere Links über das Footer-Menü.
6. **Beiträge:** Stories und Einblicke erstellen; Galerien/Video-Blöcke und Schlagwörter verwenden. Gästestimmen separat eintragen, Titel = gewünschter öffentlicher Name.
7. Homepage auf die Website-Wurzel legen; das Theme verwendet automatisch seine Konzert-Startseite. Nicht dieselbe Seite als Startseite und Warenkorb/Kasse verwenden.

## Updates mit git pull

```sh
cd /pfad/zum/wordpress-hauptverzeichnis
git status --short
git pull --ff-only
```

Versioniert werden ausschließlich unser Theme, unser Konzertmodul, Dokumentation und Tests. WordPress-Core, `wp-config.php`, `.htaccess`, Uploads, Caches, Sprachen, Drittanbieter-Plugins und die Datenbank bleiben unberührt. Inhalte und Einstellungen werden in WordPress gepflegt. Eigene Codeänderungen vorher committen; bei Konflikten stoppt `--ff-only`.

WordPress und installierte Drittanbieter-Plugins weiterhin über WordPress/WP-CLI aktualisieren. Änderungen zunächst auf einer Testinstallation prüfen, Dateien und Datenbank vorher sichern. Niemals `git clean -fdx` im WordPress-Verzeichnis ausführen: das würde ignorierte WordPress-Dateien und Uploads entfernen.

## Prüfung

```sh
php tests/selection.php
find wp-content/themes/wohnzimmer wp-content/plugins/wohnzimmer-core -name '*.php' -exec php -l {} \;
# Nur auf einer separaten Testinstallation, erstellt und entfernt Testdatensätze:
wp eval-file tests/wordpress.php
```

GitHub Actions prüft PHP-Syntax und die zeitabhängige Highlight-Auswahl. [Betrieb und Abnahme](docs/BETRIEB.md) beschreibt die noch erforderlichen Zahlungstests vor dem Livegang. Dieses Repository enthält keine Hosting-Bereitstellung und keine verbundenen Zahlungskonten.
