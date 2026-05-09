<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
checkLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../employees.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: ../employees.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="content-main">
        <div id="camera-help" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9000; justify-content: center; align-items: center; backdrop-filter: blur(40px);">
            <div class="glass p-5 text-start" style="max-width: 600px; border: 2px solid var(--accent);">
                <div class="brand-font text-rose mb-4 fs-3 tracking-widest">RESTRICTED_ACCESS</div>
                <p class="text-white fs-5 mb-4">Pendaftaran biometrik membutuhkan protokol keamanan (HTTPS/Localhost). Jika Anda menggunakan IP publik untuk testing:</p>
                <div class="bg-black bg-opacity-50 p-4 rounded-4 mb-4 border border-white border-opacity-10">
                    <code class="text-emerald d-block small mb-2">1. Buka: chrome://flags/#unsafely-treat-insecure-origin-as-secure</code>
                    <code class="text-emerald d-block small mb-2">2. Aktifkan "Insecure origins as secure"</code>
                    <code class="text-emerald d-block small">3. Tambahkan IP: http://<?= $_SERVER['HTTP_HOST'] ?></code>
                </div>
                <button onclick="location.reload()" class="btn-premium w-100 py-3">RETRY HANDSHAKE</button>
            </div>
        </div>

        <div class="header-section mb-5 animate-fadeIn">
            <a href="../employees.php" class="text-muted small fw-bold text-uppercase tracking-widest mb-3 d-inline-block hover-text-primary transition-all">
                <i class="fas fa-arrow-left me-2"></i> Employee Directory
            </a>
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="badge-glass badge-indigo mb-3 font-monospace">BIOMETRIC_ENROLLMENT_V4.0</div>
                    <h1 class="display-5 fw-bold text-white mb-2">Pendaftaran <span class="shimmer-text">Wajah</span></h1>
                    <p class="text-muted fs-5">Inisialisasi tanda tangan biometrik untuk otorisasi terminal.</p>
                </div>
            </div>
        </div>

        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <div class="glass p-5 animate-fadeIn">
                    <div class="widget-title mb-4"><i class="fas fa-id-badge text-primary"></i> Subject Information</div>
                    <div class="d-flex align-items-center gap-4 mb-5">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 1px solid var(--primary-glow);">
                            <i class="fas fa-user-tie text-primary fs-2"></i>
                        </div>
                        <div>
                            <div class="text-white fs-3 fw-bold"><?= htmlspecialchars($employee['name']) ?></div>
                            <div class="badge-glass badge-indigo mt-1"><?= htmlspecialchars($employee['nik']) ?></div>
                        </div>
                    </div>

                    <div class="glass p-4 border-opacity-5 mb-4" style="background: rgba(255,255,255,0.02);">
                        <div class="text-white-50 small fw-bold text-uppercase mb-2"><i class="fas fa-shield-check text-emerald me-2"></i> Security Protocol</div>
                        <div class="text-muted tiny leading-relaxed">
                            Analisis AI dilakukan sepenuhnya di browser. Tidak ada data gambar yang dikirim ke server. Hanya `Face Descriptor` (matematika wajah) yang disimpan sebagai tanda tangan unik.
                        </div>
                    </div>

                    <div class="d-grid gap-3 mt-5">
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Scanner Status:</span>
                            <span id="scan-status" class="fw-bold text-primary">IDLE</span>
                        </div>
                        <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                            <div id="scan-progress" class="progress-bar bg-primary" style="width: 0%; transition: width 0.3s ease;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="glass p-3 rounded-5 overflow-hidden shadow-2xl animate-fadeIn stagger-1" style="background: #000;">
                    <div class="video-container position-relative rounded-4 overflow-hidden">
                        <div id="ai-label" class="badge-glass border-opacity-20 position-absolute m-3" style="top:0; left:0; z-index:100; background: rgba(2,6,23,0.8);">INITIALIZING AI...</div>
                        <video id="video" class="w-100 h-100" autoplay muted playsinline style="object-fit: cover; filter: contrast(1.1) brightness(0.9); height: 500px !important;"></video>
                        <canvas id="overlay" class="position-absolute top-0 left-0 w-100 h-100" style="z-index: 50;"></canvas>
                        
                        <div class="position-absolute bottom-0 left-0 w-100 p-5 text-center" style="z-index: 100; background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, transparent 100%);">
                            <button id="capture-btn" class="btn-premium py-3 px-5 fs-5" disabled>
                                <i class="fas fa-face-viewfinder me-2"></i> REGISTER FACE SIGNATURE
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Face API -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    const captureBtn = document.getElementById('capture-btn');
    const aiLabel = document.getElementById('ai-label');
    const scanStatus = document.getElementById('scan-status');
    const scanProgress = document.getElementById('scan-progress');
    let faceDescriptor = null;

    async function init() {
        try {
            aiLabel.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> CALLING_AI_MODELS';
            const MODEL_URL = 'https://raw.githubusercontent.com/vladmandic/face-api/master/model/';
            await Promise.all([
                face-api.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                face-api.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                face-api.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);

            aiLabel.innerHTML = '<i class="fas fa-microchip fa-spin me-2"></i> WARMING_SCANNER';
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            
            video.onloadedmetadata = () => {
                aiLabel.innerHTML = '<i class="fas fa-circle-dot text-emerald me-2"></i> SYSTEM_HEALTH_OK';
                scanStatus.innerText = 'WAITING_FOR_FACE';
                startDetection();
            };
        } catch (err) {
            console.error(err);
            document.getElementById('camera-help').style.display = 'flex';
        }
    }

    async function startDetection() {
        const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
        face-api.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            const detections = await face-api.detectAllFaces(video, new face-api.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptors();

            const resizedDetections = face-api.resizeResults(detections, displaySize);
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw Elite Reticle
            resizedDetections.forEach(detection => {
                const box = detection.detection.box;
                const ctx = canvas.getContext('2d');
                ctx.strokeStyle = '#6366f1';
                ctx.lineWidth = 4;
                ctx.setLineDash([10, 5]);
                ctx.strokeRect(box.x, box.y, box.width, box.height);
                ctx.setLineDash([]);
                
                // Draw corners
                ctx.fillStyle = '#6366f1';
                const len = 20;
                ctx.fillRect(box.x-2, box.y-2, len, 5);
                ctx.fillRect(box.x-2, box.y-2, 5, len);
                ctx.fillRect(box.x+box.width-len+2, box.y-2, len, 5);
                ctx.fillRect(box.x+box.width-2, box.y-2, 5, len);
            });

            if (detections.length > 0) {
                faceDescriptor = detections[0].descriptor;
                captureBtn.disabled = false;
                scanStatus.innerText = 'FACE_LOCKED';
                scanStatus.classList.replace('text-primary', 'text-emerald');
                scanProgress.style.width = '100%';
                aiLabel.innerHTML = '<i class="fas fa-face-smile text-emerald me-2"></i> SCANNER_STABLE';
            } else {
                captureBtn.disabled = true;
                faceDescriptor = null;
                scanStatus.innerText = 'SEARCHING...';
                scanStatus.classList.replace('text-emerald', 'text-primary');
                scanProgress.style.width = '20%';
                aiLabel.innerHTML = '<i class="fas fa-face-meh text-white-50 me-2"></i> ALIGN_FACE_TO_CENTER';
            }
        }, 150);
    }

    captureBtn.addEventListener('click', async () => {
        if (!faceDescriptor) return;

        captureBtn.disabled = true;
        captureBtn.innerHTML = '<i class="fas fa-sync fa-spin me-2"></i> UPLOADING_SIGNATURE';

        const response = await fetch('save_face.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: <?= $id ?>,
                descriptor: Array.from(faceDescriptor)
            })
        });

        const result = await response.json();
        if (result.success) {
            window.location.href = '../employees.php?msg=Biometric Signature Saved Successfully';
        } else {
            alert("Error: " + result.message);
            captureBtn.disabled = false;
            captureBtn.innerHTML = '<i class="fas fa-face-viewfinder me-2"></i> RETRY CAPTURE';
        }
    });

    init();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
