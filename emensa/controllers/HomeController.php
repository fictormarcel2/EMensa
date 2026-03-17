<?php
// Import ALL your models
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/gericht.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/kategorie.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/besucher.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/newsletter_anmeldung.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/wunschgericht.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/ersteller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/allergen.php');

class HomeController
{
    public function index(RequestData $request) {

        //  ZUGRIFFSSCHUTZ
        // Prüfen, ob der Benutzer eingeloggt ist (Variable aus dem AuthController)
        if (!isset($_SESSION['login_ok']) || $_SESSION['login_ok'] !== true) {
            // Nicht eingeloggt? Dann sofort zur Anmeldung umleiten!
            header('Location: /anmeldung');
            exit();
        }


       // aufgabe 3 monolg
        logger()->info('Zugriff auf die Hauptseite');
        // 1. BESUCHERZÄHLER
        // Note: insertBesucher expects an IP. We use REMOTE_ADDR.
        insertBesucher($_SERVER['REMOTE_ADDR']);
        $besucherCount = getBesucherCount();

        // 2. NEWSLETTER
        $anmeldungMessage = '';
        $emailExistMessage = '';
        $nameInvalidMessage = '';
        $emailInvalidMessage = '';

        if (isset($_POST['newsletter_submit'])) {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $sprache = $_POST['sprache'] ?? 'deutsch';
            $datenschutz = $_POST['datenschutz'] ?? '';

            // Validation
            if (empty($name)) {
                $nameInvalidMessage = 'Name ist erforderlich.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailInvalidMessage = 'Email ist ungültig.';
            } elseif (empty($datenschutz)) {
                $anmeldungMessage = 'Bitte Datenschutz akzeptieren.';
            } elseif (isEmailExistsInDatabase($email)) {
                $emailExistMessage = 'Email existiert bereits.';
            } else {
                // Save to DB
                $success = insertNewsletterAnmeldung($name, $email, $sprache);
                if ($success) {
                    $anmeldungMessage = "Anmeldung erfolgreich!";
                }
            }
        }
        $anmeldungCount = getAnmeldungCount();

        // 3. GERICHTE & ALLERGENE
        $sort = $request->query['sort'] ?? 'ASC'; // Default sort
        // Ensure sort is safe
        $sort = (strtolower($sort) === 'desc') ? 'DESC' : 'ASC';

        $gerichte = getGerichtNameUndPreisInternExtern($sort); // From gericht.php
        $allergene = getAllAllergensList();
        $gerichtCount = getGerichtCount();
        $nameAngemeldet = $_SESSION['user_name'] ?? 'Gast';
        // 4. RETURN VIEW
        return view('home', [
            'gerichte' => $gerichte,
            'allergene' => $allergene,
            'besucherCount' => $besucherCount,
            'gerichtCount' => $gerichtCount,
            'anmeldungCount' => $anmeldungCount,
            'anmeldungMessage' => $anmeldungMessage,
            'emailExistMessage' => $emailExistMessage,
            'nameInvalidMessage' => $nameInvalidMessage,
            'emailInvalidMessage' => $emailInvalidMessage,
            'name' => $nameAngemeldet,
            'email' => $_POST['email'] ?? '',
            'order' => $sort
        ]);
    }

    // TASK 1: Wunschgericht Logic
    public function wunschgericht(RequestData $request) {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? 'anonym';
            $email = $_POST['email'] ?? '';
            $gerichtName = $_POST['gerichtName'] ?? '';
            $beschreibung = $_POST['beschreibung'] ?? '';

            if (!empty($email) && !empty($gerichtName) && !empty($beschreibung)) {
                // 1. Check if Ersteller exists
                $erstellerID = getIDIfErstellerExist($email);

                // 2. If not, create new Ersteller
                if (!$erstellerID) {
                    $erstellerID = getErstellerIDAndInsertErsteller($name, $email);
                }

                // 3. Save Wunschgericht
                $saved = insertWunchgericht($gerichtName, $beschreibung, $erstellerID);

                if ($saved) {
                    $message = "Wunschgericht wurde erfolgreich gespeichert!";
                } else {
                    $message = "Fehler beim Speichern.";
                }
            } else {
                $message = "Bitte alle Pflichtfelder ausfüllen.";
            }
        }

        return view('wunschgericht', ['message' => $message]);
    }

}