{{-- Axios Configuration & Interceptors --}}
<script>
    /**
     * Configure Axios for AJAX requests
     * - Add CSRF token automatically
     * - Handle common errors globally
     */

    // Set axios defaults
    axios.defaults.baseURL = '{{ url('/') }}';
    axios.defaults.headers.common['Accept'] = 'application/json';
    axios.defaults.headers.common['Content-Type'] = 'application/json';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
    }

    // Configure NProgress
    NProgress.configure({
        showSpinner: false
    });

    // Add request interceptor
    axios.interceptors.request.use(
        (config) => {
            NProgress.start();
            return config;
        },
        (error) => {
            NProgress.done();
            return Promise.reject(error);
        }
    );

    // Add response interceptor for global error handling
    axios.interceptors.response.use(
        (response) => {
            NProgress.done();
            return response;
        },
        (error) => {
            NProgress.done();
            if (error.response) {
                // Handle 401 Unauthorized - redirect to login
                if (error.response.status === 401) {
                    window.location.href = "{{ route('login') }}";
                    return Promise.reject(error);
                }

                // Handle 419 CSRF Token Mismatch - refresh page
                if (error.response.status === 419) {
                    showError("{{ __('common.session_expired') }}");
                    setTimeout(() => window.location.reload(), 2000);
                    return Promise.reject(error);
                }
            }

            return Promise.reject(error);
        }
    );
</script>
