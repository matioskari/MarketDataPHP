<?php
/**
 * edit.php
 * Mahdollistaa talletetun osaketiedon muokkaamisen (price, change_percent,
 * previous_close, volume). ID annetaan GET-parametrina ja muutokset lähetetään
 * POSTilla painamalla lomakkeen "Tallenna muutokset" -nappia.
 */
require 'database.php';

// Hae tietue annetulla id:llä
$id = (int)$_GET['id'];
$data = $db->prepare("SELECT * FROM stocks WHERE id = ?");
$data->execute([$id]);
$row = $data->fetch(PDO::FETCH_ASSOC);

if (!$row) die("Virhe: osaketta ei löydy.");

// Päivitä tietue käyttäjän lähettämillä arvoilla
if (isset($_POST['update'])) {
    $stmt = $db->prepare("UPDATE stocks SET price=?, change_percent=?, previous_close=?, volume=? WHERE id=?");
    $stmt->execute([$_POST['price'], $_POST['change_percent'], $_POST['previous_close'], $_POST['volume'], $id]);
    header("Location: saved.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Muokkaa</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<!--  KESKITTÄÄ  -->
<body class="bg-slate-50 min-h-screen flex items-center justify-center">

<div class="w-full max-w-md bg-white p-6 rounded-2xl shadow-xl">
    <h2 class="text-xl font-bold mb-4">Muokkaa tietoja — <?= $row['symbol'] ?></h2>

    <form method="POST" class="space-y-4">
        <input type="text" name="price" value="<?= $row['price'] ?>" class="w-full border p-2 rounded">
        <input type="text" name="change_percent" value="<?= $row['change_percent'] ?>" class="w-full border p-2 rounded">
        <input type="text" name="previous_close" value="<?= $row['previous_close'] ?>" class="w-full border p-2 rounded">
        <input type="text" name="volume" value="<?= $row['volume'] ?>" class="w-full border p-2 rounded">

        <button name="update" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Tallenna muutokset</button>
    </form>

    <a href="saved.php" class="block text-center mt-3 text-gray-600 hover:underline"> Takaisin</a>
</div>

</body>
</html>
