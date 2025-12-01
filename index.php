<?php
/**
 * index.php
 * Pääsivu josta käyttäjä voi hakea osaketietoja EODHD API:sta.
 * Tulokset näytetään yksinkertaisessa kortissa ja käyttäjä voi tallentaa
 * löydetyt tiedot paikalliseen tietokantaan `stocks`-tauluun.
 */
require 'config.php';
require 'database.php';

$savedMessage = null;
$errorMessage = null;
$quote = null;

// Tallennus tietokantaan (POST)
if (isset($_POST['save'])) {
    $stmt = $db->prepare("
        INSERT INTO stocks (symbol, price, change_percent, previous_close, volume)
        VALUES (:symbol, :price, :change_percent, :previous_close, :volume)
    ");
    $stmt->execute([
        ':symbol'         => $_POST['symbol'],
        ':price'          => $_POST['price'],
        ':change_percent' => $_POST['change_percent'],
        ':previous_close' => $_POST['previous_close'],
        ':volume'         => $_POST['volume']
    ]);
    $savedMessage = "Tallennus onnistui ✔";
}

/**
 * haeOsake
 * Hakee yhden osakkeen end-of-day -tiedot EODHD:n EOD-rajapinnasta.
 *
 * @param string $symbolFull Täysi symboli, esim. "AAPL.US"
 * @param string $api_key    EODHD API-avain
 * @return array
 *   - onnistuessa: AlphaVantage-tyylinen array (01. symbol, 05. price, ...)
 *   - virheessä: ['error' => 'selite']
 */
function haeOsake($symbolFull, $api_key) {
    // Haetaan EOD (historical end-of-day) -data JSON:ina
    // Palauttaa taulukon, jossa riveinä päivät (date, open, high, low, close, volume, jne.)
    $url = "https://eodhd.com/api/eod/{$symbolFull}?api_token={$api_key}&fmt=json&period=d&order=a";

    $data = @file_get_contents($url);
    if ($data === false) {
        return ['error' => 'API-yhteys epäonnistui. EODHD ei vastannut.'];
    }

    $json = json_decode($data, true);

    if (!is_array($json) || empty($json)) {
        return ['error' => 'EODHD ei palauttanut dataa annetulle symbolille.'];
    }

    // Taulukko on nousevassa järjestyksessä: vanhin -> uusin
    $count = count($json);
    $last  = $json[$count - 1];              // uusin päivä
    $prev  = $count > 1 ? $json[$count - 2] : null; // edellinen päivä (jos löytyy)

    $lastClose  = $last['close']  ?? null;
    $prevClose  = $prev['close']  ?? null;
    $lastVolume = $last['volume'] ?? null;

    // Lasketaan muutosprosentti (edelliseen päätökseen verrattuna), jos mahdollista
    $changePercentStr = '-';
    if ($lastClose !== null && $prevClose !== null && $prevClose != 0) {
        $change = (($lastClose - $prevClose) / $prevClose) * 100.0;
        // Esim. "-0.34%" tai "1.25%"
        $changePercentStr = sprintf('%.2f%%', $change);
    }

    // Muodostetaan “Global Quote” -tyylinen array, jotta muu koodi voi pysyä samana
    return [
        "01. symbol"          => $symbolFull,
        "05. price"           => $lastClose !== null ? (string)$lastClose : '-',
        "10. change percent"  => $changePercentStr,
        "08. previous close"  => $prevClose !== null ? (string)$prevClose : '-',
        "06. volume"          => $lastVolume !== null ? (string)$lastVolume : '-',
    ];
}

// Haetaan data, jos symbol-parametri on annettu
if (isset($_GET['symbol'])) {
    // Käyttäjä syöttää esim. "AAPL" → muutetaan "AAPL.US"
    $inputSymbol = strtoupper(trim($_GET['symbol']));
    if ($inputSymbol !== '') {
        $symbolFull = $inputSymbol . '.US';
        $result = haeOsake($symbolFull, $api_key);

        if (is_array($result) && isset($result['error'])) {
            $errorMessage = $result['error'];
            $quote = null;
        } else {
            $quote = $result;
        }
    } else {
        $errorMessage = 'Anna osakesymboli, esim. AAPL.';
    }
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StockPeek – Osaketietohaku</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="max-w-3xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h1 class="text-2xl font-bold text-center mb-4">StockPeek</h1>

        <!-- Haku + Dashboard nappi samalla rivillä -->
        <div class="flex flex-col sm:flex-row gap-3 items-center justify-center mb-6">
            <form method="GET" class="flex gap-2 w-full sm:w-auto">
                <input type="text" name="symbol" placeholder="AAPL, TSLA, MSFT..." required
                       class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Hae
                </button>
            </form>

            <form action="dashboard.php" method="get" class="w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    Dashboard
                </button>
            </form>
        </div>

        <!-- Näytä tallennusviesti (jos oli) -->
        <?php if (!empty($savedMessage)): ?>
            <div class="mb-4 text-green-700 bg-green-50 border border-green-100 p-3 rounded">
                <?= htmlspecialchars($savedMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Näytä virheilmoitus API:lta -->
        <?php if (!empty($errorMessage)): ?>
            <div class="mb-4 text-red-700 bg-red-50 border border-red-100 p-3 rounded">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Tulokset -->
        <?php if ($quote): ?>
            <?php
            $change = $quote["10. change percent"] ?? '';
            $isNegative = strpos(trim($change), '-') === 0;
            ?>
            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">
                        <?= htmlspecialchars($quote["01. symbol"] ?? '') ?>
                    </h2>
                    <div class="<?= $isNegative ? 'text-red-600' : 'text-green-600' ?> font-semibold">
                        <?= htmlspecialchars($quote["10. change percent"] ?? '') ?>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                    <div><span class="font-medium">Hinta (päätös):</span> <?= htmlspecialchars($quote["05. price"] ?? '-') ?> USD</div>
                    <div><span class="font-medium">Volume:</span> <?= htmlspecialchars($quote["06. volume"] ?? '-') ?></div>
                    <div><span class="font-medium">Edellinen close:</span> <?= htmlspecialchars($quote["08. previous close"] ?? '-') ?></div>
                    <div><span class="font-medium">Päiväys:</span> <?= date('Y-m-d H:i') ?></div>
                </div>

                <div class="mt-4 flex gap-3">
                    <a href="chart.php?symbol=<?= urlencode($quote["01. symbol"] ?? '') ?>" target="_blank"
                       class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">
                        Graafi
                    </a>

                    <form method="POST" class="inline">
                        <input type="hidden" name="symbol" value="<?= htmlspecialchars($quote["01. symbol"] ?? '') ?>">
                        <input type="hidden" name="price" value="<?= htmlspecialchars($quote["05. price"] ?? '') ?>">
                        <input type="hidden" name="change_percent" value="<?= htmlspecialchars($quote["10. change percent"] ?? '') ?>">
                        <input type="hidden" name="previous_close" value="<?= htmlspecialchars($quote["08. previous close"] ?? '') ?>">
                        <input type="hidden" name="volume" value="<?= htmlspecialchars($quote["06. volume"] ?? '') ?>">
                        <button type="submit" name="save"
                                class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition">
                            Tallenna
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif (isset($_GET['symbol']) && empty($errorMessage)): ?>
            <div class="text-red-600 bg-red-50 border border-red-100 p-3 rounded">
                Tietoja ei löytynyt. Tarkista symboli.
            </div>
        <?php else: ?>
            <div class="text-gray-600">
                Hae osakkeen ticker (esim. AAPL) yläpuolelta tai siirry Dashboardiin.
            </div>
        <?php endif; ?>

        <div class="mt-6 text-right text-sm text-gray-500">
            <a href="saved.php" class="hover:underline">Näytä tallennetut</a>
        </div>
    </div>
</div>

</body>
</html>
