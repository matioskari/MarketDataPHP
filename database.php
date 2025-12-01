<?php
/**
 * database.php
 * Yhdistää MySQL-tietokantaan PDO:lla ja asettaa virhetilan poikkeuksiin.
 * Muokkaa yhteysasetuksia tarvittaessa paikalliseen XAMPP/MariaDB-asennukseen.
 */
$host = "HOST_FROM_RENDER";
$dbname = "DB_NAME";
$user = "DB_USER";
$pass = "DB_PASSWORD";

try {
    // Luo PDO-yhteys ja aseta ERRMODE poikkeuksiin, jotta virheet ovat
    // helpommin käsiteltävissä kehitysvaiheessa.
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Lakkaa suorituksesta jos yhteyttä ei saada muodostettua
    die("Tietokantavirhe: " . $e->getMessage());
}

// Huom: sovelluksessa on myös `config.php` joka sisältää API-avaimen.
// Tämä rivi on paikallinen paikkamerkki. Poista tai yhdistä konfiguraatiot
// tarpeen mukaan, jotta avain ei jää eri tiedostoihin ristiriitaiseksi.
$api_key = "OMA_API_KEY_TAHAN";
