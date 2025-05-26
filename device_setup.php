<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Setup - Live Streaming Platform</title>
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

        .setup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #111;
        }

        .device-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .device-section h2 {
            margin-bottom: 1rem;
            font-size: 1.25rem;
            color: #374151;
        }

        .device-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.875rem;
            flex: 1;
        }

        .preview-container {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        #audioMeter {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        #audioLevel {
            height: 100%;
            width: 0%;
            background: #2563eb;
            transition: width 0.1s ease;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
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

        .btn-secondary {
            background-color: #4b5563;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #374151;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .device-status {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .status-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-error {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Device Setup</h1>
        
        <div class="device-section">
            <h2>Camera</h2>
            <div class="device-controls">
                <select id="cameraSelect">
                    <option value="">Loading cameras...</option>
                </select>
                <button class="btn btn-secondary" onclick="toggleCamera()">
                    <i class="fas fa-video"></i> Test Camera
                </button>
            </div>
            <div class="preview-container">
                <video id="cameraPreview" autoplay muted playsinline></video>
            </div>
            <div id="cameraStatus" class="device-status"></div>
        </div>

        <div class="device-section">
            <h2>Microphone</h2>
            <div class="device-controls">
                <select id="micSelect">
                    <option value="">Loading microphones...</option>
                </select>
                <button class="btn btn-secondary" onclick="toggleMicrophone()">
                    <i class="fas fa-microphone"></i> Test Microphone
                </button>
            </div>
            <div id="audioMeter">
                <div id="audioLevel"></div>
            </div>
            <div id="micStatus" class="device-status"></div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" onclick="saveAndContinue()">
                <i class="fas fa-check"></i> Continue to Stream
            </button>
        </div>
    </div>

    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.x.js"></script>
    <script>
        let cameras = [];
        let microphones = [];
        let currentCamera = null;
        let currentMic = null;
        let audioLevel = null;
        
        // Initialize device detection
        async function initializeDevices() {
            try {
                // Get camera and microphone permissions
                await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                
                // Get available devices
                const devices = await AgoraRTC.getDevices();
                cameras = devices.filter(device => device.kind === 'videoinput');
                microphones = devices.filter(device => device.kind === 'audioinput');

                // Populate device selections
                const cameraSelect = document.getElementById('cameraSelect');
                const micSelect = document.getElementById('micSelect');

                cameraSelect.innerHTML = cameras.map(camera => 
                    `<option value="${camera.deviceId}">${camera.label}</option>`
                ).join('');

                micSelect.innerHTML = microphones.map(mic => 
                    `<option value="${mic.deviceId}">${mic.label}</option>`
                ).join('');

                // Set initial status
                updateStatus('cameraStatus', 'Please test your camera', '');
                updateStatus('micStatus', 'Please test your microphone', '');

            } catch (error) {
                console.error('Error initializing devices:', error);
                updateStatus('cameraStatus', 'Error accessing camera', 'error');
                updateStatus('micStatus', 'Error accessing microphone', 'error');
            }
        }

        // Toggle camera preview
        async function toggleCamera() {
            try {
                if (currentCamera) {
                    currentCamera.close();
                    currentCamera = null;
                    document.getElementById('cameraPreview').srcObject = null;
                    return;
                }

                const deviceId = document.getElementById('cameraSelect').value;
                currentCamera = await AgoraRTC.createCameraVideoTrack({
                    deviceId: deviceId
                });

                const previewElement = document.getElementById('cameraPreview');
                currentCamera.play(previewElement);
                
                updateStatus('cameraStatus', 'Camera is working properly', 'success');
            } catch (error) {
                console.error('Error testing camera:', error);
                updateStatus('cameraStatus', 'Failed to test camera', 'error');
            }
        }

        // Toggle microphone test
        async function toggleMicrophone() {
            try {
                if (currentMic) {
                    currentMic.close();
                    currentMic = null;
                    if (audioLevel) clearInterval(audioLevel);
                    document.getElementById('audioLevel').style.width = '0%';
                    return;
                }

                const deviceId = document.getElementById('micSelect').value;
                currentMic = await AgoraRTC.createMicrophoneAudioTrack({
                    deviceId: deviceId
                });

                // Start audio level monitoring
                audioLevel = setInterval(() => {
                    const level = currentMic.getVolumeLevel() * 100;
                    document.getElementById('audioLevel').style.width = `${level}%`;
                }, 100);

                updateStatus('micStatus', 'Microphone is working properly', 'success');
            } catch (error) {
                console.error('Error testing microphone:', error);
                updateStatus('micStatus', 'Failed to test microphone', 'error');
            }
        }

        // Update status messages
        function updateStatus(elementId, message, type) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.className = 'device-status' + (type ? ` status-${type}` : '');
        }

        // Save selected devices and continue to main room
        function saveAndContinue() {
            // Store selected device IDs in localStorage
            const selectedCamera = document.getElementById('cameraSelect').value;
            const selectedMic = document.getElementById('micSelect').value;
            
            localStorage.setItem('selectedCamera', selectedCamera);
            localStorage.setItem('selectedMic', selectedMic);

            // Clean up
            if (currentCamera) currentCamera.close();
            if (currentMic) {
                currentMic.close();
                clearInterval(audioLevel);
            }

            // Redirect to main room
            window.location.href = 'index.php';
        }

        // Initialize on page load
        window.addEventListener('load', initializeDevices);
    </script>
</body>
</html>
