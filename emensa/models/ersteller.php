<?php

function getIDIfErstellerExist($email): null | int
{
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }
    $sql = "SELECT erstellerID FROM ersteller WHERE email = ?";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    $link->close();
    return $id;
}

function getErstellerIDAndInsertErsteller($name, $email): int
{
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }
    $sql = "INSERT INTO ersteller (name, email) VALUES (?, ?)";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    $link->close();
    return $id;
}