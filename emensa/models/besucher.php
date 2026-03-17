<?php

function insertBesucher($ip) : bool {
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }

    // We update the existing tally instead of inserting a new row
    $sql = "UPDATE besucher SET anzahl = anzahl + 1";
    $stmt = $link->prepare($sql);

    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }

    // We no longer need to bind the IP parameter
    $stmt->execute();
    $effectedRows = $stmt->affected_rows;
    $stmt->close();
    $link->close();

    return $effectedRows > 0;
}

function getBesucherCount() : int {
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }

    // We select the actual number instead of counting the rows
    $sql = "SELECT anzahl FROM besucher";
    $stmt = $link->prepare($sql);

    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }

    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $link->close();

    return $count;
}