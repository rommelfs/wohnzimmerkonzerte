# Konzertbetrieb ab Version 1.6

Der Arbeitskatalog aus der Projektprüfung wird in [ARBEITSKATALOG.md](ARBEITSKATALOG.md) mit Abnahmekriterien geführt. Diese Anleitung beschreibt die neuen Betriebsabläufe. Bestehende Website-Inhalte werden nicht zurückgesetzt.

## Konzert vorbereiten

Im Konzerteditor Beginn, Einlass und öffentliche Ortsangabe pflegen. Unter **Veranstaltungsbetrieb (intern)** kann die private Adresse hinterlegt werden. Sie wird nicht im öffentlichen Konzerttemplate oder als öffentliche REST-Metadaten ausgegeben. Ein optionales Konzertende ergänzt den Kalenderdownload; ohne Ende wird keine erfundene Dauer eingetragen.

Das Ticketprodukt behält seine Konzertidentität auch nach Entfernen der Zuordnung, Papierkorb oder endgültiger Löschung. Dadurch werden alte Tickets nicht wieder zu frei kaufbaren Standardprodukten. Für einen anderen Konzertabend ein eigenes Produkt anlegen. Bei bereits vor diesem Update vollständig gelöschten Konzerten ohne verbliebenen Bezug alte Produkte manuell kontrollieren; fehlende historische Zuordnungen lassen sich nicht zuverlässig erraten.

## Bestellung und persönliche Buchungsinformation

Neue Ticketpositionen speichern Konzertbezug, Beginn und Einlass als Bestellpositionsdaten. Die öffentliche Ortsangabe ist ebenfalls Teil der Buchung. Spätere redaktionelle Änderungen überschreiben diesen Nachweis nicht.

Erst bei **In Bearbeitung / Abgeschlossen** wird eine persönliche Buchungsinformation je Konzert und Bestellung eingeplant. Sie nennt Datum, Einlass, Plätze, Bestellnummer und den hinterlegten Treffpunkt. Eine private Adresse wird erst bei Bezahlung in der Buchung gesichert. Wiederholte Zahlungsstatus-Hooks erzeugen keinen zweiten identischen Auftrag. Ohne private Adresse wird die öffentliche Ortsangabe verwendet; die Angaben daher vor dem Verkauf vollständig pflegen.

Diese Information ist unabhängig von einer Newsletter-Anmeldung. Sie wird über denselben konfigurierten Absender und serverseitigen Mailweg versendet. Der Versandstatus steht unter **Konzerte → Benachrichtigungen**. Eine Mailserver-Annahme bleibt von der tatsächlichen Zustellung zu unterscheiden.

## Newsletter und Bestellermails trennen

Im Nachrichteneditor unter **Konzerte → Benachrichtigungen** den Empfängerkreis ausdrücklich wählen:

- **Freiwillige Newsletter-Abonnenten:** bestätigte Anmeldungen, mit persönlichem Abmeldelink.
- **Bezahlte Besteller:** Bestellungen in Bearbeitung oder abgeschlossen, nur Positionen mit verbleibenden Plätzen.
- **Offene Bestellungen:** ausstehende Zahlungen und wartende Überweisungen.
- **Alle aktiven Besteller:** Kombination der obigen Gruppen; stornierte, fehlgeschlagene oder vollständig erstattete Bestellungen gehören nicht dazu.

Mehrere Buchungen derselben Adresse für einen Abend ergeben eine gemeinsame Nachricht im gewählten Empfängerkreis. Der aktuelle Bestellstatus wird vor der tatsächlichen Übergabe erneut geprüft. Automatische Buchungsinformationen bleiben dagegen auf ihre jeweilige Bestellung beschränkt.

Bestellermails verwenden einen Buchungshinweis statt eines Newsletter-Abmeldelinks. Eine Newsletter-Abmeldung verhindert notwendige Informationen zur eigenen Buchung nicht. Newsletter-Abonnenten werden nicht automatisch aus Bestellungen importiert.

