# Multisite installieren und aktualisieren

## Zielaufbau

```text
Gemeinsames WordPress-Verzeichnis/
├── wp-admin/
├── wp-includes/
├── wp-config.php                     bestehende Netzwerkkonfiguration
└── wp-content/
    ├── uploads/                      Medien je Website
    ├── themes/                       bestehende Themes
    └── plugins/
        ├── taka-platform/            eigenes, unabhängiges Repository
        ├── woocommerce/              separat gepflegt
        └── wohnzimmerkonzerte/       dieses Git-Repository
            ├── .git/
            ├── wohnzimmerkonzerte.php
            ├── includes/
            ├── templates/
            └── assets/
```

Dieses Plugin wird wie TAKA als eigenes Repository installiert. Es importiert keinen TAKA-Code und benötigt TAKA nicht. Gleichzeitige Aktivierung verändert keine TAKA-Datensätze oder Einstellungen. Unterschiedliche Plugins können eigene Frontend-Vorlagen bereitstellen; für die Konzertstartseite deshalb eine eigene Seite verwenden.

## 1. Website und Domain zuordnen

Wenn rommelfangen.de bereits eine Website des Netzwerks ist, diese wiederverwenden. Ansonsten unter **Netzwerkverwaltung → Websites → Hinzufügen** eine Website mit dem Titel „Rommelfangen Living Room Concerts“ anlegen. Zunächst eine normale Adresse innerhalb des vorhandenen Netzwerks verwenden.

Beim Hoster:

1. DNS für rommelfangen.de auf den Server der bestehenden Multisite richten. Die konkreten Werte liefert der Hoster. Bestehende E-Mail-DNS-Einträge beibehalten.
2. Die Domain als zusätzliche Website-Domain derselben WordPress-Installation hinterlegen. Kein separates WordPress installieren und keine bloße Weiterleitung auf die Netzwerk-Hauptdomain einrichten.
3. HTTPS für rommelfangen.de einrichten.
4. Unter **Netzwerkverwaltung → Websites → Bearbeiten → Info** die Website-Adresse auf `https://rommelfangen.de` setzen. Anschließend Frontend und `/wp-admin/` prüfen.
5. Optional www.rommelfangen.de mit eigenem Zertifikat dauerhaft auf https://rommelfangen.de weiterleiten lassen.

Die Hauptdomain des Netzwerks und `DOMAIN_CURRENT_SITE` bleiben erhalten. Keine Multisite-Konstanten pauschal durch rommelfangen.de ersetzen. Bei bestehenden Domain-Mapping- oder Cookie-Sondereinstellungen zuerst die vorhandene Konfiguration prüfen.

