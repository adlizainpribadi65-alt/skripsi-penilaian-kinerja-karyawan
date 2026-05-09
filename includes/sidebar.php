<aside class="sidebar">
    <!-- Animated Particle Layer -->
    <div class="sidebar-particles">
        <div class="particle p1"></div>
        <div class="particle p2"></div>
        <div class="particle p3"></div>
        <div class="particle p4"></div>
        <div class="particle p5"></div>
    </div>

    <!-- Noise Texture Overlay -->
    <div class="sidebar-noise"></div>

    <!-- Top Accent Line -->
    <div class="sidebar-top-accent"></div>

    <div class="brand-section">
        <div class="brand-logo-ring">
            <div class="brand-logo-inner">
                <i class="fas fa-atom brand-icon-spin"></i>
            </div>
        </div>
        <div class="brand-font fs-3 fw-bold text-white mb-0 tracking-tight shimmer-neon">REEGIOELLA</div>
        <div class="text-primary tiny fw-bold opacity-75 mt-1" style="letter-spacing: 0.5em; font-size: 0.61rem;">
            ECOSYSTEM_HUB</div>
        <div class="brand-status-pill">
            <span class="status-dot-live"></span>
            <span>SYSTEM ONLINE</span>
        </div>
    </div>

    <nav class="sidebar-nav flex-grow-1 d-flex flex-column">
        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-compass"></i></span>
            Main Hub
        </div>
        <a href="dashboard.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-indigo">
                <i class="fas fa-columns"></i>
            </div>
            <span>Dashboard Hub</span>
            <div class="nav-item-glow"></div>
        </a>

        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-link"></i></span>
            Supply Chain
        </div>
        <a href="../inventori/dashboard.php" class="nav-item">
            <div class="nav-icon-box icon-emerald">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <span>Inventory Core</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="../inventori/stok.php" class="nav-item">
            <div class="nav-icon-box icon-amber">
                <i class="fas fa-warehouse"></i>
            </div>
            <span>Monitoring Stok</span>
            <div class="nav-item-glow"></div>
        </a>
        <a href="production.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'production.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-violet">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <span>Log Penilaian</span>
            <div class="nav-item-glow"></div>
        </a>

        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-fingerprint"></i></span>
            Human Capital
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

        <div class="sidebar-title">
            <span class="sidebar-title-icon"><i class="fas fa-brain"></i></span>
            Decision Engine
        </div>
        <a href="criteria.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'criteria.php' ? 'active' : '' ?>">
            <div class="nav-icon-box icon-indigo">
                <i class="fas fa-sliders"></i>
            </div>
            <span>Kriteria Penilaian</span>
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
                <div class="fw-bold text-white small text-truncate"><?= $_SESSION['username'] ?? 'Administrator' ?>
                </div>
                <div class="user-role-tag">
                    <span class="role-dot"></span>
                    Root Authority
                </div>
            </div>
            <button id="theme-toggle" class="logout-btn mx-1" title="Toggle Light/Dark Mode">
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