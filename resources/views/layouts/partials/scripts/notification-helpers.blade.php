{{-- Notification Helper Functions --}}
<script>
    /**
     * Show success notification using Toastr
     * @param {string} message - Success message to display
     * @param {string} title - Optional title (default: 'Success')
     */
    function showSuccess(message, title = "{{ __('common.success') }}") {
        toastr.success(message, title);
    }

    /**
     * Show error notification using Toastr
     * @param {string} message - Error message to display
     * @param {string} title - Optional title (default: 'Error')
     */
    function showError(message, title = "{{ __('common.error') }}") {
        toastr.error(message, title);
    }

    /**
     * Show info notification using Toastr
     * @param {string} message - Info message to display
     * @param {string} title - Optional title (default: 'Info')
     */
    function showInfo(message, title = "{{ __('common.info') }}") {
        toastr.info(message, title);
    }

    /**
     * Show warning notification using Toastr
     * @param {string} message - Warning message to display
     * @param {string} title - Optional title (default: 'Warning')
     */
    function showWarning(message, title = "{{ __('common.warning') }}") {
        toastr.warning(message, title);
    }

    /**
     * Show confirmation dialog using SweetAlert2
     * @param {string} message - Confirmation message
     * @param {string} title - Dialog title (default: 'Are you sure?')
     * @param {function} onConfirm - Callback function on confirm
     * @param {function} onCancel - Optional callback on cancel
     */
    function showConfirm(message, title = "{{ __('common.are_you_sure') }}", onConfirm, onCancel = null) {
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "{{ __('common.yes') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            buttonsStyling: false,
            customClass: {
                confirmButton: "btn btn-primary",
                cancelButton: "btn btn-light"
            }
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm();
            } else if (result.isDismissed && typeof onCancel === 'function') {
                onCancel();
            }
        });
    }
</script>
