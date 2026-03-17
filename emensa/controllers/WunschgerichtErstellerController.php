<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/wunschgericht.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/ersteller.php');
class WunschgerichtErstellerController
{
    public function createNewWunschgericht ($name, $gerichtName, $email, $beschreibung) : bool {
        $name = empty(trim($name)) ? "anonym" : $name;
        $id = getIDIfErstellerExist($email);
        if ($id === null) {
            $id = getErstellerIDAndInsertErsteller($name, $email);
        }
        return insertWunchgericht($gerichtName, $beschreibung, $id);
    }
}