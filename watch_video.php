<?php
session_start();

if (!isset($_SESSION['user']) || !in_array($_GET['video_id'], $_SESSION['user']['purchased_videos'])) {
    header('Location: index.php');
    exit;
}

$videoId = htmlspecialchars($_GET['video_id']);
$videoFile = "../videos/video{$videoId}.mp4";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Video</title>
</head>
<body>
    <h1>Watch Video</h1>
    <video controls>
        <source src="<?= $videoFile ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <a href="index.php" class="back-button">Back to Homepage</a>
</body>
</html>
