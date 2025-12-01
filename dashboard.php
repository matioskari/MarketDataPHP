<?php
require 'database.php';

// Poisto
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM stocks WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: dashboard.php");
    exit;
}

// Hae viimeisin tallennus per symbol
$data = $db->query("
    SELECT s1.*
    FROM stocks s1
    INNER JOIN (
        SELECT symbol, MAX(saved_at) AS latest
        FROM stocks
        GROUP BY symbol
    ) s2 ON s1.symbol = s2.symbol AND s1.saved_at = s2.latest
    ORDER BY s1.symbol ASC
")->fetchAll(PDO::FETCH_ASSOC);

$symbols = [];
$prices = [];
foreach ($data as $row) {
    $symbols[] = $row['symbol'];
    $prices[] = (float)$row['price'];
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 min-h-screen">
<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold">📊 Dashboard</h1>
            <div class="flex gap-2">
                <a href="index.php" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Haku</a>
                <a href="saved.php" class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">Tallennetut</a>
            </div>
        </div>

        <?php if (count($data) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Symboli</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Hinta</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Muutos %</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Volume</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Aika</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Toiminnot</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['symbol']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['price']) ?> USD</td>
                                <td class="px-4 py-2 <?= strpos($row['change_percent'],'-')===0 ? 'text-red-600' : 'text-green-600' ?>">
                                    <?= htmlspecialchars($row['change_percent']) ?>
                                </td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['volume']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['saved_at']) ?></td>
                                <td class="px-4 py-2">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="text-indigo-600 hover:underline">Muokkaa</a>
                                    <a href="dashboard.php?delete=<?= $row['id'] ?>" onclick="return confirm('Vahvista poisto')" class="ml-3 text-red-600 hover:underline">Poista</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php
                    /**
                     * dashboard.php
                     * Näyttää dashboardin, jossa listataan viimeisimmät tallennetut hinnat per symboli
                     * sekä piirretään pylväsdiagrammi viimeisimmistä hinnoista.
                     */
                    require 'database.php';

                    // Poisto toiminto: id saadaan GET-parametrista ja rivin poistamisen jälkeen
                    // uudelleenohjataan käyttäjä takaisin dashboardiin.
                    if (isset($_GET['delete'])) {
                        $id = (int)$_GET['delete'];
                        $stmt = $db->prepare("DELETE FROM stocks WHERE id = ?");
                        $stmt->execute([$id]);
                        header("Location: dashboard.php");
                        exit;
                    }

                    // Hae viimeisin tallennus per symbol. Sisäkkäinen JOIN varmistaa, että
                    // saamme vain kutakin symbolia vastaavan viimeisimmän rivin.
                    $data = $db->query("\n    SELECT s1.*\n    FROM stocks s1\n    INNER JOIN (\n        SELECT symbol, MAX(saved_at) AS latest\n        FROM stocks\n        GROUP BY symbol\n    ) s2 ON s1.symbol = s2.symbol AND s1.saved_at = s2.latest\n    ORDER BY s1.symbol ASC\n")->fetchAll(PDO::FETCH_ASSOC);

                    $symbols = [];
                    $prices = [];
                    foreach ($data as $row) {
                        $symbols[] = $row['symbol'];
                        // Muunna hinta liukuluvuksi graafia varten
                        $prices[] = (float)$row['price'];
                    }
                },
                options: { responsive: true }
            });
            </script>

        <?php else: ?>
            <p>Ei tallennettuja osakkeita vielä.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
