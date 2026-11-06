# Webhosting-Antrag

PHP-Formular zum Beantragen neuer Webspaces (Hochschule Hannover).
Ein Antrag wird in eine lokale MySQL-Datenbank geschrieben.

## Aufbau

| Datei | Zweck |
|---|---|
| `index.php` | Formular + Verarbeitung (PDO, Prepared Statements, CSRF-Schutz) |
| `credentials.example.php` | Vorlage für die DB-Zugangsdaten |
| `schema.sql` | Tabellen-Schema für die Anträge |
| `.gitignore` | schließt die echte `credentials.php` vom Repo aus |

## Installation

1. **Datenbank anlegen** — Schema einspielen:

   ```bash
   mysql -u root -p < schema.sql
   ```

2. **Zugangsdaten anlegen** — Vorlage kopieren und befüllen.
   **Wichtig:** die echte `credentials.php` gehört eine Ebene ÜBER die
   DocumentRoot (nicht ins Webroot), damit sie nicht per HTTP abrufbar ist:

   ```bash
   cp credentials.example.php ../credentials.php   # eine Ebene über docs/
   # Werte in ../credentials.php eintragen …
   ```

3. **Formular ins Webroot legen** — `index.php` in die DocumentRoot kopieren.

## Dateirechte (Sicherheit)

`credentials.php` muss außerhalb der DocumentRoot liegen und nur für den
PHP-Prozess-User lesbar sein:

```bash
chown <php-user>:<php-user> credentials.php
chmod 600 credentials.php
chmod 700 <verzeichnis>
```

Pfad-Anpassung: `index.php` lädt die Datei über `dirname(__DIR__) . '/credentials.php'`.
Liegt die Konfiguration woanders, die Variable `$credentialsPath` in `index.php` anpassen.

## Hinweis

`credentials.php` wird über `.gitignore` niemals versioniert — im Repo liegt nur
die `credentials.example.php` mit Platzhaltern.
