<?php
/**
 * Diese Datei enthält alle SQL Statements für die Tabelle "kategorie"
 */
function db_kategorie_select_all_sorted() {
    $link = connectdb();
    $sql = "SELECT * FROM kategorie ORDER BY name ASC";
    $result = mysqli_query($link, $sql);
    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($link);
    return $data;
}