<?php
/**
 * saved.php
 * Näyttää kaikki tallennetut osakkeet aikajärjestyksessä (uusin ensin).
 * Tarjoaa myös mahdollisuuden poistaa yksittäisiä rivejä.
 */
require 'database.php';

// Poisto: id annetaan GET-parametrina
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM stocks WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: saved.php");
    exit;
}

$data = $db->query("SELECT * FROM stocks ORDER BY saved_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tallennetut</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<!--  ============  KESKITTÄÄ KOKO SIVUN  ============ -->
<body class="bg-slate-50 min-h-screen flex items-center justify-center">

<div class="w-full max-w-4xl bg-white shadow-xl rounded-2xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold">Tallennetut osakkeet</h1>
        <a href="index.php" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">⬅ Takaisin</a>
    </div>

    <?php if ($data): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Symboli</th>
                        <th class="px-4 py-2 text-left">Hinta</th>
                        <th class="px-4 py-2 text-left">Muutos %</th>
                        <th class="px-4 py-2 text-left">Volume</th>
                        <th class="px-4 py-2 text-left">Aika</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="px-4 py-2"><?= $row['symbol'] ?></td>
                            <td class="px-4 py-2"><?= $row['price'] ?> USD</td>
                            <td class="px-4 py-2 <?= strpos($row['change_percent'],'-')===0?'text-red-600':'text-green-600' ?>">
                                <?= $row['change_percent'] ?>
                            </td>
                            <td class="px-4 py-2"><?= $row['volume'] ?></td>
                            <td class="px-4 py-2"><?= $row['saved_at'] ?></td>
                            <td class="px-4 py-2 text-right">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="text-indigo-600 hover:underline">Muokkaa</a> |
                                <a href="saved.php?delete=<?= $row['id'] ?>" onclick="return confirm('Poistetaanko?')" class="text-red-600 hover:underline">Poista</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-600">Ei tallennettuja osakkeita.</p>
    <?php endif; ?>
</div>
</body>
</html>
