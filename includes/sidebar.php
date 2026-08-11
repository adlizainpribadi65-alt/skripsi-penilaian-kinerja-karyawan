<aside class="sidebar">
    <!-- Top Accent Line -->
    <div class="sidebar-top-accent"></div>

    <div class="brand-section">
        <div
            style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; background: white; margin: 0 auto 8px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <img src="<?= $base_url ?>assets/img/logo_ry.png" alt="RY"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="fs-5 fw-bold mb-0" style="font-family: 'Inter', sans-serif; letter-spacing: 1px; color: #1e293b;">
            REEGIOELLA</div>

        <!-- Minimalist Status Dot (Top-Right) -->
        <div class="brand-status-pill-mini">
            <span class="status-dot-live"></span>
        </div>

    </div>

    <nav class="sidebar-nav flex-grow-1 d-flex flex-column">
        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-compass"></i></span>
            Hub Utama
        </div>
        <a href="dashboard.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-indigo">
                <i class="fas fa-columns"></i>
            </div>
            <span>Dashboard Hub</span>
            <div class="nav-item-glow"></div>
        </a>

        <!-- Logistik & Inventori (Disembunyikan) -->


        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-star"></i></span>
            Penilaian Kinerja
        </div>
        <a href="production.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'production.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-violet">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <span>Log Penilaian</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="scores.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'scores.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-cyan">
                <i class="fas fa-database"></i>
            </div>
            <span>Manajemen Skor</span>
            <div class="nav-item-glow"></div>
        </a>

        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-fingerprint"></i></span>
            Sumber Daya Manusia
        </div>
        <a href="employees.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-cyan">
                <i class="fas fa-users-viewfinder"></i>
            </div>
            <span>Direktori Personel</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="attendance.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-rose">
                <i class="fas fa-calendar-check"></i>
            </div>
            <span>Riwayat Presensi</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="industrial/kiosk.php" target="_blank" class="nav-item">
            <div class="nav-icon-box icon-teal">
                <i class="fas fa-display"></i>
            </div>
            <span>Terminal Kiosk</span>
            <span class="nav-ext-badge"><i class="fas fa-arrow-up-right-from-square"></i></span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="kiosk_qr.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'kiosk_qr.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-violet">
                <i class="fas fa-qrcode"></i>
            </div>
            <span>QR Kiosk</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="kiosk_qr_pdf.php" target="_blank" class="nav-item">
            <div class="nav-icon-box icon-rose">
                <i class="fas fa-file-pdf"></i>
            </div>
            <span>Cetak PDF QR</span>
            <span class="nav-ext-badge"><i class="fas fa-arrow-up-right-from-square"></i></span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="sop_termination.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'sop_termination.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-rose" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);">
                <i class="fas fa-file-shield"></i>
            </div>
            <span>SOP Pemberhentian</span>
            <div class="nav-item-glow"></div>
        </a>

        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-brain"></i></span>
            Perangkingan & Keputusan
        </div>
        <a href="rekap_mingguan.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'rekap_mingguan.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-teal">
                <i class="fas fa-chart-pie"></i>
            </div>
            <span>Rekapitulasi Mingguan</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="criteria.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'criteria.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-indigo">
                <i class="fas fa-sliders"></i>
            </div>
            <span>Kriteria Penilaian</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="ahp.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'ahp.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-emerald">
                <i class="fas fa-balance-scale"></i>
            </div>
            <span>Matriks AHP</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="ranking.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'ranking.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-gold">
                <i class="fas fa-award"></i>
            </div>
            <span>Peringkat Akhir</span>
            <div class="nav-item-glow"></div>
        </a>
    </nav>

    <!-- Sidebar Footer - User Profile -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-divider"></div>
        <div class="sidebar-user-card">
            <div class="user-avatar-ring">
                <div class="user-avatar">
                    <i class="fas fa-shield-halved"></i>
                </div>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-bold small text-truncate" style="color: #1e293b;"><?= $_SESSION['username'] ?? 'Administrator' ?>
                </div>
                <div class="user-role-tag">
                    <span class="role-dot"></span>
                    Otoritas Penuh
                </div>
            </div>
            <button id="theme-toggle" class="logout-btn mx-1" title="Ganti Mode Terang/Gelap">
                <i class="fas fa-moon shadow-glow-mini" id="theme-icon"></i>
            </button>
            <a href="logout.php" class="logout-btn" title="Logout">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
    </div>

    <!-- Theme Control Logic -->
    <script src="<?= $base_url ?>assets/js/theme-toggle.js"></script>

</aside>