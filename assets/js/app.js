// Modern Dashboard JS
document.addEventListener('DOMContentLoaded', () => {
    console.log('Premium Dashboard v3.0 Initialized');

    // Add staggered entry animation for bento cards if needed
    const cards = document.querySelectorAll('.bento-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.22, 1, 0.36, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });

    // Simple smooth scrolling for the sidebar active link
    const activeLink = document.querySelector('.modern-nav-link.active');
    if (activeLink) {
        activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
