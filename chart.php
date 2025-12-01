<?php
/**
 * chart.php
 * Näyttää 30 päivän hintakehityksen valitulle osakesymbolille käyttäen
 * AlphaVantage API:n TIME_SERIES_DAILY -dataa ja Chart.js:ää visualisointiin.
 *
 * Käyttäjä antaa `symbol`-parametrin GET-kyselyssä (esim. ?symbol=AAPL).
 * Tiedot haetaan JSON-muodossa, muunnetaan taulukoksi ja leikataan 30
 * viimeisimpään päivään. Taulukot käännetään niin, että aikajärjestys on
 * vanhimmasta uusimpaan, mikä helpottaa graafin lukemista.
 */
require 'config.php';

// Symbolin lukeminen GET-parametrista, oletuksena AAPL
$symbol = $_GET['symbol'] ?? 'AAPL';
$symbol = strtoupper(trim($symbol));

// Kutsutaan AlphaVantage API:ta TIME_SERIES_DAILY-funktiolla
$url = "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$symbol&apikey=$api_key";
$opts = ["http" => ["method" => "GET", "header" => "User-Agent: PHP\r\n"]];
$context = stream_context_create($opts);
// Suoritetaan HTTP-GET ja puretaan JSON. @-operaattori estää varoituksia
// jos esimerkiksi verkkoyhteyttä ei ole; tällöin $data jää nulliksi.
$dataRaw = @file_get_contents($url, false, $context);
$data = $dataRaw ? json_decode($dataRaw, true) : null;

$dates = [];
$prices = [];

// Jos data löytyi, otetaan 30 uusinta päivää ja kerätään päivämäärät + sulkuhinnat
if (isset($data["Time Series (Daily)"])) {
    $slice = array_slice($data["Time Series (Daily)"], 0, 30);
    foreach ($slice as $date => $row) {
        $dates[] = $date;
        // Sulkuhinta merkkijonona -> muunnetaan liukuluvuksi
        $prices[] = (float)$row["4. close"];
    }
    // Chart.js odottaa yleensä aikajärjestystä vasemmalta oikealle:
    // järjestetään taulukot vanhimmasta uusimpaan
    $dates = array_reverse($dates);
    $prices = array_reverse($prices);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Graafi – <?= htmlspecialchars($symbol) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="max-w-3xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold"><?= htmlspecialchars($symbol) ?> – 30 päivän kehitys</h1>
            <a href="index.php" class="text-sm text-blue-600 hover:underline">⬅ Takaisin</a>
        </div>

        <?php if (!empty($prices)): ?>
            <canvas id="stockChart" width="400" height="200"></canvas>

            <script>
            const ctx = document.getElementById('stockChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($dates) ?>,
                    datasets: [{
                        label: 'Lopetushinta USD',
                        data: <?= json_encode($prices) ?>,
                        tension: 0.25,
                        borderWidth: 2,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { display: true },
                        y: { beginAtZero: false }
                    }
                }
            });
            </script>
        <?php else: ?>
            <div class="text-red-600">Ei historian tietoja saatavilla.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
