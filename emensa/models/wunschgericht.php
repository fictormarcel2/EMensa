<?php

function insertWunchgericht($name, $beschreibung, $id): bool
{
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }
    $sql = "INSERT INTO wunschgericht (name, beschreibung, erstellerID) VALUES (?, ?, ?)";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }
    $stmt->bind_param("ssi", $name, $beschreibung, $id);
    $stmt->execute();
    $effectedRows = $stmt->affected_rows;
    $stmt->close();
    $link->close();
    return $effectedRows > 0;
}
