# Redaktion, Tickets und Gutscheine

## Ein Konzert veröffentlichen

1. Unter Produkte ein **einfaches, virtuelles** Produkt je Konzert anlegen, z. B. „Ticket · Künstler · Datum“. Preis setzen.
2. Lagerverwaltung am Produkt aktivieren. Bestand = verkaufbare Plätze, also Gesamtkapazität abzüglich Künstler-, Helfer- und anderer reservierter Plätze. Nachbestellungen **nicht erlauben**. Das Produkt keinem zweiten Konzert zuordnen.
3. Konzert unter „Konzerte“ erstellen. Beginn inklusive Uhrzeit, Einlass und öffentliche Ortsangabe pflegen. Eine private Wohnzimmeradresse nicht öffentlich eintragen; diese bei Bedarf separat an bestätigte Gäste senden.
4. Ticketprodukt auswählen. Ungültige oder bereits zugeordnete Produkte werden abgewiesen. Erst das Konzert veröffentlichen, dann den Ticketverkauf prüfen. Unveröffentlichte Ticketprodukte werden nicht verlinkt.
5. Beitragsbild, Beschreibung und Auszug pflegen. Bilder, Videos und Galerien können in den Konzerttext. Mit Tags Künstler, Genre oder Saison zuordnen.

Tickets werden über die normale WooCommerce-Kasse verkauft; die Bestellbestätigung dient als Buchungsnachweis. Ein QR-Ticket-/Einlassscanner ist nicht Bestandteil dieser Version. Bestellungen in WooCommerce sind die Gästeliste. Keine separate Kapazität parallel zum WooCommerce-Bestand führen. Bei mehreren Tarifarten ist eine Erweiterung mit gemeinsamem Kontingent notwendig; diese Version verwendet einen Tarif pro Konzert.

## Bestände und Überweisung

Globale Lagerverwaltung einschalten. WooCommerce verwaltet Bestandsabzug und Reservierungen, unser Modul baut keine zweite Bestandslogik auf.

- „Bestand halten: 30 Minuten“ betrifft Bestellungen mit Status **Zahlung ausstehend**. Der Hosting-Cron muss zuverlässig laufen; bei wenig Besuchern einen echten Cron für WordPress einrichten.
- Überweisungsbestellungen stehen üblicherweise auf **Wartend / On hold**. Diese Bestellungen blockieren Plätze bis zur Zahlung oder Stornierung. Die 30-Minuten-Einstellung gibt sie **nicht** automatisch frei.
- Kommuniziert eine Zahlungsfrist, etwa drei Werktage, und kontrolliert offene Überweisungen täglich. Nur nach tatsächlichem Eingang auf „In Bearbeitung“ oder „Abgeschlossen“ setzen. Unbezahlte Buchungen nach der vereinbarten Frist stornieren und die Freigabe im Produktbestand kontrollieren.
- Bei Erstattungen ausdrücklich entscheiden, ob Plätze wieder eingelagert werden. Keine Plätze über die reale Raumkapazität hinaus hinzufügen.
- Konzertabsage sperrt weitere Ticketkäufe. Bereits bezahlte Buchungen werden dadurch nicht automatisch erstattet; Gäste informieren und Erstattungen im jeweiligen Zahlungssystem bearbeiten.

