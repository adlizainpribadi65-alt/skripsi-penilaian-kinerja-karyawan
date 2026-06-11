<?php
require_once __DIR__ . '/../includes/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Industri - Reegioella Hub</title>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Design System -->
    <link rel="stylesheet" href="../assets/css/modern.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at 50% 50%, #1e1b4b 0%, var(--bg-dark) 80%);
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .terminal-container {
            width: 100%;
            max-width: 900px;
            padding: 40px;
            text-align: center;
            animation: entrance-fade 1s cubic-bezier(0.16, 1, 0.3, 1) both;
            position: relative;
            z-index: 10;
        }

        .kiosk-clock {
            font-family: 'Outfit', sans-serif;
            font-size: 9rem;
            font-weight: 800;
            line-height: 0.9;
            background: linear-gradient(180deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
            letter-spacing: -0.05em;
            filter: drop-shadow(0 0 50px var(--primary-glow));
            animation: clock-pulse 4s ease-in-out infinite alternate;
        }

        @keyframes clock-pulse {
            from { transform: scale(1); filter: drop-shadow(0 0 40px var(--primary-glow)); }
            to { transform: scale(1.02); filter: drop-shadow(0 0 70px var(--primary-glow)); }
        }

        .input-mega-box {
            background: rgba(13, 17, 23, 0.4);
            backdrop-filter: blur(40px) saturate(180%);
            border: 2px solid var(--border-glass);
            border-radius: 48px;
            padding: 70px 50px;
            margin-top: 40px;
            box-shadow: 
                0 60px 120px -30px rgba(0,0,0,0.8),
                inset 0 1px 1px rgba(255,255,255,0.05);
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .input-mega-box::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            animation: scanner-sweep 3s infinite;
        }

        @keyframes scanner-sweep {
            0% { transform: translateY(-100%); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateY(600%); opacity: 0; }
        }

        .input-mega-box:focus-within {
            border-color: var(--primary);
            transform: translateY(-8px);
            background: rgba(13, 17, 23, 0.6);
            box-shadow: 0 80px 150px -40px rgba(99, 102, 241, 0.25);
        }

        .nik-mega-input {
            width: 100%;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 5.5rem;
            font-weight: 800;
            text-align: center;
            letter-spacing: 0.1em;
            font-family: 'Outfit', sans-serif;
            outline: none;
            text-transform: uppercase;
            caret-color: var(--primary);
        }

        .nik-mega-input::placeholder {
            color: rgba(255,255,255,0.03);
            letter-spacing: 0;
        }

        #feedback-area {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 5000;
            background: rgba(2, 4, 8, 0.98);
            backdrop-filter: blur(60px);
            animation: entrance-fade 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .result-card {
            background: transparent;
            border: none;
            box-shadow: none;
            transform: scale(1.1);
        }

        .status-badge-kiosk {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.4em;
            color: var(--primary);
            text-transform: uppercase;
            padding: 8px 24px;
            border: 1px solid var(--primary-glow);
            border-radius: 100px;
            display: inline-block;
            margin-bottom: 20px;
        }

        /* Ambient Orbs for Terminal */
        .terminal-orb {
            position: absolute;
            width: 800px; height: 800px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            z-index: 1;
        }
    </style>
</head>
<body>

    <div class="terminal-orb" style="background: radial-gradient(circle, var(--primary) 0%, transparent 70%); top: -400px; left: -400px;"></div>
    <div class="terminal-orb" style="background: radial-gradient(circle, var(--cyan) 0%, transparent 70%); bottom: -400px; right: -400px;"></div>

    <div class="terminal-container">
        <div class="mb-5">
            <div class="status-badge-kiosk"><i class="fas fa-tower-broadcast me-2"></i> Terminal Hub Industri</div>
            <div id="digital-clock" class="kiosk-clock">00:00:00</div>

        </div>

        <div class="input-mega-box">
            <div class="text-muted small fw-bold text-uppercase mb-4 tracking-widest opacity-50">Silakan Scan ID Card atau Ketik NIK Anda</div>
            <form id="attendance-form">
                <input type="text" id="nik-input" class="nik-mega-input" placeholder="000-000" autocomplete="off" autofocus>
            </form>
            <div class="mt-5 text-muted small fw-bold tracking-widest opacity-25">
                <i class="fas fa-keyboard-left me-2"></i> PENGISIAN OTOMATIS AKTIF
            </div>
        </div>

        <div class="mt-5 pt-3 d-flex justify-content-center gap-5 opacity-50">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-shield-check text-primary fs-4"></i>
                <div class="text-start">
                    <div class="text-white fw-bold tiny tracking-widest">AES-256</div>
                    <div class="text-muted tiny">ENKRIPSI</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-bolt-lightning text-cyan fs-4"></i>
                <div class="text-start">
                    <div class="text-white fw-bold tiny tracking-widest">INSTAN</div>
                    <div class="text-muted tiny">SINKRONISASI SAW</div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="../attendance.php" class="btn btn-outline-primary px-4 py-2" style="border-radius: 50px; text-decoration: none;">
                <i class="fas fa-desktop me-2"></i> CEK LIVE ABSENSI
            </a>
        </div>
    </div>

    <!-- Cinematic Feedback -->
    <div id="feedback-area">
        <div class="result-card text-center">
            <div id="res-icon" style="font-size: 10rem; margin-bottom: 40px; filter: drop-shadow(0 0 30px currentColor);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="text-muted small fw-bold tracking-widest text-uppercase mb-2 opacity-50 font-monospace">DATA PERSONEL</div>
            <div id="res-name" class="display-3 fw-bold mb-3 shimmer-text brand-font">NAME</div>
            <div id="res-msg" class="text-muted fs-4 fw-bold tracking-widest text-uppercase">ACCESS GRANTED</div>
        </div>
    </div>

    <script>
        const feedback = document.getElementById('feedback-area');
        const input = document.getElementById('nik-input');
        let isProcessing = false;

        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('digital-clock').innerText = `${h}:${m}:${s}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        document.getElementById('attendance-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const val = input.value.trim();
            if(!val || isProcessing) return;

            isProcessing = true;
            input.disabled = true;

            try {
                const response = await fetch('process_kiosk.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `nik=${val}`
                });
                const data = await response.json();

                if (data.success) {
                    showResult(true, data.name, "OTORISASI_BERHASIL");
                } else {
                    showResult(false, "DITOLAK", data.message || "ID_TIDAK_VALID");
                }
            } catch (err) {
                showResult(false, "ERROR", "SISTEM_OFFLINE_KEGAGALAN");
            } finally {
                input.value = '';
                input.disabled = false;
                input.focus();
            }
        });

        function showResult(success, name, msg) {
            const icon = document.getElementById('res-icon');
            const nameEl = document.getElementById('res-name');
            const msgEl = document.getElementById('res-msg');
            
            icon.style.color = success ? 'var(--secondary)' : 'var(--accent)';
            icon.innerHTML = `<i class="fas ${success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>`;
            
            nameEl.innerText = name.toUpperCase();
            nameEl.className = success ? 'display-3 fw-bold mb-3 shimmer-text brand-font' : 'display-3 fw-bold mb-3 text-rose brand-font';
            
            msgEl.innerText = msg;
            msgEl.className = success ? 'text-emerald fs-4 fw-bold tracking-widest text-uppercase' : 'text-rose fs-4 fw-bold tracking-widest text-uppercase';
            
            feedback.style.display = 'flex';
            
            setTimeout(() => {
                feedback.style.display = 'none';
                isProcessing = false;
            }, 3000);
        }

        // Keep focus on input
        document.addEventListener('click', () => input.focus());
    </script>
</body>
</html>
