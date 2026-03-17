<?php
/**
 * - Praktikum DBWT team-419. Autoren:
 * - Fictor, Marcelbrian, 3721218
 * - Yassir, AIT SI, 3719844
 */

// --- DATENBANKVERBINDUNG HERSTELLEN (Wird auf jeder externen Seite benötigt) ---
$link = mysqli_connect("localhost", "root", "Yassir2003", "emensawerbeseite");

if (!$link) {
    die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
}

mysqli_set_charset($link, "utf8mb4");

$message_wunsch = '';

// --- WUNSCHGERICHT LOGIK (POST REQUESTS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_wunsch'])) {

    $er_name = trim($_POST['er_name'] ?? '');
    $er_email = trim($_POST['er_email'] ?? '');
    $wg_name = trim($_POST['wg_name'] ?? '');
    $wg_beschreibung = trim($_POST['wg_beschreibung'] ?? '');

    // Validierung
    if (empty($er_email) || empty($wg_name)) {
        $message_wunsch = '<p style="color:red;">Name des Wunschgerichts und E-Mail sind Pflichtfelder.</p>';
    } else if (!filter_var($er_email, FILTER_VALIDATE_EMAIL)) {
        $message_wunsch = '<p style="color:red;">Bitte geben Sie eine gültige E-Mail-Adresse ein.</p>';
    } else {
        $er_name_safe = empty($er_name) ? 'anonym' : $er_name;

        // SICHERHEIT (SQL Injection): Maskierung aller Inputs
        $email_safe = mysqli_real_escape_string($link, $er_email);
        $name_safe = mysqli_real_escape_string($link, $er_name_safe);
        $wg_name_safe = mysqli_real_escape_string($link, $wg_name);
        $wg_beschreibung_safe = mysqli_real_escape_string($link, $wg_beschreibung);

        $current_date = date('Y-m-d H:i:s');
        $eid = null;

        // 1. Ersteller prüfen oder neu anlegen
        $sql_select_eid = "SELECT eid FROM ersteller WHERE email = '$email_safe'";
        $result = mysqli_query($link, $sql_select_eid);

        if ($row = mysqli_fetch_assoc($result)) {
            $eid = $row['eid'];
        } else {
            $sql_insert_ersteller = "INSERT INTO ersteller (name, email) VALUES ('$name_safe', '$email_safe')";
            mysqli_query($link, $sql_insert_ersteller);
            $eid = mysqli_insert_id($link);
        }

        // 2. Wunschgericht speichern
        if ($eid) {
            $sql_insert_wunsch = "INSERT INTO wunschgericht (eid, name, beschreibung, erstellungsdatum) 
                                  VALUES ($eid, '$wg_name_safe', '$wg_beschreibung_safe', '$current_date')";

            if (mysqli_query($link, $sql_insert_wunsch)) {
                // SICHERHEIT (XSS): htmlspecialchars bei der Ausgabe
                $message_wunsch = '<p style="color:green;">Ihr Wunschgericht "' . htmlspecialchars($wg_name) . '" wurde erfolgreich übermittelt!</p>';
            } else {
                $message_wunsch = '<p style="color:red;">Fehler beim Speichern der Daten.</p>';
            }
        } else {
            $message_wunsch = '<p style="color:red;">Fehler: Konnte Ersteller nicht verarbeiten.</p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wunschgericht melden | E-Mensa</title>
    <style>
        /* Minimales CSS, idealerweise importiert oder im Header definiert */
        * { font-family: Arial, sans-serif; }
        body { padding: 40px; }
        fieldset { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; }
        legend { font-weight: bold; font-size: 1.1em; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        input[type="text"], input[type="email"], textarea { padding: 8px; border: 1px solid #999; }
        button { padding: 10px 20px; background-color: #57b7c3; color: white; border: none; cursor: pointer; }
        a { color: #57b7c3; text-decoration: none; }
    </style>
</head>
<body>

<p><a href="../../M41/werbeseite/werbeseite.php">Zurück zur Hauptseite</a></p>

<h1>Ihr Wunschgericht: Melden Sie uns Ihre Idee!</h1>

<?php echo $message_wunsch; ?>

<form method="post" action="wunschgericht.php">
    <fieldset>
        <legend>Ihre Kontaktdaten</legend>
        <div class="form-group">
            <label for="er_name">Ihr Name (optional):</label>
            <input type="text" id="er_name" name="er_name" placeholder=""
                   value="<?php echo isset($_POST['submit_wunsch']) ? htmlspecialchars($_POST['er_name'] ?? '') : ''; ?>">
        </div>
        <div class="form-group">
            <label for="er_email">Ihre E-Mail (*):</label>
            <input type="email" id="er_email" name="er_email" required
                   value="<?php echo isset($_POST['submit_wunsch']) ? htmlspecialchars($_POST['er_email'] ?? '') : ''; ?>">
        </div>
    </fieldset>

    <fieldset>
        <legend>Details zum Gericht</legend>
        <div class="form-group">
            <label for="wg_name">Name des Gerichts (*):</label>
            <input type="text" id="wg_name" name="wg_name" required
                   value="<?php echo isset($_POST['submit_wunsch']) ? htmlspecialchars($_POST['wg_name'] ?? '') : ''; ?>">
        </div>
        <div class="form-group">
            <label for="wg_beschreibung">Beschreibung/Hinweise (z.B. Zutaten):</label>
            <textarea id="wg_beschreibung" name="wg_beschreibung" rows="4"><?php echo isset($_POST['submit_wunsch']) ? htmlspecialchars($_POST['wg_beschreibung'] ?? '') : ''; ?></textarea>
        </div>
    </fieldset>

    <button type="submit" name="submit_wunsch">Wunsch abschicken</button>
</form>

<?php
mysqli_close($link);
?>
</body>
</html>