Zusätzliche Platzhalter für Bestellermails: `{Bestellnummer}`, `{Tickets}`, `{Einlass}` und `{Adresse}`. Die private `{Adresse}` ist ausschließlich im Empfängerkreis **Bezahlte Besteller** erlaubt. Bei Newsletter oder offenen Bestellungen verweigert der Editor diesen Platzhalter. In der Vorschau stehen Beispieldaten; reale Adressen werden nicht in eine allgemein zugängliche Vorschau geschrieben.

## Konzertübersicht, Gästeliste und Einlass

Unter **Konzerte → Konzertbetrieb** einen Abend wählen. Die Ansicht zeigt bezahlte Plätze, offene/reservierte Plätze, freien Produktbestand, eingecheckte Plätze und bezahlten Positionsumsatz netto nach Erstattungen. Die Bestellungen bleiben WooCommerce-Bestellungen, einschließlich Unterstützung seiner Bestellspeicherung.

Die Gästeliste enthält Käufer, E-Mail, Bestellnummer, Zahlungsstatus und verbleibende Ticketmenge. Teil-Erstattungen reduzieren nur die betroffene Position. Einlass wird als Zahl von 0 bis zur bezahlten Restmenge gespeichert. Offene Buchungen sind nicht eincheckbar. Dafür ist kein zusätzliches Kundenkonto und keine zwingende Erfassung aller Mitreisenden nötig.

**CSV** exportiert die Liste, **Drucken** öffnet die Browser-Druckfunktion. Zugang benötigen berechtigte WooCommerce-Verwalter oder Administratoren. Eine öffentliche Gästeliste wird nicht angeboten.

Freiplätze für Künstler und Helfer vor dem Verkauf vom Produktbestand abziehen. Weitere Tarife mit gemeinsamem Kontingent und QR-Scanner sind spätere Ausbaustufen.

## Absage oder Verschiebung

Die Konzertübersicht bietet eine bewusst zu bestätigende **Absage**. Sie sperrt den Verkauf, löst aber keine echte Zahlungserstattung aus. Der Nachrichteneditor erlaubt anschließend weiterhin Bestellermails für diesen abgesagten Abend. Ein Newsletter-Versand zur Bewerbung eines abgesagten Abends bleibt gesperrt.

Zunächst die betroffenen Besteller informieren. Danach über den Link der jeweiligen Bestellung nur die betroffene Konzertposition in WooCommerce erstatten und über die Wiedereinlagerung entscheiden. Andere Konzerte derselben Bestellung bleiben erhalten. Echte Auszahlungen werden ausschließlich im bestehenden WooCommerce-/Zahlungssystem bewusst bestätigt.

Bei einer Verschiebung den neuen Termin redaktionell setzen und die Besteller mit einer eigenen Nachricht über die Änderung informieren. Die ursprüngliche Buchungsinformation bleibt als Nachweis erhalten; der Kalenderlink zeigt den aktuellen öffentlichen Termin. Es gibt keine unbemerkte automatische Umbuchung oder Auszahlung.

## Überweisungsfristen

Neue Überweisungsbestellungen erhalten beim Wechsel auf **Wartend / On hold** eine Frist von standardmäßig drei Tagen, spätestens bis zum frühesten gebuchten Konzertbeginn. Im Konzertbetrieb ist sie sichtbar. Eine Zahlungserinnerung wird einmalig am letzten Tag der Frist bzw. bei der nächsten Betriebsprüfung danach gesendet. Abgesagte oder nicht mehr veröffentlichte Konzerte werden nicht zur Zahlung aufgefordert.

**Automatische Stornierung ist zunächst ausgeschaltet.** Sie kann unter den Betriebseinstellungen bewusst aktiviert werden. Dann wird eine überfällige, weiterhin unbezahlte Überweisungsbestellung vollständig storniert und der Bestand durch WooCommerce wieder freigegeben. Bei gemischten unbezahlten Bestellungen betrifft dies alle Positionen, weil die Bestellung als Ganzes unbezahlt ist. Ohne Aktivierung überfällige Buchungen weiterhin regelmäßig manuell bearbeiten.

Vor dem Update bestehende offene Bestellungen erhalten keine rückwirkend erfundene neue Frist. Der Hosting-Cron muss zuverlässig laufen, bei Multisite pro aktiver Konzertwebsite. Die Betriebsprüfung wird stündlich eingeplant.

