{{-- Authentication Functions --}}
<script>
    /**
     * Handle user logout
     */
    function handleLogout() {
        // Create a form and submit it (to properly handle CSRF)
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('logout') }}";
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken.content;
            form.appendChild(csrfInput);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
</script>
