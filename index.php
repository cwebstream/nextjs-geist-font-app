<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Get active stream if exists
$stmt = $conn->prepare("SELECT s.*, u.username as presenter_name 
                       FROM streams s 
                       JOIN users u ON s.presenter_id = u.id 
                       WHERE s.status = 'active' 
                       ORDER BY s.created_at DESC 
                       LIMIT 1");
$stmt->execute();
$activeStream = $stmt->get_result()->fetch_assoc();

// Get hand raises for presenter
$handRaises = [];
if (isPresenter() && $activeStream) {
    $stmt = $conn->prepare("SELECT h.*, u.username 
                           FROM hand_raises h 
                           JOIN users u ON h.user_id = u.id 
                           WHERE h.stream_id = ? AND h.status = 'pending'
                           ORDER BY h.created_at ASC");
    $stmt->bind_param("i", $activeStream['id']);
    $stmt->execute();
    $handRaises = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Streaming Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        .navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 1.25rem;
            color: #111;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .stream-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .video-container {
            background: #000;
            aspect-ratio: 16/9;
            position: relative;
        }

        .stream-controls {
            padding: 1rem;
            display: flex;
            gap: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-danger {
            background-color: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn-secondary {
            background-color: #4b5563;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #374151;
        }

        .hand-raises {
            margin-top: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }

        .hand-raises h2 {
            margin-bottom: 1rem;
            font-size: 1.25rem;
            color: #111;
        }

        .hand-raise-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .hand-raise-item:last-child {
            border-bottom: none;
        }

        .logout-btn {
            color: #dc2626;
            text-decoration: none;
        }

        .logout-btn:hover {
            text-decoration: underline;
        }

        .attendee-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        #speakerControls {
            display: none;
            gap: 1rem;
            align-items: center;
        }

        #speakerControls.active {
            display: flex;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Live Streaming Platform</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="stream-container">
            <div class="video-container" id="video-container">
                <!-- Agora video elements will be added here -->
            </div>
            
            <div class="stream-controls">
                <?php if (isPresenter()): ?>
                    <?php if (!$activeStream): ?>
                        <button class="btn btn-primary" onclick="startStream()">
                            <i class="fas fa-play"></i> Start Stream
                        </button>
                    <?php else: ?>
                        <button class="btn btn-danger" onclick="stopStream()">
                            <i class="fas fa-stop"></i> Stop Stream
                        </button>
                        <button class="btn btn-secondary" onclick="toggleMic()">
                            <i class="fas fa-microphone"></i> Mute/Unmute
                        </button>
                        <button class="btn btn-secondary" onclick="toggleCamera()">
                            <i class="fas fa-video"></i> Camera On/Off
                        </button>
                        <button class="btn btn-secondary" onclick="toggleScreenShare()">
                            <i class="fas fa-desktop"></i> Share Screen
                        </button>
                        <button class="btn btn-danger" onclick="leaveStream()">
                            <i class="fas fa-sign-out-alt"></i> Leave Stream
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($activeStream): ?>
                        <div class="attendee-controls">
                            <button class="btn btn-primary" onclick="raiseHand()" id="raiseHandBtn">
                                <i class="fas fa-hand"></i> Raise Hand
                            </button>
                            <div id="speakerControls" style="display: none;">
                                <button class="btn btn-secondary" onclick="toggleMic()">
                                    <i class="fas fa-microphone"></i> Mute/Unmute
                                </button>
                                <button class="btn btn-secondary" onclick="toggleCamera()">
                                    <i class="fas fa-video"></i> Camera On/Off
                                </button>
                                <button class="btn btn-danger" onclick="leaveStream()">
                                    <i class="fas fa-sign-out-alt"></i> Leave Stream
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isPresenter() && !empty($handRaises)): ?>
            <div class="hand-raises">
                <h2>Hand Raises</h2>
                <?php foreach ($handRaises as $raise): ?>
                    <div class="hand-raise-item">
                        <span><?php echo htmlspecialchars($raise['username']); ?></span>
                        <div>
                            <button class="btn btn-primary" onclick="approveHandRaise(<?php echo $raise['id']; ?>)">
                                Approve
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.x.js"></script>
    <script>
        // Agora client initialization
        const client = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
        let localTracks = {
            audioTrack: null,
            videoTrack: null,
            screenTrack: null
        };
        let isHost = <?php echo isPresenter() ? 'true' : 'false'; ?>;
        let isMuted = false;
        let isVideoOff = false;
        let isApprovedSpeaker = false;

        // Function to show speaker controls when approved
        function showSpeakerControls() {
            isApprovedSpeaker = true;
            document.getElementById('raiseHandBtn').style.display = 'none';
            const speakerControls = document.getElementById('speakerControls');
            speakerControls.classList.add('active');
            speakerControls.style.display = 'flex';
        }

        async function startStream() {
            try {
                const response = await fetch('stream_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=start'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    await initializeAgora(data.channel, data.token);
                    location.reload();
                }
            } catch (error) {
                console.error('Error starting stream:', error);
                alert('Failed to start stream');
            }
        }

        async function stopStream() {
            try {
                const response = await fetch('stream_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=stop'
                });
                
                if (response.ok) {
                    await leaveChannel();
                    location.reload();
                }
            } catch (error) {
                console.error('Error stopping stream:', error);
                alert('Failed to stop stream');
            }
        }

        async function initializeAgora(channel, token) {
            await client.join(<?php echo json_encode(AGORA_APP_ID); ?>, channel, token);
            
            if (isHost || isApprovedSpeaker) {
                // Get saved device preferences
                const selectedCamera = localStorage.getItem('selectedCamera');
                const selectedMic = localStorage.getItem('selectedMic');

                // Create tracks with selected devices
                localTracks.audioTrack = await AgoraRTC.createMicrophoneAudioTrack({
                    deviceId: selectedMic
                });
                localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack({
                    deviceId: selectedCamera
                });
                
                await client.publish(Object.values(localTracks));
                
                // Initialize UI states
                isMuted = false;
                isVideoOff = false;
                document.querySelector('[onclick="toggleMic()"] i').className = 'fas fa-microphone';
                document.querySelector('[onclick="toggleCamera()"] i').className = 'fas fa-video';
            }


            client.on("user-published", async (user, mediaType) => {
                await client.subscribe(user, mediaType);
                if (mediaType === "video") {
                    const playerContainer = document.createElement("div");
                    playerContainer.id = user.uid;
                    document.getElementById("video-container").append(playerContainer);
                    user.videoTrack.play(playerContainer);
                }
                if (mediaType === "audio") {
                    user.audioTrack.play();
                }
            });
        }

        async function toggleMic() {
            try {
                if (!localTracks.audioTrack) {
                    localTracks.audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
                    await client.publish(localTracks.audioTrack);
                }

                if (isMuted) {
                    await localTracks.audioTrack.setEnabled(true);
                    isMuted = false;
                    document.querySelector('[onclick="toggleMic()"] i').className = 'fas fa-microphone';
                } else {
                    await localTracks.audioTrack.setEnabled(false);
                    isMuted = true;
                    document.querySelector('[onclick="toggleMic()"] i').className = 'fas fa-microphone-slash';
                }
            } catch (error) {
                console.error('Error toggling microphone:', error);
                alert('Failed to toggle microphone. Please try again.');
            }
        }

        async function toggleCamera() {
            try {
                if (!localTracks.videoTrack && !localTracks.screenTrack) {
                    localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack();
                    await client.publish(localTracks.videoTrack);
                }

                if (isVideoOff) {
                    await localTracks.videoTrack.setEnabled(true);
                    isVideoOff = false;
                    document.querySelector('[onclick="toggleCamera()"] i').className = 'fas fa-video';
                } else {
                    await localTracks.videoTrack.setEnabled(false);
                    isVideoOff = true;
                    document.querySelector('[onclick="toggleCamera()"] i').className = 'fas fa-video-slash';
                }
            } catch (error) {
                console.error('Error toggling camera:', error);
                alert('Failed to toggle camera. Please try again.');
            }
        }

        async function leaveStream() {
            if (confirm('Are you sure you want to leave the stream?')) {
                await leaveChannel();
                window.location.reload();
            }
        }

        async function leaveChannel() {
            for (let trackName in localTracks) {
                let track = localTracks[trackName];
                if (track) {
                    track.stop();
                    track.close();
                    localTracks[trackName] = null;
                }
            }
            await client.leave();
        }

        async function toggleScreenShare() {
            try {
                if (localTracks.screenTrack) {
                    // Stop screen sharing
                    await client.unpublish(localTracks.screenTrack);
                    localTracks.screenTrack.stop();
                    localTracks.screenTrack.close();
                    localTracks.screenTrack = null;
                    
                    // Switch back to camera if it was active
                    if (!localTracks.videoTrack) {
                        localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack();
                        await client.publish(localTracks.videoTrack);
                    }
                } else {
                    // Start screen sharing
                    if (localTracks.videoTrack) {
                        await client.unpublish(localTracks.videoTrack);
                        localTracks.videoTrack.stop();
                        localTracks.videoTrack.close();
                        localTracks.videoTrack = null;
                    }
                    
                    localTracks.screenTrack = await AgoraRTC.createScreenVideoTrack({
                        encoderConfig: "1080p_1",
                        optimizationMode: "detail"
                    });
                    await client.publish(localTracks.screenTrack);

                    // Handle screen share stopped by user through browser
                    localTracks.screenTrack.on("track-ended", async () => {
                        await client.unpublish(localTracks.screenTrack);
                        localTracks.screenTrack.stop();
                        localTracks.screenTrack.close();
                        localTracks.screenTrack = null;

                        // Switch back to camera
                        localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack();
                        await client.publish(localTracks.videoTrack);
                    });
                }
            } catch (error) {
                console.error('Error toggling screen share:', error);
                alert('Failed to toggle screen share. Please try again.');
            }
        }

        async function raiseHand() {
            try {
                const response = await fetch('stream_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=raise_hand'
                });
                
                if (response.ok) {
                    alert('Hand raised! Waiting for presenter approval.');
                }
            } catch (error) {
                console.error('Error raising hand:', error);
                alert('Failed to raise hand');
            }
        }

        async function approveHandRaise(raiseId) {
            try {
                const response = await fetch('stream_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=approve_hand&raise_id=${raiseId}`
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        showSpeakerControls();
                    }
                    location.reload();
                }
            } catch (error) {
                console.error('Error approving hand raise:', error);
                alert('Failed to approve hand raise');
            }
        }

        // Check if devices are selected, if not redirect to setup
        if ((isHost || isApprovedSpeaker) && 
            (!localStorage.getItem('selectedCamera') || !localStorage.getItem('selectedMic'))) {
            window.location.href = 'device_setup.php';
        }

        // Initialize if there's an active stream
        <?php if ($activeStream): ?>
        initializeAgora(
            <?php echo json_encode($activeStream['channel_name']); ?>,
            generateAgoraToken(<?php echo json_encode($activeStream['channel_name']); ?>, <?php echo $_SESSION['user_id']; ?>)
        );
        <?php endif; ?>
    </script>
</body>
</html>