## Warteliste und Kalender

Ausverkaufte Konzerte erhalten auf Detail- und Shopseite ein separates Wartelistenformular. Die Anmeldung hat eine eigene Bestätigung mit 48-Stunden-Link. Sie reserviert keine Plätze und verändert keine Newsletter-Einwilligung.

Wenn wieder Bestand verfügbar ist, werden bestätigte Wartende in Anmeldereihenfolge über die Buchungsmöglichkeit informiert; pro Lauf höchstens 20 pro Konzert und nicht mehr als der aktuell verfügbare Bestand. Jeder Eintrag wird einmal benachrichtigt. Der Hinweis ist **unverbindlich**, ohne exklusive Kaufzusage; der öffentliche Bestand kann sich gleichzeitig ändern. Garantierte, zeitlich reservierte Nachrückangebote sind ein eigener späterer Ausbau und werden hier nicht vorgetäuscht.

Unbestätigte Einträge werden nach sieben Tagen entfernt, verbleibende Einträge spätestens eine Woche nach dem Konzert. Abmeldung ist ohne Login möglich. Wartelistendaten sind in WordPress-Datenauskunft und -löschung enthalten.

Auf der Konzertdetailseite und in der Bestellansicht kann ein Kalendertermin geladen werden. Er enthält ausschließlich die öffentliche Ortsangabe, niemals die private Adresse. Zeitzonen werden über den Website-Termin in UTC übersetzt. Ein abgesagter öffentlicher Termin trägt den entsprechenden Kalenderstatus.

## Website und weitere Abnahme

Der Newsletter ist ein eigener sortierbarer Startseitenbereich. Bestehenden Layouts wird er einmalig hinzugefügt, ohne ihre Inhalte zurückzusetzen. Er lässt sich danach wie andere Bereiche verschieben, deaktivieren oder entfernen. Leere dynamische Bereiche werden auf Wunsch automatisch ausgeblendet. Rückmeldungen zu An-, Bestätigung und Abmeldung verwenden den Konzertseitenrahmen.

Das Backend bietet einen bearbeitbaren FAQ-/Hinweisbereich. Echte Gastgebertexte, Bilder, konkrete Zugangsinformationen und Anreiseangaben kommen von euch; sie werden nicht erfunden. Der mobile Shop zeigt Kaufdaten vor dem großen Bild und bei gefülltem Warenkorb einen dauerhaften Weg zur Kasse. Virtuelle Warenkörbe benötigen keine allgemeinen Versandnotizen.

Vor dem produktiven Einsatz weiterhin tatsächliche Postfix-Zustellung, Zahlungsanbieter, Gutscheine und Rückerstattungen mit den eigenen Konten prüfen. Die automatisierten Tests führen keine externen Zahlungen durch und verschicken keine echten Mails.

Unter den Betriebseinstellungen kann der wartende Newsletter-/Bestellerversand pausiert werden. Bestätigungslinks und Zahlungserinnerungen bleiben dabei aktiv. Der Mailbereich zeigt die Pause sichtbar an. Dort kann außerdem eine Testmail an die Adresse des angemeldeten Administrators angefordert werden; damit den tatsächlichen Posteingang und Absender vor dem ersten Verteiler prüfen. Eine erfolgreiche Übergabe ist noch kein Zustellbeleg.

### Bisherige Newsletter-Einwilligungen

Der Editor trennt **Neue Konzertankündigungen** und **Neuigkeiten / Rückblicke**. Ältere, ausschließlich für neue Ankündigungen erteilte Einwilligungen werden nicht still erweitert. Für Rückblicke werden nur bestätigte Anmeldungen mit dem erweiterten Themenumfang ausgewählt. Über das Anmeldeformular oder Kundenkonto kann eine neue Bestätigung angefordert werden. Bis zum Bestätigungsklick bleibt die ursprüngliche Anmeldung aktiv und ihr Nachweis unverändert; eine anonyme Anfrage überschreibt auch kein Namensprofil. Schon vor dem Update versandte Bestätigungslinks behalten ihren ursprünglichen Themenumfang.