Quellen: [WooCommerce Lagerverwaltung](https://woocommerce.com/document/configuring-woocommerce-settings/products/), [Überweisung](https://woocommerce.com/document/bacs/).

## Rabattcodes

Unter Marketing → Gutscheine WooCommerce-Coupons anlegen. Prozent- oder Festbetrag, Beschränkung auf Ticketprodukte, Ablaufdatum, Gesamtnutzungslimit und Limit pro Kunde nach Bedarf einstellen. Geschenkgutschein-Produkte von Rabattaktionen ausschließen. Rabattcodes sind keine Guthabenkarten.

Quelle: [WooCommerce Coupons](https://woocommerce.com/document/coupon-management/).

## Geschenkgutscheine mit Restguthaben

**PW WooCommerce Gift Cards** installieren und den Einrichtungsdialog durchlaufen. Ein Gutscheinprodukt mit euren Beträgen, z. B. 25 / 50 / 75 Euro, erstellen. Das veröffentlichte Produkt unter Einstellungen → Rommelfangen Concerts als Geschenkgutschein-URL eintragen. Es wird nicht als Konzertticket verknüpft.

Das Plugin verwaltet Codes, Versand und Guthaben. Ausgabe erst nach bestätigter Zahlung konfigurieren. Bei Überweisung Zahlungseingang kontrollieren und Bestellung anschließend abschließen. Nicht „bei Bestellungseingang senden“ aktivieren. E-Mail-Versand mit einem echten Postfach testen und SMTP bzw. den Versanddienst des Hosts einrichten.

Gutscheine können für Tickets eingelöst werden, Restguthaben bleibt erhalten. Laufzeit, Bedingungen, steuerliche Behandlung und Erstattungsregeln für euren tatsächlichen Betrieb festlegen. Die Erweiterung wird separat gepflegt und nicht in dieses Repository kopiert.

Quellen: [Erweiterung](https://wordpress.org/plugins/pw-woocommerce-gift-cards/), [Gutscheinnummern und Zahlungsstatus](https://www.pimwick.com/pw-faq/gift-card-number-not-generated-or-emailed/), [E-Mail-Versand](https://www.pimwick.com/pw-faq/gift-card-emails-not-being-sent-or-received/).

## Fotos, Videos, Stories und Gästestimmen

Beiträge sind das Konzerttagebuch: Kategorien wie „Stories“ und „Behind the Scenes“, Tags für Künstler oder Genres. Die Startseite zeigt die sechs neuesten Beiträge. Für Videos bevorzugt die WordPress-Videoblöcke mit eigenen Dateien verwenden; externe Einbettungen bewusst einrichten. Fotos mit Alternativtext versehen und nur mit passender Freigabe veröffentlichen. Gästestimmen erscheinen erst, wenn ihr sie ausdrücklich veröffentlicht; es gibt keine erfundenen Beispielzitate.

## Vor der ersten echten Buchung

- Highlight mit zwei zukünftigen Terminen, mit ausschließlich vergangenen Terminen und ohne Termine prüfen. Abgesagte Termine werden bei der automatischen Auswahl ausgelassen.
- Ein Ticket mit kleinem Testbestand anlegen. Zwei parallele Checkouts auf den letzten Platz testen; keine Überbuchung, keine Nachbestellung. Warenkorb nach Konzertbeginn erneut prüfen.
- PayPal im Sandbox-Modus vollständig bezahlen, Abbruch und Erstattung testen. Erst danach Live-Konto aktivieren. [PayPal-Einrichtung](https://woocommerce.com/document/woocommerce-paypal-payments/account-setup-and-onboarding/).
- Überweisungsbestellung anlegen, Bestandsblockierung prüfen, Zahlung manuell bestätigen; auch Stornierung und Freigabe testen.
- Rabattcode mit Limit, Ablauf und Produktbeschränkung prüfen.
- Geschenkgutschein mit PayPal und separat per Überweisung kaufen; kein Versand vor Zahlung. Teilbetrag auf Ticketbestellung einlösen, verbleibendes Guthaben erneut einlösen; gleiche Karte in parallelen Bestellungen und Erstattung prüfen.
- E-Mails, Mobilansicht, Tastaturbedienung, Bilder und Videos prüfen.
- Impressum, Datenschutz, tatsächliche Preis-/Steuerangaben und Verkaufsbedingungen fertigstellen. Bankverbindung, echte Konzertdaten, Fotos und Social-Links eintragen. Backups und Wiederherstellung einrichten.

Automatisierte lokale Tests ersetzen weder einen echten Zahlungstest noch die Einrichtung eurer Konten.

## Verifikation

Die automatisierten Prüfungen umfassen Highlight-Auswahl, Beginn/Zeitzone, Ticketzuordnung, Verkaufssperren, Bestandsabzug und Rückbuchung. Zusätzlich wird der Plugin-Aufbau in einer Multisite mit getrennten Websites geprüft; Details unter [Multisite-Tests](../tests/MULTISITE.md).

Vor dem Livegang bleiben visuelle Prüfung mit eurem aktiven Theme, externe PayPal-Zahlungen, tatsächlicher Gutscheinversand und vollständige Gutschein-Kassenabläufe mit euren Konten erforderlich. Automatisierte Tests verschicken keine E-Mails und führen keine externen Zahlungen aus.

## Website und konzertübergreifender Ticketshop

Die Gestaltung, sortierbaren Bereiche und die neue Über-uns-Abteilung werden unter **Einstellungen → Rommelfangen Concerts** gepflegt. Jedes Konzert kann getrennte Bilder und Farben für Ankündigung und Rückblick erhalten. Der Ticketshop übernimmt den ausgewählten Abend und erlaubt Tickets mehrerer Konzerte in einem WooCommerce-Warenkorb. [Vollständige Bedienungsanleitung](WEBSITE-EDITOR.md).
