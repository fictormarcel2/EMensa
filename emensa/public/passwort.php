<?php
// Datei /passwort.php

//  Wählen Sie ein sicheres Passwort und einen Salt
$passwort = 'YassirMarcel20';
$salt = 'YA20'; // Mindestens 4 Zeichen Salt

// 2. Hash berechnen (hier SHA-1 + Salt, wie in der alten Anforderung oft üblich)
//

$hash = sha1($passwort . $salt);

echo "Passwort: " . $passwort . "<br>";
echo "Salt: " . $salt . "<br>";
echo "<strong> Hash: " . $hash;
?>