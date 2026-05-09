document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const root = document.documentElement;

    // Load initial state
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Ensure root attribute is set (matches header script redundancy)
    if (!root.getAttribute('data-theme')) {
        root.setAttribute('data-theme', currentTheme);
    }

    const updateIcon = (theme) => {
        if (!themeIcon) return;
        if (theme === 'dark') {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
    };

    updateIcon(currentTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const theme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            
            // Apply theme with a transition effect
            root.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            updateIcon(theme);
            
            // Premium animation feedback
            if (themeIcon) {
                themeIcon.classList.add('animate-spin-once');
                setTimeout(() => themeIcon.classList.remove('animate-spin-once'), 600);
            }
        });
    }
});
