<?php
session_start();

// Absoluter Pfad zu ffmpeg (Windows)
$ffmpeg = "C:\\ffmpeg\\bin\\ffmpeg.exe";

// Funktion: Wallet abrufen (Python-Script)
function getWalletAddress() {
    $python = "C:\\Users\\DEINNAME\\AppData\\Local\\Programs\\Python\\Python39\\python.exe";
    $wallet = trim(shell_exec("$python wallets.py"));
    return $wallet ?: 'Wallet not found';
}



// Funktion: Solana Preis abrufen
function getSolanaPrice() {
    $output = trim(shell_exec('python fetch_price.py')); // genau wie vorher

    // Nur die erste Zahl extrahieren
    if (preg_match('/\d+(\.\d+)?/', $output, $matches)) {
        return (float)$matches[0]; // nur die Zahl
    }

    // Fehler nur anzeigen, kein Hardcode
    trigger_error("Fehler beim Abrufen des Solana-Preises: ungültiger Output '$output'", E_USER_WARNING);
    return null;
}

// Funktion: Solana Balance
function getSolanaBalance($wallet) {
    if (!$wallet) return 0;
    $python = "C:\\Users\\DEINNAME\\AppData\\Local\\Programs\\Python\\Python39\\python.exe";
    $balance = trim(shell_exec("$python fetch_balance.py " . escapeshellarg($wallet)));
    return is_numeric($balance) ? (float)$balance : 0;
}

// Funktion: Thumbnail erstellen
function getThumbnail($video) {
    global $ffmpeg;
    $videoPath = realpath($video);
    $tmp = tempnam(sys_get_temp_dir(), 'thumb') . '.png';
    shell_exec("$ffmpeg -y -i " . escapeshellarg($videoPath) . " -ss 00:00:01 -vframes 1 " . escapeshellarg($tmp) . " 2>&1");
    if (file_exists($tmp)) {
        $data = base64_encode(file_get_contents($tmp));
        unlink($tmp);
        return "data:image/png;base64," . $data;
    }
    return "";
}

// Funktion: Videolänge in Minuten
function getDurationMinutes($video) {
    global $ffmpeg;
    $videoPath = realpath($video);
    $raw = shell_exec("$ffmpeg -i " . escapeshellarg($videoPath) . " 2>&1");
    preg_match('/Duration: (\d+):(\d+):([\d\.]+)/', $raw, $m);
    if ($m) {
        $hours = (int)$m[1];
        $minutes = (int)$m[2];
        $seconds = (float)$m[3];
        return $hours*60 + $minutes + $seconds/60;
    }
    return 0;
}

// Initialisierung Session
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'wallet' => getWalletAddress(),
        'balance' => 0,
        'purchased' => [],
        'start' => time(),
    ];
}

// Wallet / Balance
$wallet = $_SESSION['user']['wallet'];
$balance = getSolanaBalance($wallet);
$price = getSolanaPrice();
$_SESSION['user']['balance'] = $balance;

// Timer
$timeRemaining = max(0, ($_SESSION['user']['start'] + 48*3600) - time());

// Videos laden
$videoDir = __DIR__ . '/videos/';
$videos = glob($videoDir . '*.mp4');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Site Video Store</title>
<style>
body { font-family: Arial; background:#121212; color:#e0e0e0; margin:0; }
.header { display:flex; justify-content:space-between; padding:10px; background:#1e1e1e; }
.header .logo { font-size:24px; font-weight:bold; }
.header a { color:#bb86fc; text-decoration:none; }
.header a:hover { text-decoration:underline; }
.container { padding:20px; }
.video-grid { display:flex; flex-wrap:wrap; gap:20px; }
.video-item { background:#2c2c2c; padding:10px; border-radius:8px; width:200px; text-align:center; }
.video-thumb { width:100%; border-radius:8px; }
button { background:#6200ea; border:none; color:white; padding:10px; border-radius:8px; cursor:pointer; }
button:hover { background:#3700b3; }
.footer { position:fixed; bottom:0; width:100%; padding:10px; background:#1e1e1e; display:flex; justify-content:space-between; box-sizing:border-box; }
</style>
<script>
function startCountdown(duration, display) {
    var timer = duration, hours, minutes, seconds;
    setInterval(function () {
        hours = parseInt(timer / 3600, 10);
        minutes = parseInt((timer % 3600) / 60, 10);
        seconds = parseInt(timer % 60, 10);
        hours = hours < 10 ? "0"+hours : hours;
        minutes = minutes < 10 ? "0"+minutes : minutes;
        seconds = seconds < 10 ? "0"+seconds : seconds;
        display.textContent = hours + ":" + minutes + ":" + seconds;
        if (--timer < 0) timer=0;
    },1000);
}
window.onload = function() {
    var display = document.getElementById('time');
    startCountdown(<?= $timeRemaining ?>, display);
};
</script>
</head>
<body>
<div class="header">
<div class="logo">Site</div>
<div><a href="about.php">About</a></div>
</div>
<div class="container">
<div class="video-grid">
<?php foreach($videos as $idx => $video): 
    $thumb = getThumbnail($video);
    $minutes = getDurationMinutes($video);
    $usd = $minutes * 0.30;
    $sol = ($price>0)? $usd/$price : 0;
    $usdF = number_format($usd,2);
    $solF = number_format($sol,8);
?>
<div class="video-item">
    <img src="<?= $thumb ?>" class="video-thumb" alt="Video <?= $idx+1 ?>">
    <?php if(!in_array($idx+1,$_SESSION['user']['purchased']??[])): ?>
    <form action="buy_video.php" method="POST">
        <input type="hidden" name="video_id" value="<?= $idx+1 ?>">
        <button type="submit">Buy <?= $solF ?> SOL / $<?= $usdF ?></button>
    </form>
    <?php else: ?>
        <a href="watch_video.php?video_id=<?= $idx+1 ?>"><button>Watch</button></a>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>
<div class="footer">
<div>Balance: <?= number_format($balance,8) ?> SOL</div>
<div>Wallet: <?= htmlspecialchars($wallet) ?></div>
<div id="time"></div>
</div>
</body>
</html>