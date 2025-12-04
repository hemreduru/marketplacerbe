<script>
    "use strict";

    /**
     * Marketplace Settings Page Module
     */
    const KTMarketplaceSettings = (function() {

        /**
         * Handle form submission
         */
        const handleFormSubmit = async (e) => {
            e.preventDefault();
            const form = $(e.currentTarget);
            const submitBtn = form.find('.submit-btn');
            const marketplaceId = form.data('marketplace-id');

            // Show loading
            submitBtn.attr('data-kt-indicator', 'on');
            submitBtn.prop('disabled', true);

            const formData = {
                marketplace_id: marketplaceId,
                api_key: form.find('input[name="api_key"]').val(),
                api_secret: form.find('input[name="api_secret"]').val(),
                additional_credentials: {}
            };

            // Collect additional credentials
            form.find('input[name^="additional_credentials"]').each(function() {
                const name = $(this).attr('name');
                const key = name.match(/\[(.*?)\]/)[1];
                formData.additional_credentials[key] = $(this).val();
            });

            try {
                const response = await axios.put('/marketplace-settings', formData);

                if (response.data.success) {
                    showSuccess(response.data.message);
                }
            } catch (error) {
                if (error.response) {
                    if (error.response.data.errors) {
                        const errors = Object.values(error.response.data.errors).flat();
                        showError(errors.join('<br>'));
                    } else if (error.response.data.message) {
                        showError(error.response.data.message);
                    } else {
                        showError(translations.error_occurred);
                    }
                } else {
                    showError(translations.request_failed);
                }
            } finally {
                // Hide loading
                submitBtn.removeAttr('data-kt-indicator');
                submitBtn.prop('disabled', false);
            }
        };

        /**
         * Initialize
         */
        const init = () => {
            $('.marketplace-form').on('submit', handleFormSubmit);
        };

        return {
            init: init
        };
    })();

    // Initialize on DOM ready
    KTUtil.onDOMContentLoaded(() => {
        KTMarketplaceSettings.init();
    });
</script>
