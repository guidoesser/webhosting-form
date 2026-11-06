<?php
declare(strict_types=1);

/**
 * Webhosting-Antrag — Formular + Verarbeitung
 *
 * Zugangsdaten liegen in credentials.php (AUSSERHALB des Webroots, chmod 600).
 * Pfad bei Bedarf unten anpassen. Siehe credentials.example.php.
 */

// --- 1) Datenbank-Zugangsdaten laden (eine Ebene über dem Webroot) ---
$credentialsPath = dirname(__DIR__) . '/credentials.php';
if (!is_file($credentialsPath)) {
    http_response_code(500);
    exit('Konfigurationsdatei nicht gefunden.');
}
$credentials = require $credentialsPath;

// --- 2) CSRF-Schutz ---
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// --- 3) Zustand ---
$errors  = [];
$success = false;
$old     = [];

$statusOptionen = ['Studierende:r', 'Mitarbeiter:in', 'Lehrende:r', 'Sonstiges'];

// --- 4) Verarbeitung bei POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Ihre Sitzung ist abgelaufen oder die Anfrage war ungültig. Bitte senden Sie das Formular erneut ab.';
    } else {
        $old = [
            'vorname'                 => trim((string)($_POST['vorname'] ?? '')),
            'nachname'                => trim((string)($_POST['nachname'] ?? '')),
            'email'                   => trim((string)($_POST['email'] ?? '')),
            'status'                  => trim((string)($_POST['status'] ?? '')),
            'benutzername'            => trim((string)($_POST['benutzername'] ?? '')),
            'laufzeit_semester'       => (string)($_POST['laufzeit_semester'] ?? ''),
            'projektbeschreibung'     => trim((string)($_POST['projektbeschreibung'] ?? '')),
            'weitere_ansprechpartner' => trim((string)($_POST['weitere_ansprechpartner'] ?? '')),
            'anmerkungen'             => trim((string)($_POST['anmerkungen'] ?? '')),
        ];
        $zustimmung = isset($_POST['zustimmung']);

        if ($old['vorname'] === '') {
            $errors[] = 'Bitte geben Sie Ihren Vornamen an.';
        }
        if ($old['nachname'] === '') {
            $errors[] = 'Bitte geben Sie Ihren Nachnamen an.';
        }
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
        }
        if ($old['status'] === '' || !in_array($old['status'], $statusOptionen, true)) {
            $errors[] = 'Bitte wählen Sie aus, wer Sie sind.';
        }
        if ($old['benutzername'] === '') {
            $errors[] = 'Bitte geben Sie einen Benutzernamen für das Webspace an.';
        } elseif (!preg_match('/^[a-z0-9][a-z0-9-]{1,62}$/i', $old['benutzername'])) {
            $errors[] = 'Der Benutzername darf nur Buchstaben, Ziffern und Bindestriche enthalten.';
        }
        if (!ctype_digit($old['laufzeit_semester']) || (int)$old['laufzeit_semester'] < 1 || (int)$old['laufzeit_semester'] > 6) {
            $errors[] = 'Bitte wählen Sie eine Laufzeit.';
        }
        if ($old['projektbeschreibung'] === '') {
            $errors[] = 'Bitte beschreiben Sie kurz, wofür Sie das Webspace benötigen.';
        }
        if (!$zustimmung) {
            $errors[] = 'Bitte stimmen Sie den Nutzungsbedingungen und der Datenschutzerklärung zu.';
        }

        if (empty($errors)) {
            try {
                $pdo = new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=%s', $credentials['host'], $credentials['db'], $credentials['charset'] ?? 'utf8mb4'),
                    $credentials['user'],
                    $credentials['pass'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
                $stmt = $pdo->prepare(
                    'INSERT INTO webhosting_antraege
                        (vorname, nachname, email, status, benutzername, laufzeit_semester, projektbeschreibung, weitere_ansprechpartner, anmerkungen, nutzungsbedingungen)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $old['vorname'],
                    $old['nachname'],
                    $old['email'],
                    $old['status'],
                    $old['benutzername'],
                    (int)$old['laufzeit_semester'],
                    $old['projektbeschreibung'],
                    $old['weitere_ansprechpartner'],
                    $old['anmerkungen'],
                    $zustimmung ? 1 : 0,
                ]);
                $success = true;
                $old = [];
            } catch (PDOException $e) {
                error_log('Webhosting-Antrag DB-Fehler: ' . $e->getMessage());
                $errors[] = 'Beim Speichern ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Webspace beantragen</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; color: #222; }
        h1 { font-size: 1.6rem; }
        label { font-weight: 600; display: block; margin-top: 1rem; }
        input[type=text], input[type=email], select, textarea { width: 100%; padding: .5rem; margin-top: .25rem; font-size: 1rem; box-sizing: border-box; }
        textarea { min-height: 6rem; resize: vertical; }
        .hinweis { color: #555; font-size: .85rem; margin-top: .25rem; }
        .error { background: #fdecea; border: 1px solid #f5c6cb; color: #721c24; padding: .75rem; border-radius: 4px; margin-bottom: 1rem; }
        .error ul { margin: .25rem 0 0 1.2rem; }
        .success { background: #e6f4ea; border: 1px solid #b7dfc1; color: #155724; padding: .75rem; border-radius: 4px; margin-bottom: 1rem; }
        .checkbox { display: flex; align-items: flex-start; gap: .5rem; margin-top: 1rem; }
        .checkbox input { margin-top: .3rem; }
        .checkbox label { margin-top: 0; font-weight: 400; }
        button { margin-top: 1.5rem; padding: .6rem 1.5rem; font-size: 1rem; cursor: pointer; background: #0b6e4f; color: #fff; border: none; border-radius: 4px; }
        button:hover { background: #095c42; }
        .nutzungsbedingungen { background: #f7f7f7; border: 1px solid #ddd; border-radius: 4px; padding: 1rem; margin-top: 1rem; font-size: .9rem; }
        .nutzungsbedingungen h2 { font-size: 1rem; margin-top: 0; }
        .nutzungsbedingungen ul { margin: .5rem 0 0 1.2rem; padding: 0; }
        .nutzungsbedingungen li { margin-bottom: .4rem; }
    </style>
</head>
<body>
    <h1>Webspace beantragen</h1>

    <p>Dieses Formular ermöglicht Ihnen lediglich das <strong>Beantragen noch nicht vorhandener Webspaces</strong>.</p>
    <p>Sie möchten Ihr Projekt verlängern? Dann benutzen Sie bitte das entsprechende Formular (<a href="https://service.it.hs-hannover.de/de/services/webhosting/webspace-verlaengern/">Webspace verlängern</a>).</p>
    <p>Bei Fragen können Sie uns jederzeit über das <a href="https://service.it.hs-hannover.de/de/services/webhosting/kontakt-webhosting/">Kontaktformular</a> kontaktieren.</p>

    <?php if ($success): ?>
        <div class="success">Vielen Dank! Ihr Antrag wurde übermittelt. Wir melden uns per E-Mail bei Ihnen.</div>
    <?php elseif ($errors): ?>
        <div class="error"><strong>Bitte korrigieren Sie Folgendes:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

        <label for="vorname">Vorname *</label>
        <input type="text" id="vorname" name="vorname" required value="<?= htmlspecialchars($old['vorname'] ?? '') ?>">

        <label for="nachname">Nachname *</label>
        <input type="text" id="nachname" name="nachname" required value="<?= htmlspecialchars($old['nachname'] ?? '') ?>">

        <label for="email">E-Mail-Adresse *</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">

        <label for="status">Ich bin *</label>
        <select id="status" name="status" required>
            <option value="">— bitte wählen —</option>
            <?php foreach ($statusOptionen as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($old['status'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="benutzername">Benutzername *</label>
        <input type="text" id="benutzername" name="benutzername" required value="<?= htmlspecialchars($old['benutzername'] ?? '') ?>">
        <div class="hinweis">Gleichzeitig der Name für das Webspace: <code>benutzername.wp.hs-hannover.de</code></div>

        <label for="laufzeit_semester">Laufzeit in Semestern *</label>
        <select id="laufzeit_semester" name="laufzeit_semester" required>
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <option value="<?= $i ?>" <?= ($old['laufzeit_semester'] ?? '') === (string)$i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <label for="projektbeschreibung">Projektbeschreibung *</label>
        <textarea id="projektbeschreibung" name="projektbeschreibung" required><?= htmlspecialchars($old['projektbeschreibung'] ?? '') ?></textarea>
        <div class="hinweis">Wofür benötigen Sie Ihr Webspace?</div>

        <label for="weitere_ansprechpartner">Weitere Ansprechpartner</label>
        <textarea id="weitere_ansprechpartner" name="weitere_ansprechpartner"><?= htmlspecialchars($old['weitere_ansprechpartner'] ?? '') ?></textarea>
        <div class="hinweis">Bitte geben Sie Namen und E-Mail-Adressen an.</div>

        <label for="anmerkungen">Anmerkungen</label>
        <textarea id="anmerkungen" name="anmerkungen"><?= htmlspecialchars($old['anmerkungen'] ?? '') ?></textarea>

        <div class="checkbox">
            <input type="checkbox" id="zustimmung" name="zustimmung" value="1" required <?= isset($_POST['zustimmung']) ? 'checked' : '' ?>>
            <label for="zustimmung">Ich stimme den <strong>Nutzungsbedingungen</strong>** und der <a href="https://service.it.hs-hannover.de/privacy/">Datenschutzerklärung</a> zu. *</label>
        </div>

        <div class="nutzungsbedingungen">
            <h2>** Nutzungsbedingungen</h2>
            <ul>
                <li>Die inhaltliche und technische Verantwortung für die Software bzw. Internetseite, die Sie bei uns hosten, liegt bei Ihnen. Bitte beachten Sie dabei insbesondere die geltenden Datenschutzrichtlinien. Sollte es zu Verstößen kommen, werden wir den betroffenen Account bis zur Klärung der Sachlage deaktivieren.</li>
                <li>Bei unpassenden oder unangebrachten Namen für den beantragten Webspace dürfen wir diesen Namen anpassen oder ändern.</li>
                <li>Es gilt die <a href="https://service.it.hs-hannover.de/documents/13/ivs-bo.pdf">Benutzerordnung für IV-Systeme</a>.</li>
                <li>Formloser, schriftlicher Antrag per Webformular.</li>
                <li>Die Nennung eines Verantwortlichen sowie ggf. eines technischen Ansprechpartners ist verpflichtend.</li>
                <li>Bei einem Wechsel des Verantwortlichen oder technischen Ansprechpartners müssen Sie uns benachrichtigen.</li>
                <li>Können wir Sie bei Fragen/Problemen nicht erreichen, wird Ihre Seite bis zur Klärung deaktiviert.</li>
            </ul>
        </div>

        <button type="submit">Antrag absenden</button>
    </form>
</body>
</html>
