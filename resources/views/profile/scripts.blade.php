<script>
    "use strict";

    /**
     * Settings Page Module
     */
    const KTSettings = (function() {

        /**
         * Handle profile form submission
         */
        const handleProfileSubmit = async (e) => {
            e.preventDefault();
            const form = $(e.currentTarget);
            const submitBtn = form.find('#profile_submit');

            // Show loading
            submitBtn.attr('data-kt-indicator', 'on');
            submitBtn.prop('disabled', true);

            const formData = {
                name: form.find('input[name="name"]').val(),
                email: form.find('input[name="email"]').val()
            };

            try {
                const response = await axios.put('/settings/profile', formData);

                if (response.data.success) {
                    showSuccess(response.data.message);
                }
            } catch (error) {
                handleError(error);
            } finally {
                // Hide loading
                submitBtn.removeAttr('data-kt-indicator');
                submitBtn.prop('disabled', false);
            }
        };

        /**
         * Handle preferences form submission
         */
        const handlePreferencesSubmit = async (e) => {
            e.preventDefault();
            const form = $(e.currentTarget);
            const submitBtn = form.find('#preferences_submit');

            // Show loading
            submitBtn.attr('data-kt-indicator', 'on');
            submitBtn.prop('disabled', true);

            const formData = {
                preferred_language_id: form.find('select[name="preferred_language_id"]').val(),
                theme: form.find('select[name="theme"]').val(),
                dark_mode: form.find('input[name="dark_mode"]').is(':checked')
            };

            try {
                const response = await axios.put('/settings', formData);

                if (response.data.success) {
                    Swal.fire({
                        text: response.data.message + " " +
                            "{{ __('settings.reload_page_for_changes') }}",
                        icon: "success",
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonText: "{{ __('common.yes') }}",
                        cancelButtonText: "{{ __('common.no') }}",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-active-light"
                        }
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }
            } catch (error) {
                handleError(error);
            } finally {
                // Hide loading
                submitBtn.removeAttr('data-kt-indicator');
                submitBtn.prop('disabled', false);
            }
        };

        /**
         * Handle password form submission
         */
        const handlePasswordSubmit = async (e) => {
            e.preventDefault();
            const form = $(e.currentTarget);
            const submitBtn = form.find('#password_submit');

            // Show loading
            submitBtn.attr('data-kt-indicator', 'on');
            submitBtn.prop('disabled', true);

            const formData = {
                current_password: form.find('input[name="current_password"]').val(),
                new_password: form.find('input[name="new_password"]').val(),
                new_password_confirmation: form.find('input[name="new_password_confirmation"]')
                .val()
            };

            try {
                const response = await axios.put('/settings/password', formData);

                if (response.data.success) {
                    showSuccess(response.data.message);
                    form[0].reset();
                }
            } catch (error) {
                handleError(error);
            } finally {
                // Hide loading
                submitBtn.removeAttr('data-kt-indicator');
                submitBtn.prop('disabled', false);
            }
        };

        /**
         * Handle errors
         */
        const handleError = (error) => {
            if (error.response) {
                if (error.response.data.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    showError(errors.join('<br>'));
                } else if (error.response.data.message) {
                    showError(error.response.data.message);
                } else {
                    showError("{{ __('common.error_occurred') }}");
                }
            } else {
                showError("{{ __('common.request_failed') }}");
            }
        };

        /**
         * Initialize
         */
        const init = () => {
            $('#profile_form').on('submit', handleProfileSubmit);
            $('#preferences_form').on('submit', handlePreferencesSubmit);
            $('#password_form').on('submit', handlePasswordSubmit);
        };

        return {
            init: init
        };
    })();

    // Initialize on DOM ready
    KTUtil.onDOMContentLoaded(() => {
        KTSettings.init();
    });
</script>
