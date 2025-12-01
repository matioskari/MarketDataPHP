<?php
/**
 * database.php
 * Yhdistää MySQL-tietokantaan PDO:lla ja asettaa virhetilan poikkeuksiin.
 * Muokkaa yhteysasetuksia tarvittaessa paikalliseen XAMPP/MariaDB-asennukseen.
 */
$host = "dpg-d4mqgk0bdp1s73er2d5g-a";
$port = "5432";
$dbname = "marketdata-db";
$user = "marketdata_bj2m_user";
$pass = "7SZAbvxo7JhPABRHYcgX6Des8zrZY4Ar";

try {
    // Luo PDO-yhteys ja aseta ERRMODE poikkeuksiin, jotta virheet ovat
    // helpommin käsiteltävissä kehitysvaiheessa.
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $db = new PDO($dsn, $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Lakkaa suorituksesta jos yhteyttä ei saada muodostettua
    die("Tietokantavirhe: " . $e->getMessage());
}