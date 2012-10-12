Kursformat ElateXam
===================

Was bereits geht:
-----------------

- Umsetzung als Course-Format Plugin (siehe http://docs.moodle.org/dev/Course_Format)
- Dozenten und Tutoren sollen in entsprechenden Moodle-Kursen Klausuren erstellen und dabei die bereits vorhandenen Möglichkeiten der Fragenverwaltung (Questionbank) nutzen
- es lassen sich Kategorien anlegen, in denen sich die Fragen einordnen lassen
	- die Kategorien lassen sich verschieben und ineinander verschachteln
- Fragen aus Questionbank lassen sich in die angelegten Kategorien einordnen
	- dabei lässt sich zwischen den Kategorien aus der Questionbank wechseln
- Formular für Metadaten ist angelegt
	- Bereits eingesetzt in Meta-Fragetyp-Plugin (siehe (moodle-root)/question/type/meta)
- Tabellen sind entworfen, siehe db/install.xml bzw. doc/db.png

Was am Mockup noch unfertig ist // mach ich noch fertig
-------------------------------

- Es sollte möglich sein, mehrere Fragen aus Questionbank gleichzeitig in Klausur zu übertragen
	- Button "Use in Exam" unter Questionbank, wenn der gedrückt wird sollen die ausgewählten Fragen in links ausgewählter Kategorie zu sehen sein
- Außerhalb sollte es auch auf Klausurseite Checkboxen geben sowie ein Button, um mehrere Fragen aus der Klausur gleichzeitig entfernen zu können
- Verschieben von Fragen (auf Linker Seite) funktioniert noch nicht
- Mehr als eine Frage (auf linker Seite) wird noch nicht angezeigt

Was noch zu tun ist:
--------------------

- Speichern und Laden funktioniert noch nicht (siehe Tabellenentwurf)
	- Zuordnung Kategorien zu Klausur als Fremdschlüsselbeziehung (n:1)
	- Zuordnung Fragen zu Kategorie als Fremdschlüsselbeziehung (n:1)
	- es ist sicherzustellen, dass eine deutliche Warnung erscheint, wenn
		Frage(n) aus Questionbank gelöscht wird/werden, die in Klausur(en) genutzt wird/werden
- Export (und Import) ins Moodle-XML-Format funktioniert noch nicht
	- Export äquivalent umzusetzen wie die Moodle-interne Fragesammlung
	- Anforderung 1: eine mit dem beschriebenen Plugin erstellte Klausur sollte sich so in Moodle importieren lassen,
				so dass sich die darin enthaltenen Fragen (und Kategorien) auch durch ein unmodifiziertes Moodle eingelesen werden können,
				so als wäre die XML-Datei von der Questionbank exportiert worden
	- Anforderung 2: eine mit dem beschriebenen Plugin erstellte Klausur sollte sich in ElateXam importieren lassen,
				welches ebenfalls von dem besagten Moodle-XML-Format ausgeht
	- Die Daten aus dem Formular für Metadaten sollten so in der XML-Datei erscheinen, als wäre
		es wären die Daten Teil eines entsprechenden Fragetyps -> siehe Fragetyp "Meta"
- Startseite eines Kurses mit dem entsprechenden Kurstyp sollte eine Übersicht über die angelegten Klausuren sein
	- Buttons zum Hinzufügen und importieren von Klausuren
	- Buttons zum Löschen und Bearbeiten der bereits vorhandenen Klausuren
	- Anzeige bestimmter Metadaten aus den Klausuren, z.B. Erstellungsdatum, Datum letzter Bearbeitung

