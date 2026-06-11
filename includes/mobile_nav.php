<?php
// Mobile Navigation Hub - Floating Action Bar
$current_page = basename($_SERVER['PHP_SELF']);
$is_inv = (basename(dirname($_SERVER['PHP_SELF'])) == 'inventori');
$prefix = $is_inv ? '../dss-saw/' : '';
$inv_prefix = $is_inv ? '' : '../inventori/';
?>

<div class="mobile-nav-hub d-lg-none">
    <div class="mobile-nav-inner">
        <a href="<?= $prefix ?>dashboard.php"
            class="mobile-nav-item <?= $current_page == 'dashboard.php' && !$is_inv ? 'active' : '' ?>">
            <div class="mobile-nav-icon"><i class="fas fa-columns"></i></div>
            <span>Home</span>
        </a>
        <a href="<?= $prefix ?>attendance.php"
            class="mobile-nav-item <?= $current_page == 'attendance.php' ? 'active' : '' ?>">
            <div class="mobile-nav-icon"><i class="fas fa-calendar-check"></i></div>
            <span>Absen</span>
        </a>
        <a href="<?= $prefix ?>employees.php"
            class="mobile-nav-item <?= $current_page == 'employees.php' ? 'active' : '' ?>">
            <div class="mobile-nav-icon"><i class="fas fa-users"></i></div>
            <span>Staff</span>
        </a>
        <a href="<?= $prefix ?>ranking.php"
            class="mobile-nav-item <?= $current_page == 'ranking.php' ? 'active' : '' ?>">
            <div class="mobile-nav-icon"><i class="fas fa-award"></i></div>
            <span>Rank</span>
        </a>
    </div>
</div>

<style>
    .mobile-nav-hub {
        position: fixed;
        bottom: 25px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        width: calc(100% - 40px);
        max-width: 450px;
        animation: slideUpNav 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes slideUpNav {
        from {
            transform: translate(-50%, 100px);
            opacity: 0;
        }

        to {
            transform: translate(-50%, 0);
            opacity: 1;
        }
    }

    .mobile-nav-inner {
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(25px) saturate(200%);
        -webkit-backdrop-filter: blur(25px) saturate(200%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 10px 15px;
        display: flex;
        justify-content: space-around;
        align-items: center;
        box-shadow:
            0 15px 35px -10px rgba(0, 0, 0, 0.5),
            0 0 1px 1px rgba(255, 255, 255, 0.05);
    }

    .mobile-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: #94a3b8 !important;
        text-decoration: none !important;
        transition: all 0.3s ease;
        flex: 1;
    }

    .mobile-nav-item span {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .mobile-nav-icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.1rem;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        background: transparent;
    }

    .mobile-nav-item.active {
        color: #fff !important;
    }

    .mobile-nav-item.active .mobile-nav-icon {
        background: linear-gradient(135deg, #6366f1 0%, #22d3ee 100%);
        box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
        transform: translateY(-8px) scale(1.1);
    }

    .mobile-nav-item.active span {
        color: #22d3ee;
        opacity: 1;
    }

    /* Ripple effect on touch */
    .mobile-nav-item:active .mobile-nav-icon {
        transform: scale(0.9);
        opacity: 0.7;
    }
</style>