{{-- Theme Management Functions --}}
<script>
    /**
     * Toggle theme
     */
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        const isDarkMode = newTheme === 'dark';

        // Apply immediately
        document.documentElement.setAttribute('data-bs-theme', newTheme);

        // Update database and session
        axios.post('/settings/theme', {
            dark_mode: isDarkMode
        }).catch(error => {
            // Revert on error
            document.documentElement.setAttribute('data-bs-theme', currentTheme);
            console.error('Failed to save theme preference:', error);
        });
    }

    /**
     * Toggle language
     */
    function toggleLanguage() {
        const currentLang = '{{ app()->getLocale() }}';
        const newLang = currentLang === 'tr' ? 'en' : 'tr';
        const newLangId = newLang === 'tr' ? 1 : 2;

        // Update language in database and session
        axios.post('/settings/language', {
            language_id: newLangId
        }).then(response => {
            if (response.data.success) {
                showSuccess(response.data.message || '{{ __("common.language_updated") }}');
                // Reload to apply new translations
                setTimeout(() => window.location.reload(), 500);
            }
        }).catch(error => {
            showError(error.response?.data?.message || '{{ __("common.language_update_failed") }}');
        });
    }
</script>
