<?php
session_start();

// Function to get the wallet address from the CSV file
function getWalletFromCSV() {
    $walletFile = 'wallets.csv';
    $walletAddress = '';
    
    if (file_exists($walletFile)) {
        if (($handle = fopen($walletFile, "r")) !== FALSE) {
            // Skip the header row
            fgetcsv($handle);

            // Read the first wallet entry
            if (($data = fgetcsv($handle)) !== FALSE) {
                $walletAddress = $data[0]; // Public key
            }
            fclose($handle);
        }
    }
    return $walletAddress;
}

// Initialize session variables
if (!isset($_SESSION['wallet_address'])) {
    $_SESSION['wallet_address'] = getWalletFromCSV();
    $_SESSION['created_at'] = time();
}

$walletAddress = $_SESSION['wallet_address'] ?? '';

// Timer setup
$timerExpiresAt = isset($_SESSION['created_at']) ? $_SESSION['created_at'] + (48 * 3600) : 0;
$timeRemaining = max(0, $timerExpiresAt - time());

// Fetch Solana price
$priceCommand = 'python fetch_price.py';
$price = shell_exec($priceCommand);

// Fetch wallet balance
if (!empty($walletAddress)) {
    $balanceCommand = 'python fetch_balance.py ' . escapeshellarg($walletAddress);
    $balance = shell_exec($balanceCommand);
    
    // Debug: output the full result of balance command
    $balance = htmlspecialchars(trim($balance));
} else {
    $balance = "Wallet address not found";
}

// Output results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solana Wallet</title>
    <script>
        function updateData() {
            fetch('fetch_data.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('walletBalance').textContent = data.balance;
                    document.getElementById('solanaPrice').textContent = data.price;
                    document.getElementById('timeRemaining').textContent = data.timeRemaining;
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        // Update data every second
        setInterval(updateData, 1000);
    </script>
</head>
<body>
    <h1>Your Solana Wallet</h1>
    <p><strong>Address:</strong> <span id="walletAddress"><?php echo htmlspecialchars($walletAddress); ?></span></p>
    <p><strong>Balance:</strong> <span id="walletBalance"><?php echo $balance; ?></span></p>
    <p><strong>Solana Price:</strong> $<span id="solanaPrice"><?php echo htmlspecialchars(trim($price)); ?></span></p>
    <p><strong>Time Remaining:</strong> <span id="timeRemaining"><?php echo gmdate("H:i:s", $timeRemaining); ?></span></p>
</body>
</html>
