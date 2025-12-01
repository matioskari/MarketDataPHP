<?php
/**
 * index.php
 * Pääsivu josta käyttäjä voi hakea osaketietoja AlphaVantage API:sta.
 * Tulokset näytetään yksinkertaisessa kortissa ja käyttäjä voi tallentaa
 * löydetyt tiedot paikalliseen tietokantaan `stocks`-tauluun.
 */
require 'config.php';
require 'database.php';

// Tallennus tietokantaan (POST)
if (isset($_POST['save'])) {
    // Valmistele INSERT ja täytä arvot POST-dataa käyttäen
    $stmt = $db->prepare("
        INSERT INTO stocks (symbol, price, change_percent, previous_close, volume)
        VALUES (:symbol, :price, :change_percent, :previous_close, :volume)
    ");
    $stmt->execute([
        ':symbol' => $_POST['symbol'],
        ':price' => $_POST['price'],
        ':change_percent' => $_POST['change_percent'],
        ':previous_close' => $_POST['previous_close'],
        ':volume' => $_POST['volume']
    ]);
    // Viesti käyttäjälle onnistuneesta tallennuksesta
    $savedMessage = "Tallennus onnistui ✔";
}

/**
 * haeOsake
 * Hakee yhden osakkeen nykyiset tiedot AlphaVantage:n GLOBAL_QUOTE -päätepisteestä.
 *
 * @param string $symbol Osakesymboli (esim. 'AAPL')
 * @param string $api_key AlphaVantage API-avain
 * @return array|false Palauttaa 'Global Quote' -assosiatiivisen taulukon tai false
 */
function haeOsake($symbol, $api_key) {
    $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$api_key";

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: PHP\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);

    if ($data === false) {
        die("<b>API ERROR:</b> file_get_contents returned FALSE.<br>URL: $url");
    }

    $json = json_decode($data, true);

    // DEBUG: Tulosta koko raakavastaus, jotta näemme mikä puuttuu
    echo "<pre>";
    echo "DEBUG - API RAW RESPONSE:\n\n";
    print_r($json);
    echo "</pre>";

    return $json["Global Quote"] ?? false;
}


$quote = null;
if (isset($_GET['symbol'])) {
    $symbol = strtoupper(trim($_GET['symbol']));
    // Haetaan data API:sta käyttäen yläpuolella määriteltyä funktiota
    $quote = haeOsake($symbol, $api_key);
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

        <!-- Tulokset -->
        <?php if ($quote): ?>
            <?php
            $change = $quote["10. change percent"] ?? '';
            $isNegative = strpos(trim($change), '-') === 0;
            ?>
            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold"><?= htmlspecialchars($quote["01. symbol"] ?? '') ?></h2>
                    <div class="<?= $isNegative ? 'text-red-600' : 'text-green-600' ?> font-semibold">
                        <?= htmlspecialchars($quote["10. change percent"] ?? '') ?>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                    <div><span class="font-medium">Hinta:</span> <?= htmlspecialchars($quote["05. price"] ?? '-') ?> USD</div>
                    <div><span class="font-medium">Volume:</span> <?= htmlspecialchars($quote["06. volume"] ?? '-') ?></div>
                    <div><span class="font-medium">Edellinen close:</span> <?= htmlspecialchars($quote["08. previous close"] ?? '-') ?></div>
                    <div><span class="font-medium">Päiväys:</span> <?= date('Y-m-d H:i') ?></div>
                </div>

                <div class="mt-4 flex gap-3">
                    <a href="chart.php?symbol=<?= urlencode($quote["01. symbol"] ?? '') ?>" target="_blank"
                       class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">Graafi</a>

                    <form method="POST" class="inline">
                        <input type="hidden" name="symbol" value="<?= htmlspecialchars($quote["01. symbol"] ?? '') ?>">
                        <input type="hidden" name="price" value="<?= htmlspecialchars($quote["05. price"] ?? '') ?>">
                        <input type="hidden" name="change_percent" value="<?= htmlspecialchars($quote["10. change percent"] ?? '') ?>">
                        <input type="hidden" name="previous_close" value="<?= htmlspecialchars($quote["08. previous close"] ?? '') ?>">
                        <input type="hidden" name="volume" value="<?= htmlspecialchars($quote["06. volume"] ?? '') ?>">
                        <button type="submit" name="save"
                                class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition">Tallenna</button>
                    </form>
                </div>
            </div>
        <?php elseif (isset($_GET['symbol'])): ?>
            <div class="text-red-600 bg-red-50 border border-red-100 p-3 rounded">Tietoja ei löytynyt. Tarkista symboli.</div>
        <?php else: ?>
            <div class="text-gray-600">Hae osakkeen ticker yläpuolelta tai siirry Dashboardiin.</div>
        <?php endif; ?>

        <div class="mt-6 text-right text-sm text-gray-500">
            <a href="saved.php" class="hover:underline">Näytä tallennetut</a>
        </div>
    </div>
</div>

</body>
</html>
