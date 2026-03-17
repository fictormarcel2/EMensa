<?php
/**
 * Diese Datei enthält alle SQL Statements für die Tabelle "gerichte"
 */
function db_gericht_select_all() {
    try {
        $link = connectdb();
        $link->set_charset("utf8mb4"); // FIX: Encoding

        $sql = 'SELECT id, name, beschreibung FROM gericht ORDER BY name';
        $result = mysqli_query($link, $sql);

        $data = mysqli_fetch_all($result, MYSQLI_BOTH);

        mysqli_close($link);
    }
    catch (Exception $ex) {
        $data = array(
            'id'=>'-1',
            'error'=>true,
            'name' => 'Datenbankfehler '.$ex->getCode(),
            'beschreibung' => $ex->getMessage());
    }
    finally {
        return $data;
    }
}

function getGerichtNameUndPreisIntern() {
    $link = connectdb();
    $link->set_charset("utf8mb4"); // FIX: Encoding

    $sql = 'SELECT name, preisintern FROM gericht WHERE preisintern > 2 ORDER BY name DESC';

    $result = mysqli_query($link, $sql);
    $data = mysqli_fetch_all($result, MYSQLI_BOTH);
    mysqli_close($link);
    return $data;
}

function getGerichtNameUndPreisInternExtern($order) : array {
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }

    $link->set_charset("utf8mb4");

    $sortOrder = (strtolower($order) === 'desc') ? 'DESC' : 'ASC';

    $sql = "SELECT 
                gericht.id,
                gericht.name,
                gericht.beschreibung,
                gericht.preisintern,
                gericht.preisextern,
                gericht.vegetarisch,
                gericht.vegan,
                gericht.bildname,
                GROUP_CONCAT(allergen.code) as allergene
            FROM gericht
            LEFT JOIN gericht_hat_allergen ON gericht.id = gericht_hat_allergen.gericht_id
            LEFT JOIN allergen ON gericht_hat_allergen.code = allergen.code
            GROUP BY gericht.id
            ORDER BY gericht.name $sortOrder
            LIMIT 5";

    $stmt = $link->prepare($sql);
    if (!$stmt) {
        die("Could not prepare statement: " . mysqli_error($link));
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $gerichte = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['allergene']) {
            $row['allergene'] = explode(',', $row['allergene']);
        }
        $gerichte[] = $row;
    }

    $stmt->close();
    $link->close();

    return $gerichte;
}

function getGerichtCount() : int {
    $link = connectdb();
    if (!$link) {
        die("Verbindung zur Datenbank konnte nicht hergestellt werden");
    }
    $sql = "SELECT COUNT(id) AS Anzahl_Gerichte FROM gericht";
    $result = $link->query($sql);
    $row = $result->fetch_assoc();
    $count = $row['Anzahl_Gerichte'];
    $link->close();
    return $count;
}