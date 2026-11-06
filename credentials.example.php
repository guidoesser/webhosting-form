<?php
/**
 * Beispiel-Konfiguration für die MySQL-Anbindung.
 *
 * ANLEITUNG:
 * 1. Diese Datei nach `credentials.php` kopieren — und zwar eine Ebene ÜBER
 *    die DocumentRoot (also NICHT ins Webroot), damit sie per HTTP nicht
 *    erreichbar ist.
 *
 *      cp credentials.example.php ../credentials.php
 *
 * 2. Die Platzhalter durch echte Zugangsdaten ersetzen.
 * 3. Rechte setzen, damit die Datei nur lokal (vom PHP-Prozess-User) lesbar ist:
 *
 *      chown <php-user>:<php-user> credentials.php
 *      chmod 600 credentials.php
 *      chmod 700 <verzeichnis>
 *
 * 4. `credentials.php` steht in `.gitignore` — sie wird NIEMALS ins Repo
 *    committet. Nur diese Beispieldatei (mit Platzhaltern) ist versioniert.
 */
return [
    'host'    => 'localhost',
    'db'      => 'webhosting_antraege',
    'user'    => 'formular',
    'pass'    => 'GEHEIM',
    'charset' => 'utf8mb4',
];
