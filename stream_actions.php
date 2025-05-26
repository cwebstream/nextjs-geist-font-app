<?php
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';
$conn = getDBConnection();

switch ($action) {
    case 'start':
        if (!isPresenter()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only presenters can start streams']);
            exit();
        }

        // Check if presenter already has an active stream
        $stmt = $conn->prepare("SELECT id FROM streams WHERE presenter_id = ? AND status = 'active'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'You already have an active stream']);
            exit();
        }

        // Create new stream
        $channelName = 'stream_' . time() . '_' . $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO streams (presenter_id, title, channel_name, status) VALUES (?, ?, ?, 'active')");
        $title = "Live Stream by " . $_SESSION['username'];
        $stmt->bind_param("iss", $_SESSION['user_id'], $title, $channelName);
        
        if ($stmt->execute()) {
            $streamId = $conn->insert_id;
            $token = generateAgoraToken($channelName, $_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'stream_id' => $streamId,
                'channel' => $channelName,
                'token' => $token
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create stream']);
        }
        break;

    case 'stop':
        if (!isPresenter()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only presenters can stop streams']);
            exit();
        }

        // Stop active stream
        $stmt = $conn->prepare("UPDATE streams SET status = 'inactive' WHERE presenter_id = ? AND status = 'active'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to stop stream']);
        }
        break;

    case 'raise_hand':
        // Get active stream
        $stmt = $conn->prepare("SELECT id FROM streams WHERE status = 'active' LIMIT 1");
        $stmt->execute();
        $stream = $stmt->get_result()->fetch_assoc();

        if (!$stream) {
            http_response_code(400);
            echo json_encode(['error' => 'No active stream found']);
            exit();
        }

        // Check if user already has a pending hand raise
        $stmt = $conn->prepare("SELECT id FROM hand_raises WHERE stream_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $stream['id'], $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'You already have a pending hand raise']);
            exit();
        }

        // Create hand raise request
        $stmt = $conn->prepare("INSERT INTO hand_raises (stream_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $stream['id'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to raise hand']);
        }
        break;

    case 'approve_hand':
        if (!isPresenter()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only presenters can approve hand raises']);
            exit();
        }

        $raiseId = $_POST['raise_id'] ?? 0;
        
        // Verify the hand raise belongs to presenter's active stream
        $stmt = $conn->prepare("
            SELECT h.id 
            FROM hand_raises h 
            JOIN streams s ON h.stream_id = s.id 
            WHERE h.id = ? AND s.presenter_id = ? AND s.status = 'active' AND h.status = 'pending'
        ");
        $stmt->bind_param("ii", $raiseId, $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid hand raise request']);
            exit();
        }

        // Approve hand raise
        $stmt = $conn->prepare("UPDATE hand_raises SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $raiseId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to approve hand raise']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>
