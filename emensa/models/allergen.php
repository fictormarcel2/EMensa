<?php

function getAllAllergens(array $gerichteId) : array {
    $link = connectdb();
    $link->set_charset("utf8mb4");
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }

    $placeholders = implode(',', array_fill(0, count($gerichteId), '?'));
    $sql = "SELECT DISTINCT allergen.code, allergen.name, allergen.typ
            FROM allergen
            INNER JOIN gericht_hat_allergen ON allergen.code = gericht_hat_allergen.code
            WHERE gericht_hat_allergen.gericht_id IN ($placeholders)";

    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }


    $stmt->bind_param(str_repeat('i', count($gerichteId)), ...$gerichteId);

    $stmt->execute();
    $result = $stmt->get_result();
    $allergene = [];
    while ($row = $result->fetch_assoc()) {
        $allergene[] = $row;
    }

    $stmt->close();
    $link->close();

    return $allergene;
}

function getAllAllergensList() {
    $link = connectdb();
    $link->set_charset("utf8mb4");
    $sql = "SELECT code, name, typ FROM allergen ORDER BY code";
    $result = mysqli_query($link, $sql);
    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($link);
    return $data;
}