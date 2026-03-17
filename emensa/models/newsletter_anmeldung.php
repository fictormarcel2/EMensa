<?php

function isEmailExistsInDatabase($email) : bool {
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }
    $sql = "SELECT COUNT(*) AS count FROM newsletter_anmeldung WHERE email = ?";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($emailCount);
    $stmt->fetch();
    $stmt->close();
    $link->close();
    return $emailCount > 0;
}

function insertNewsletterAnmeldung($name, $email, $sprache) : bool{
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }
    $sql = "INSERT INTO newsletter_anmeldung(name, email, sprache) values(?, ?, ?)";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }
    $stmt->bind_param("sss", $name, $email, $sprache);
    $stmt->execute();
    $effectedRows = $stmt->affected_rows;
    $stmt->close();
    $link->close();
    return $effectedRows > 0;
}

function getAnmeldungCount() : int {
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }
    $sql = "SELECT COUNT(*) AS count FROM newsletter_anmeldung";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}
