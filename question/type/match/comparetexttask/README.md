# About

In diesem Ordner befinden sich alle Daten, die für die Erstellung
einer Professorensicht für eine ComplexTask Aufgabe erforderlich sind.

Für neuen Fragetyp einfach sämptliche Vorkommnisse von "comparetexttask"
durch die gewünschte Bezeichnung ersetzen, und das sowohl in den
Dateien als auch den Dateinamen!!

Der so entstandene Ordner kann in .../moodle/question/type/
verschoben werden und wird dann als zu installierendes Plugin erkannt,
sobald sich ein Admin bei Moodle anmeldet (übrigens auch wenn
Moodle neu installiert wird).

Das Plugin wurde mit Moodle 2.3 getestet, seit Moodle 1.9
gibt es neue Pluginschnittstellen und somit sind Plugins von
Moodle 1.9 nicht kompatibel mit Moodle 2.3 (und andersrum).

## Moodle Setup for Development:

- disable everything here: http://.../moodle/admin/settings.php?section=ajax
- enable debugging here: http://.../moodle/admin/settings.php?section=debugging
- this tool might come in handy: http://.../moodle/admin/tool/xmldb/
