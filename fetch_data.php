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

// Output results as JSON
header('Content-Type: application/json');
echo json_encode([
    'walletAddress' => $walletAddress,
    'balance' => $balance,
    'price' => trim($price),
    'timeRemaining' => gmdate("H:i:s", $timeRemaining)
]);
?>
