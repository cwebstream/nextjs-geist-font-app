<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'livestream_db');

// Agora credentials - Replace with your Agora project credentials
define('AGORA_APP_ID', ''); // Your Agora App ID from Agora Console
define('AGORA_APP_CERTIFICATE', ''); // Your Agora App Certificate from Agora Console
define('AGORA_PRIMARY_CERTIFICATE', ''); // Your Primary Certificate from Agora Console

// Database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is presenter
function isPresenter() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'presenter';
}

// Function to generate Agora token
function generateAgoraToken($channelName, $uid) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $appID = AGORA_APP_ID;
    $appCertificate = AGORA_APP_CERTIFICATE;
    $role = RtcTokenBuilder::RolePublisher;
    $expireTimeInSeconds = 3600; // Token expires in 1 hour
    $currentTimestamp = time();
    $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

    try {
        $token = RtcTokenBuilder::buildTokenWithUid(
            $appID,
            $appCertificate,
            $channelName,
            $uid,
            $role,
            $privilegeExpiredTs
        );
        return $token;
    } catch (Exception $e) {
        error_log("Error generating Agora token: " . $e->getMessage());
        return null;
    }
}
?>
