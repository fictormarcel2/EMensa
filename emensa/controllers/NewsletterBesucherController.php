<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/newsletter_anmeldung.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/besucher.php');
class NewsletterBesucherController
{
    public function checkSubmissionForm($data) : array {
        $emailExistMessage = "";
        $nameInvalidMessage = "";
        $emailInvalidMessage = "";
        $anmeldungMessage = "";
        $name = "";
        $email = "";
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $emailExistMessage = $this->isEmailExist($data['email']) ? "Email already exists." : "";
            $nameInvalidMessage = $this->isNameValid($data['vor']) ? "" : "Name is invalid.";
            $emailInvalidMessage = $this->isEmailValid($data['email']) ? "" : "Email address is invalid.";
            if(empty($nameInvalidMessage) && empty($emailInvalidMessage) && empty($emailExistMessage)) {
                $anmeldungMessage = $this->insertAnmeldung($data['vor'], $data['email'], $data['sprache']) ? "Success" : "";
            }
            $name = empty($nameInvalidMessage) ? $_POST['vor'] : "";
            $email = empty($emailInvalidMessage) && empty($emailExistMessage) ? $_POST['email'] : "";
        }
        return [
            'emailExistMessage' => $emailExistMessage,
            'nameInvalidMessage' => $nameInvalidMessage,
            'emailInvalidMessage' => $emailInvalidMessage,
            'anmeldungMessage' => $anmeldungMessage,
            'name' => $name,
            'email' => $email,
        ];
    }
    public function getBesucherCount($ip) : int {
        insertBesucher($ip);
        return getBesucherCount();
    }

    public function getAnmeldungCount() : int {
        return getAnmeldungCount();
    }

    public function insertAnmeldung($name, $email, $sprache) : bool{
        return insertNewsletterAnmeldung($name, $email, $sprache);
    }

    public function isEmailValid($email) : bool{
        return $email && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function isNameValid($name) : bool{
        return $name && trim($name) !== '';
    }

    public function isEmailExist($email) : bool{
        return isEmailExistsInDatabase($email);
    }
}