Quelle: [WordPress Domain Mapping](https://developer.wordpress.org/advanced-administration/multisite/domain-mapping/).

## 2. GitHub-Zugriff auf dem Server

Wenn dein Server bereits über einen persönlichen GitHub-SSH-Schlüssel Zugriff auf dieses private Repository hat, diesen Zugang verwenden. Ein reiner Deploy-Key von taka-platform gilt dagegen nur für dieses eine Repository; für wohnzimmerkonzerte einen eigenen anlegen.

Auf dem Server, unter deinem Hosting-Benutzer:

```sh
mkdir -p ~/.ssh
ssh-keygen -t ed25519 -f ~/.ssh/rommelfangen_github -C 'rommelfangen.de'
cat ~/.ssh/rommelfangen_github.pub
```

Bei GitHub im Repository **rommelfs/wohnzimmerkonzerte → Settings → Deploy keys → Add deploy key** den öffentlichen `.pub`-Schlüssel eintragen. Schreibzugriff wird zum Aktualisieren nicht benötigt. Den privaten Schlüssel nicht teilen.

Bei einem eigenen Deploy-Key folgenden separaten Host-Eintrag in `~/.ssh/config` ergänzen, ohne vorhandene TAKA-Einträge zu überschreiben:

```text
Host github-rommelfangen
    HostName github.com
    User git
    IdentityFile ~/.ssh/rommelfangen_github
    IdentitiesOnly yes
```

Dann zum Klonen die Adresse `git@github-rommelfangen:rommelfs/wohnzimmerkonzerte.git` verwenden. Bei einem bereits passenden persönlichen Schlüssel genügt `git@github.com:rommelfs/wohnzimmerkonzerte.git`. Den beim ersten Zugriff angezeigten Server-Fingerabdruck anhand der GitHub-Dokumentation prüfen.

Quelle: [GitHub Deploy Keys](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/managing-deploy-keys).

## 3. Plugin klonen

Dateien und Datenbank vorher sichern. Als Hosting-Benutzer arbeiten, der die WordPress-Dateien verwaltet; nicht als root. Der folgende Pfad ist ein Platzhalter:

```sh
cd /pfad/zur/multisite/wp-content/plugins
git clone git@github.com:rommelfs/wohnzimmerkonzerte.git wohnzimmerkonzerte
```

Bei eigenem Deploy-Key die oben beschriebene Alias-Adresse verwenden. Wenn das Zielverzeichnis bereits existiert, zuerst prüfen; nicht darüberkopieren oder ein zweites Repository darin initialisieren. Auch wenn die WordPress-Installation bereits Git verwendet, wird kein neues Repository im WordPress-Hauptverzeichnis angelegt. Das übergeordnete Deployment darf dieses separate Plugin-Verzeichnis nicht überschreiben oder löschen.

Der Hoster sollte HTTP-Zugriffe auf `.git` sperren. `https://rommelfangen.de/wp-content/plugins/wohnzimmerkonzerte/.git/HEAD` darf keine Repository-Inhalte ausliefern, sondern muss mit 403 oder 404 antworten. Diese Sperre gehört in die Serverkonfiguration; das Plugin verändert keine gemeinsam genutzten Serverregeln.

## 4. Nur auf der Konzertwebsite aktivieren

Unter **Netzwerkverwaltung → Plugins** fehlende Drittanbieter-Erweiterungen installieren:

- WooCommerce
- WooCommerce PayPal Payments
- PW WooCommerce Gift Cards

Bereits vorhandene Installationen wiederverwenden. Danach in die **Website-Verwaltung von rommelfangen.de** wechseln und dort WooCommerce, Rommelfangen Living Room Concerts und die beiden Erweiterungen aktivieren. Keine Netzwerkaktivierung verwenden, wenn nur diese Website die Funktionen benötigt. Falls das Plugin-Menü für Website-Admins ausgeblendet ist, als Netzwerkadministrator über die Website-Verwaltung oder WP-CLI aktivieren.

Mit WP-CLI, im gemeinsamen WordPress-Verzeichnis:

```sh
cd /pfad/zur/multisite
wp site list --fields=blog_id,url

# Nur fehlende Erweiterungen installieren:
wp plugin install woocommerce woocommerce-paypal-payments pw-woocommerce-gift-cards

# Jede Aktivierung adressiert ausdrücklich die Konzertwebsite:
wp plugin activate woocommerce --url=https://rommelfangen.de
wp plugin activate wohnzimmerkonzerte --url=https://rommelfangen.de
wp plugin activate woocommerce-paypal-payments pw-woocommerce-gift-cards --url=https://rommelfangen.de
```

Kein `--network` ergänzen. Falls WooCommerce bereits netzwerkweit aktiv ist, diese Aktivierung beibehalten und nur die Konzertwebsite konfigurieren. Netzwerkweite Aktivierungen nicht ohne Prüfung der anderen Websites ändern.

Das Konzertplugin unterstützt auch eine ausdrücklich gewünschte Netzwerkaktivierung. Dann erscheinen die Konzertfunktionen auf allen Websites, und die URL-Regeln werden je Website beim ersten Laden vorbereitet; dies gilt auch für neu angelegte Websites. Inhalte und Einstellungen bleiben trotzdem je Website getrennt. Netzwerk-Deaktivierung entfernt abgeleitete URL-Regeln in Paketen von 100 Websites, aber keine Inhalte. Bei sehr großen Netzwerken diese Aktion in einem Wartungsfenster planen.

## 5. Startseite und Links einrichten

Im Dashboard von rommelfangen.de:

1. Eine Seite „Wohnzimmerkonzerte“ erstellen und einen **Shortcode-Block** mit `[rommelfangen_homepage]` einfügen. Veröffentlichen.
2. Unter **Einstellungen → Lesen** „Eine statische Seite“ auswählen und diese Seite zur Startseite machen. Eine etwa vorhandene bisherige Startseite wird nicht automatisch ersetzt.
3. Unter **Einstellungen → Rommelfangen Concerts** Social-Media-Links, Geschenkgutschein-Produkt sowie Impressums- und Datenschutz-URLs eintragen.
4. Zeitzone Europe/Berlin und gewünschte Sprache kontrollieren.
5. Unter **Einstellungen → Permalinks** die gewünschte Struktur einstellen und speichern. Die bestehenden Multisite-Webserverregeln beibehalten.

Das bisherige Theme bleibt aktiv. Das Plugin übernimmt die Darstellung ausschließlich auf Seiten mit diesem Shortcode, Konzertdetails und dem Konzertarchiv. Normale Seiten und Stories verwenden das vorhandene Theme. Ab Version 1.2 können Warenkorb und Kasse ebenfalls das Konzertdesign verwenden; dies ist in den Website-Einstellungen abschaltbar. Kein Kopierschritt und keine Theme-Aktivierung nach einem `git pull` sind nötig.

Optional per WP-CLI; die angezeigte ID bei den folgenden Befehlen einsetzen:

```sh
wp post create --post_type=page --post_status=publish --post_title='Wohnzimmerkonzerte' --post_content='[rommelfangen_homepage]' --porcelain --url=https://rommelfangen.de
wp option update show_on_front page --url=https://rommelfangen.de
wp option update page_on_front SEITEN_ID --url=https://rommelfangen.de
wp option update timezone_string Europe/Berlin --url=https://rommelfangen.de
```

`wp post create` nur einmal ausführen, damit keine doppelten Seiten entstehen. Die Konfiguration gilt immer für die mit `--url` ausgewählte Website.

## 6. Verkauf vorbereiten

WooCommerce auf rommelfangen.de einrichten: EUR, Verkaufsländer, Shop/Kasse/Warenkorb, Bankverbindung und PayPal-Konto. Globale Lagerverwaltung und Rabattcodes einschalten. Für den ersten Gutscheinablauf klassische Warenkorb-/Kassenseiten mit `[woocommerce_cart]` und `[woocommerce_checkout]` verwenden.

Konzerte, Ticketprodukte, Preise und Gutscheine sind Daten dieser Website. Eine andere Website im Netzwerk führt ihren eigenen Shop; ein gemeinsamer Warenkorb oder gemeinsame Gutscheinguthaben werden durch dieses Plugin nicht eingerichtet. Zahlungs- und Gutschein-Erweiterungen vor dem Livegang auf der konkreten Multisite testen.

Siehe [Betrieb und Abnahme](BETRIEB.md). Quelle: [WooCommerce in Multisite](https://woocommerce.com/document/installing-uninstalling-woocommerce/).

## 7. Spätere Updates

```sh
cd /pfad/zur/multisite/wp-content/plugins/wohnzimmerkonzerte
git status --short
git pull --ff-only
```

Erst Backup und Testwebsite, dann produktiv aktualisieren. `git status --short` sollte vor und nach dem Update leer sein. Bei lokalen Änderungen anhalten und vergleichen. `git pull --ff-only` verhindert unerwartete Merge-Commits, schützt aber nicht vor einer fachlich ungeeigneten Änderung: Startseite, Konzertdetail und Ticketbuchung anschließend kurz prüfen. Website-Cache leeren, falls einer aktiv ist.

Nach einem normalen Update keine Deaktivierung/Aktivierung nötig. Das Plugin aktualisiert seine eigenen URL-Regeln bei Bedarf beim nächsten Aufruf der jeweiligen Website. Inhalte und Einstellungen bleiben in deren Datenbanktabellen. Es überschreibt keine WordPress-Konfiguration, keine Uploads und keine anderen Plugins.

Codeänderungen gelten für jede Website, auf der das Plugin aktiv ist. WordPress-Core und Drittanbieter-Erweiterungen separat über die Netzwerkverwaltung aktualisieren. Auch nach Datenbankänderungen von WooCommerce muss ein Rollback zu einem passenden Datenbankbackup passen; ein Git-Rollback allein reicht dafür nicht.

## Wechsel von Version 1.0

Dieser Abschnitt betrifft nur eine bereits installierte ursprüngliche Version mit Repository im WordPress-Hauptverzeichnis. Falls du noch nichts installiert hast, direkt mit dem neuen Plugin beginnen.

1. Dateien und Datenbank sichern. Im **alten Root-Checkout noch kein `git pull`** ausführen: Die neue Version hat eine andere Verzeichnisstruktur.
2. Das neue Repository separat nach `wp-content/plugins/wohnzimmerkonzerte` klonen.
3. Auf jeder betroffenen Website das alte Plugin `wohnzimmer-core` deaktivieren. Falls es netzwerkweit aktiviert war, zuerst die betroffenen Websites erfassen und die Umstellung koordinieren.
4. Das alte Theme `wohnzimmer` durch ein vorhandenes, WooCommerce-kompatibles WordPress-Theme ersetzen.
5. Das neue Plugin auf der gewünschten Website aktivieren. Es behält die bisherigen Post Types `rl_concert` und `rl_testimonial`, Metafelder und Ticketprodukt-IDs bei. Vorhandene Links aus `theme_mods_wohnzimmer` werden einmalig in websiteeigene Plugin-Einstellungen übernommen.
6. Die Startseite mit dem neuen Shortcode anlegen/auswählen und Einstellungen prüfen. Konzert-URLs bleiben unter `/konzerte/`; URL-Regeln werden neu aufgebaut.
7. Alte Codeverzeichnisse und das alte Root-Repository erst nach erfolgreicher Prüfung gezielt archivieren bzw. aus dem bisherigen Deployment nehmen. Keine pauschalen Löschbefehle im WordPress-Hauptverzeichnis verwenden. Fortan ausschließlich im neuen Plugin-Repository aktualisieren.

Das neue Plugin verweigert die Aktivierung bei gleichzeitig laufendem alten Konzertmodul oder altem Konzert-Theme, um doppelte Funktionsdefinitionen zu vermeiden. Es löscht bei Deaktivierung keine Konzerte, Bestellungen, Uploads oder Einstellungen.

## Gestaltung und Ticketshop ab Version 1.2

Nach dem normalen `git pull` unter **Einstellungen → Rommelfangen Concerts** alle neuen Gestaltungsfelder und Startseitenbereiche bearbeiten. Bestehende Links und Konzertdaten bleiben erhalten. Die neue Konfiguration wird je Website gespeichert. Eine bestehende Startseite mit `[rommelfangen_homepage]` funktioniert weiter; der Ticketshop ist über den neuen Header-Link sofort erreichbar. Anleitung: [Website-Editor und Ticketshop](WEBSITE-EDITOR.md).
