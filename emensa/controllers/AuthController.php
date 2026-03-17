<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/gericht.php');

class AuthController
{
    /**
     * Zeigt die Anmeldeseite an (GET /anmeldung)
     */
    public function index(RequestData $request)
    {
        return view('anmeldung', ['msg' => $_SESSION['login_error'] ?? null]);
    }

    /**
     * Prüft die Anmeldung (POST /anmeldung_verfizieren)
     */
    public function checkLogin(RequestData $request)
    {
        $email = $request->query['email'] ?? '';
        $passwort = $request->query['passwort'] ?? '';

        $link = mysqli_connect("localhost", "root", "root", "emensawerbeseite");
        if (!$link) {
            die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
        }

        mysqli_begin_transaction($link);

        $query = "SELECT id, name, passwort FROM benutzer WHERE email = ?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // HIER definieren wir $user
        $user = mysqli_fetch_assoc($result);

        $salt = "YA20";
        $isValid = false;

        if ($user) {
            $inputHash = sha1($passwort . $salt);
            if ($inputHash === $user['passwort']) {
                $isValid = true;
            }
        }

        if ($isValid) {
            $_SESSION['login_ok'] = true;

            //
            $_SESSION['user_name'] = $user['name'];

            // AUFGABE 5b: Prozedur aufrufen
            $query = "CALL inkrementiere_anmeldungen(?)";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, "i", $user['id']);
            mysqli_stmt_execute($stmt);

            //
            logger()->info('Erfolgreiche Anmeldung', ['user' => $user['name']]);

            mysqli_commit($link);

            header('Location: /');
            exit();
        } else {
            $_SESSION['login_error'] = 'Anmeldung fehlgeschlagen! E-Mail oder Passwort falsch.';

            logger()->warning('Fehlgeschlagene Anmeldung', ['email' => $email]);

            if ($user) {
                $sqlFail = "UPDATE benutzer 
                    SET anzahlfehler = anzahlfehler + 1, 
                        letzterfehler = NOW() 
                    WHERE id = ?";
                $stmtFail = mysqli_prepare($link, $sqlFail);
                mysqli_stmt_bind_param($stmtFail, "i", $user['id']);
                mysqli_stmt_execute($stmtFail);
            }

            mysqli_commit($link);

            header('Location: /anmeldung');
            exit();
        }
    }

    public function logout()
    {
        $name = $_SESSION['user_name'] ?? 'Unbekannt';
        logger()->info('Abmeldung', ['user' => $name]);

        session_destroy();
        header('Location: /');
        exit();
    }
}