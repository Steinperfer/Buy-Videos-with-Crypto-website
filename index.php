<?php
session_start();

// Function to get the Solana wallet address
function getWalletAddress() {
    $walletAddress = trim(shell_exec('python wallets.py'));
    return $walletAddress;
}

// Function to fetch Solana price
function getSolanaPrice() {
    $priceCommand = 'python fetch_price.py';
    return trim(shell_exec($priceCommand));
}

// Function to fetch Solana wallet balance
function getSolanaBalance($walletAddress) {
    if (!empty($walletAddress)) {
        $balanceCommand = 'python fetch_balance.py ' . escapeshellarg($walletAddress);
        return trim(shell_exec($balanceCommand));
    } else {
        return "Wallet address not found";
    }
}

// Function to get base64 encoded thumbnail
function getVideoThumbnailBase64($videoFile) {
    // Generate the thumbnail and encode to base64
    $thumbnailData = shell_exec("ffmpeg -i " . escapeshellarg($videoFile) . " -vf 'thumbnail,scale=320:240' -vframes 1 -f image2pipe -vcodec png - | base64");
    return $thumbnailData;
}

// Initialize user if not already
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'solana_address' => getWalletAddress(),
        'session_id' => session_id(),
        'solana_balance' => 0.0,
        'purchased_videos' => [],
        'timer_start' => time(),
    ];
} else {
    // Check if wallet address needs to be updated
    if (!isset($_SESSION['user']['solana_address']) || empty($_SESSION['user']['solana_address'])) {
        $_SESSION['user']['solana_address'] = getWalletAddress();
    }
}

// Get wallet address
$walletAddress = $_SESSION['user']['solana_address'] ?? '';

// Fetch Solana price and balance
$price = getSolanaPrice();
$balance = getSolanaBalance($walletAddress);

// Ensure the price is a valid numeric value
$price = is_numeric($price) ? (float)$price : 1; // Default to 1 if the price is invalid or not found

// Timer setup
$timerExpiresAt = $_SESSION['user']['timer_start'] + (48 * 3600);
$timeRemaining = max(0, $timerExpiresAt - time());

$videoDir = 'videos/';
$videoFiles = glob($videoDir . '*.mp4');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>video buy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background-color: #1e1e1e;
        }

        .header .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .header .about a {
            color: #bb86fc;
            text-decoration: none;
        }

        .header .about a:hover {
            text-decoration: underline;
        }

        .container {
            padding: 20px;
            flex: 1;
        }

        .video-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .video-item {
            background-color: #2c2c2c;
            padding: 10px;
            border-radius: 8px;
            width: 200px;
            text-align: center;
        }

        .video-thumb {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        button {
            background-color: #6200ea;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 8px;
        }

        button:hover {
            background-color: #3700b3;
        }

        .button {
            display: block;
            background-color: #6200ea;
            border: none;
            color: white;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            margin: 20px auto;
            width: 200px;
            border-radius: 8px;
        }

        .button:hover {
            background-color: #3700b3;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            padding: 10px;
            background-color: #1e1e1e;
            color: #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
        }

        .footer .balance {
            font-size: 18px;
            margin-right: 20px;
        }

        .footer .solana-address {
            font-size: 18px;
            text-align: center;
            flex: 1;
            margin: 0 20px;
        }

        .footer .timer {
            font-size: 18px;
            text-align: right;
            white-space: nowrap;
        }
    </style>
    <script>
        function startCountdown(duration, display) {
            var timer = duration, hours, minutes, seconds;
            setInterval(function () {
                hours = parseInt(timer / 3600, 10);
                minutes = parseInt((timer % 3600) / 60, 10);
                seconds = parseInt(timer % 60, 10);

                hours = hours < 10 ? "0" + hours : hours;
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = hours + ":" + minutes + ":" + seconds;

                if (--timer < 0) {
                    timer = 0;
                }
            }, 1000);
        }

        window.onload = function () {
            var timeLeft = <?= 48 * 3600 - (time() - $_SESSION['user']['timer_start']) ?>;
            var display = document.querySelector('#time');
            startCountdown(timeLeft, display);
        };
    </script>
</head>
<body>
    <div class="header">
        <div class="logo">SP</div>
        <div class="about">
            <a href="about.php" class="about-button">About</a>
        </div>
    </div>
    <div class="container">
        <div class="video-grid">
            <?php
            // Iterate through each video file
            foreach ($videoFiles as $index => $videoFile): 
                $videoName = basename($videoFile, '.mp4');

                // Get base64 encoded thumbnail
                $thumbnailBase64 = getVideoThumbnailBase64($videoFile);
                $thumbnailSrc = "data:image/png;base64," . htmlspecialchars($thumbnailBase64);

                // Get video duration in seconds
                $duration = shell_exec("ffmpeg -i " . escapeshellarg($videoFile) . " 2>&1 | grep 'Duration' | awk '{print $2}' | tr -d ,");
                list($hours, $minutes, $seconds) = explode(':', $duration);
                //$totalMinutes = $hours * 60 + $minutes + $seconds / 60;
                $totalMinutes = (int)$hours * 60 + (int)$minutes + (int)$seconds / 60;

                // Calculate price per video
                $priceInUsd = $totalMinutes * 0.30; // 30 cents per minute
                $priceInSol = $price > 0 ? $priceInUsd / $price : 0; // Ensure $price is greater than 0

                // Format prices
                $priceInUsdFormatted = number_format($priceInUsd, 2);
                $priceInSolFormatted = number_format($priceInSol, 8);
            ?>
                <div class="video-item">
                    <img src="<?= $thumbnailSrc ?>" alt="<?= htmlspecialchars($videoName) ?>" class="video-thumb">
                    <?php if (!in_array($index + 1, $_SESSION['user']['purchased_videos'])): ?>
                        <form action="buy_video.php" method="POST">
                            <input type="hidden" name="video_id" value="<?= $index + 1 ?>">
                            <button type="submit">Buy for <?= $priceInSolFormatted ?> SOL / $<?= $priceInUsdFormatted ?></button>
                        </form>
                    <?php else: ?>
                        <a href="watch_video.php?video_id=<?= $index + 1 ?>" class="button">Watch Video</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="footer">
        <div class="balance">Balance: <?= number_format($_SESSION['user']['solana_balance'], 8) ?> SOL</div>
        <div class="solana-address">SOL Address: <?= htmlspecialchars($_SESSION['user']['solana_address']) ?></div>
        <div class="timer" id="time"></div>
    </div>
</body>
</html>
