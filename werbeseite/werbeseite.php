<?php
/**
 * - Praktikum DBWT team-419. Autoren:
 * - Fictor, Marcelbrian, 3721218
 * - Yassir, AIT SI, 3719844
 */

$newsletterFile = './newsletter.txt';

// Datenbankverbindung herstellen
$link = mysqli_connect("localhost", "root", "root", "emensawerbeseite");

if (!$link) {
    die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
}

mysqli_set_charset($link, "utf8mb4");

// --- FORMULARLOGIK  ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A) NEWSLETTER ANMELDUNG
    if (isset($_POST['newsletter_submit'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $sprache = $_POST['sprache'] ?? 'deutsch';
        $datenschutz = $_POST['datenschutz'] ?? '';

        if (!empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($datenschutz)) {
            $clean_email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $clean_name = htmlspecialchars($name);
            $clean_sprache = htmlspecialchars($sprache);

            $line =  $clean_email . ';' . $clean_sprache . ';' . $clean_name . "\n";
            file_put_contents($newsletterFile, $line, FILE_APPEND | LOCK_EX);
        }
    }

}

// --- DATEN ABFRAGEN ---

// 1. Besucherzähler
$sqlUpdate = "UPDATE besucher SET anzahl = anzahl + 1";
mysqli_query($link, $sqlUpdate);

$sqlCounter = "SELECT anzahl FROM besucher";
$resultCounter = mysqli_query($link, $sqlCounter);
$besucherzaehler = 0;

if ($resultCounter && mysqli_num_rows($resultCounter) > 0) {
    $rowCounter = mysqli_fetch_assoc($resultCounter);
    $besucherzaehler = (int)$rowCounter['anzahl'];
    mysqli_free_result($resultCounter);
}
// 2. Anzahl der Gerichte
$sqlCount = "SELECT COUNT(*) as cnt FROM gericht";
$resCount = mysqli_query($link, $sqlCount);
$anzahl_gerichte = 0;
if ($resCount && mysqli_num_rows($resCount) > 0) {
    $rowCount = mysqli_fetch_assoc($resCount);
    $anzahl_gerichte = $rowCount['cnt'];
    mysqli_free_result($resCount);
}

// 3. Newsletter Anmeldungen
$newsletter_anmeldungen = 0;
if (file_exists($newsletterFile)) {
    $lines = file($newsletterFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newsletter_anmeldungen = count($lines);
}

// 4. Speisen laden
$sortierung = "ASC";
if (isset($_GET['sort'])) {
    $allowed_sorts = ['asc', 'desc'];
    $selected_sort = strtolower($_GET['sort']);
    if (in_array($selected_sort, $allowed_sorts)) {
        $sortierung = strtoupper($selected_sort);
    }
}

$sql = "SELECT id, name, preisintern, preisextern 
        FROM gericht 
        ORDER BY name $sortierung 
        LIMIT 5";
$resultGerichte = mysqli_query($link, $sql);
if (!$resultGerichte) {
    die("Fehler bei der Abfrage.");
}

// 5. Allergene laden (Nur verwendete)
$sqlAllergens = "SELECT DISTINCT a.code, a.name 
                 FROM allergen a
                 JOIN gericht_hat_allergen gha ON a.code = gha.code
                 ORDER BY a.code";
$resultAllergens = mysqli_query($link, $sqlAllergens);
if (!$resultAllergens) {
    die("Fehler bei der Allergen-Abfrage.");
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ihre E-Mensa</title>
    <style>
        /* CSS bleibt unverändert */
        * { font-family: Arial, sans-serif; }
        body { margin: 0; padding: 0; background-color: white; color: #333; }
        header { padding: 10px 40px; border-bottom: 2px solid #ddd; background-color: white; }
        .page-grid { display: grid; grid-template-columns: 1fr 1000px 1fr; background-color: white; }
        .middle { padding: 20px; }
        #logo { border: 2px solid #333; padding: 12px 25px; font-weight: bold; }
        nav { display: flex; gap: 30px; margin-left: 20px; border: 2px solid #333; padding: 20px; }
        nav a { color: #57b7c3; }
        section { margin-bottom: 50px; }
        h2 { font-size: 1.4em; margin-bottom: 20px; font-weight: normal; }
        p { line-height: 1.6; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #333; text-align: left; }
        th { font-weight: bold; }
        .stats-container { display: flex; justify-content: space-around; margin: 30px 0; }
        .stat { text-align: center; }
        form { margin-top: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        label { margin-bottom: 5px; font-size: 0.95em; }
        input[type="text"], input[type="email"], select { padding: 8px; border: 1px solid #999; background-color: #f9f9f9; }
        .form-bottom { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: center; }
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { margin-right: 8px; }
        button { padding: 10px 20px; background-color: #ddd; border: 1px solid #999; width: 100%; }
        ul { list-style-type: disc; padding-left: 25px; line-height: 1.8; }
        .center-text { text-align: center; margin: 40px 0; font-size: 1.6em; font-weight: normal; }
        .rectangle { width: 100%; height: 180px; border: 2px solid; background-color: #fafafa; display: flex; align-items: center; justify-content: center; margin: 10px auto; }
        .image_container { max-width: 960px; margin: 0 auto; background-color: white; }
        footer { text-align: center; font-size: 0.95em; color: #333; background-color: white; }
        footer ul { list-style: none; display: flex; gap: 0; justify-content: center; }
        #space li + li::before { content: " | "; }
        #clr { color: #57b7c3; }
        .header-content { width: 900px; margin: 0 auto; }
        .sort-link { color: #57b7c3; font-weight: bold; text-decoration: none; margin: 0 5px; }
    </style>
</head>
<body>

<header>
    <span class="img"><img src="../logo.png" alt="E-Mensa Logo" id="logo"></span>
    <div class="header-content">
        <nav>
            <a href="#ankuendigung">Ankündigung</a>
            <a href="#speisen">Speisen</a>
            <a href="#zahlen">Zahlen</a>
            <a href="#kontakt">Kontakt</a>
            <a href="wunschgericht.php">Wunschgericht</a>
            <a href="#wichtig">Wichtig für uns</a>
        </nav>
    </div>
</header>

<div class="page-grid">
    <div class="left"></div>

    <div class="middle">
        <div class="image_container">
            <div class="rectangle"><span>✕</span></div>
        </div>

        <main>
            <section id="ankuendigung">
                <h2>Bald gibt es Essen auch online ;)</h2>
                <p style="border: 2px solid #333;text-align: justify;">
                    Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
                </p>
            </section>

            <section id="speisen">
                <h2>Köstlichkeiten, die Sie erwarten</h2>

                <p>
                    Sortieren nach Name:
                    <a href="?sort=asc" class="sort-link">Aufsteigend</a> |
                    <a href="?sort=desc" class="sort-link">Absteigend</a>
                </p>

                <table>
                    <thead>
                    <tr>
                        <th>Gericht</th>
                        <th>Preis intern</th>
                        <th>Preis extern</th>
                        <th>Allergene</th> </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($resultGerichte && mysqli_num_rows($resultGerichte) > 0) {
                        mysqli_data_seek($resultGerichte, 0);
                        while ($row = mysqli_fetch_assoc($resultGerichte)):
                            $g_id = $row['id'];
                            $sqlAllergene = "SELECT code FROM gericht_hat_allergen WHERE gericht_id = $g_id";
                            $resAllergene = mysqli_query($link, $sqlAllergene);

                            $allergeneListe = [];
                            while($a = mysqli_fetch_assoc($resAllergene)) {
                                $allergeneListe[] = $a['code'];
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo number_format($row['preisintern'], 2, ',', '') . '€'; ?></td>
                                <td><?php echo number_format($row['preisextern'], 2, ',', '') . '€'; ?></td>
                                <td>
                                    <?php
                                    if (empty($allergeneListe)) {
                                        echo "Keine";
                                    } else {
                                        echo htmlspecialchars(implode(', ', $allergeneListe));
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile;
                    } else {
                        echo '<tr><td colspan="4">Keine Gerichte verfügbar.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>

                <h3>Verwendete Allergene:</h3>
                <ul style="list-style-type: none; padding: 0; font-size: 0.9em;">
                    <?php
                    if ($resultAllergens && mysqli_num_rows($resultAllergens) > 0) {
                        while ($allergenRow = mysqli_fetch_assoc($resultAllergens)) {
                            echo '<li><strong>' . htmlspecialchars($allergenRow['code']) . ':</strong> ' . htmlspecialchars($allergenRow['name']) . '</li>';
                        }
                    } else {
                        echo '<li>Keine Allergene definiert.</li>';
                    }
                    ?>
                </ul>

                <?php
                if ($resultGerichte) mysqli_free_result($resultGerichte);
                if ($resultAllergens) mysqli_free_result($resultAllergens);
                ?>
            </section>

            <section id="zahlen">
                <h2>E-Mensa in Zahlen</h2>
                <div class="stats-container">
                    <div class="stat"><span><?php echo $besucherzaehler; ?></span> Besuche</div>
                    <div class="stat"><span><?php echo $newsletter_anmeldungen; ?></span> Anmeldungen zum<br>Newsletter</div>
                    <div class="stat"><span><?php echo $anzahl_gerichte; ?></span> Speisen</div>
                </div>
            </section>

            <section id="kontakt">
                <h2>Interesse geweckt? Wir informieren Sie!</h2>

                <form method="post" action="#kontakt">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Ihr Name:</label>
                            <input type="text" id="name" name="name" placeholder="Vorname"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Ihre E-Mail:</label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="sprache">Newsletter bitte in:</label>
                            <select id="sprache" name="sprache">
                                <option value="deutsch" <?php echo ($_POST['sprache'] ?? 'deutsch') === 'deutsch' ? 'selected' : ''; ?>>Deutsch</option>
                                <option value="englisch" <?php echo ($_POST['sprache'] ?? '') === 'englisch' ? 'selected' : ''; ?>>Englisch</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-bottom">
                        <div class="checkbox-group">
                            <input type="checkbox" id="datenschutz" name="datenschutz" value="true"
                                    <?php echo isset($_POST['datenschutz']) ? 'checked' : ''; ?>>
                            <label for="datenschutz">Den Datenschutzbestimmungen stimme ich zu</label>
                        </div>
                        <button type="submit" name="newsletter_submit">Zum Newsletter anmelden</button>
                    </div>
                </form>
            </section>

            <section id="wichtig">
                <h2>Das ist uns wichtig</h2>
                <ul style="margin-left: 100px">
                    <li>Beste frische saisonale Zutaten</li>
                    <li>Ausgewogene abwechslungsreiche Gerichte</li>
                    <li>Sauberkeit</li>
                </ul>
            </section>

            <h2 class="center-text">Wir freuen uns auf Ihren Besuch!</h2>
            <hr>
            <footer>
                <ul id="space">
                    <li>(c) E-Mensa GmbH</li>
                    <li>Yassir AIT SI ; Fictor Marcelbrian</li>
                    <li><a href="Impressum" id="clr">Impressum</a></li>
                </ul>
            </footer>
        </main>
    </div>

    <div class="right"></div>
</div>

<?php
mysqli_close($link);

?>
</body>
</html>