<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/gericht.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/allergen.php');

class GerichtController
{
    public function checkIfAscOrDesc ($order) : int {
        $order = in_array($order, ['0', '1'], true) ? $order : '0';
        return $order === '0' ?  '1' :  '0';
    }
    public function getGericht($order) : array {
        $asc = $order == '0' ? 'DESC' : 'ASC';
        return getGerichtNameUndPreisInternExtern($asc);
    }

    function getGerichtIds(array $gerichte): array {
        $ids = [];
        foreach ($gerichte as $gericht) {
            $ids[] = $gericht['id'];
        }
        return $ids;
    }

    public function getAllergens(array $gerichteId) : array {
        return getAllAllergens($gerichteId);
    }

    function getGerichtCount() : int {
        return getGerichtCount();
    }
}