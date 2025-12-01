<?php
// chart.php
require 'config.php';

$symbolFull = $_GET['symbol'] ?? '';
$symbolFull = trim($symbolFull);

if ($symbolFull === '') {
    die('Symbolia ei annettu.');
}

// Hae viimeaikainen EOD-data EODHD:stä
// order=d → uusin ensin, fmt=json → helppo parsi
$url = "https://eodhd.com/api/eod/{$symbolFull}?api_token={$api_key}&fmt=json&period=d&order=d";

$data = @file_get_contents($url);
if ($data === false) {
    die('API-yhteys epäonnistui. EODHD ei vastannut.');
}

$json = json_decode($data, true);
if (!is_array($json) || empty($json)) {
    die('EODHD ei palauttanut dataa annetulle symbolille.');
}

// Otetaan viimeiset 30 päivää (tai vähemmän jos dataa ei ole niin paljoa)
$points = array_slice($json, 0, 30); // uusin -> vanhin
$points = array_reverse($points);    // käännetään vanhin -> uusin graafia varten

$dates  = [];
$closes = [];

foreach ($points as $row) {
    if (!isset($row['date'], $row['close'])) {
        continue;
    }
    $dates[]  = $row['date'];
    $closes[] = $row['close'];
}

if (empty($dates)) {
    die('Ei riittävästi kurssidataa graafia varten.');
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Kurssigraafi – <?= htmlspecialchars($symbolFull) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Tailwind (optional) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 min-h-screen">
<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">
            Kurssigraafi – <?= htmlspecialchars($symbolFull) ?>
        </h1>
        <p class="text-sm text-gray-500 mb-4">
            Näytetään enintään 30 viimeisintä kaupankäyntipäivää (päätöskurssi).
        </p>
        <canvas id="priceChart" height="120"></canvas>
        <div class="mt-4">
            <a href="index.php" class="text-blue-600 hover:underline">&larr; Takaisin hakuun</a>
        </div>
    </div>
</div>

<script>
    const labels = <?= json_encode($dates) ?>;
    const data   = <?= json_encode($closes, JSON_NUMERIC_CHECK) ?>;

    const ctx = document.getElementById('priceChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Päätöskurssi (USD)',
                data: data,
                borderWidth: 2,
                fill: false,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        maxTicksLimit: 8
                    }
                },
                y: {
                    beginAtZero: false
                }
            }
        }
    });
</script>
</body>
</html>
