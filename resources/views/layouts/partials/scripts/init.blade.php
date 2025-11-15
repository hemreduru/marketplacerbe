{{-- Page Initialization --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Apply theme from PHP session
        const serverDarkMode = {{ Session::get('app_dark_mode', false) ? 'true' : 'false' }};
        const theme = serverDarkMode ? 'dark' : 'light';
        document.documentElement.setAttribute('data-bs-theme', theme);

        // Apply language display
        const serverLocale = '{{ app()->getLocale() }}';
        const langDisplay = document.getElementById('current-language-code');
        if (langDisplay) {
            langDisplay.textContent = serverLocale.toUpperCase();
        }

        // Attach theme toggle handler
        const themeToggle = document.getElementById('kt_theme_mode_toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleTheme();
            });
        }

        // Attach language toggle handler
        const languageToggle = document.getElementById('kt_language_toggle');
        if (languageToggle) {
            languageToggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleLanguage();
            });
        }
    });
</script>
