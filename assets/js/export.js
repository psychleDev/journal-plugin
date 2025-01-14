jQuery(document).ready(function ($) {
    console.log('Export script initialized');
    console.log('Looking for export buttons:', $('.export-entries').length);

    // Handle export button click
    $(document).on('click', '.export-entries', function (e) {
        console.log('Export button clicked');
        e.preventDefault();
        const $button = $(this);
        const originalText = $button.text();

        // Get current protocol
        const protocol = window.location.protocol;
        const ajaxUrl = journalAjax.ajaxurl.replace(/^http:/, protocol);

        // Show loading state
        $button.text('Exporting...').prop('disabled', true);

        // Remove any existing error messages
        $('.journal-notification').remove();

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'export_journal_entries',
                nonce: journalAjax.nonce
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function (response, status, xhr) {
                console.log('Export ajax success:', { status, contentType: xhr.getResponseHeader('content-type') });
                const contentType = xhr.getResponseHeader('content-type');

                // Check if response is JSON (error message)
                if (contentType && contentType.indexOf('application/json') > -1) {
                    console.log('Received JSON response instead of blob');
                    const reader = new FileReader();
                    reader.onload = function () {
                        try {
                            const jsonResponse = JSON.parse(this.result);
                            showError(jsonResponse.data.message || 'Export failed. Please try again.');
                        } catch (e) {
                            console.error('Error parsing JSON response:', e);
                            showError('Export failed. Please try again.');
                        }
                    };
                    reader.readAsText(response);
                    return;
                }

                try {
                    // Handle successful CSV download
                    const blob = new Blob([response], {
                        type: 'text/csv;charset=utf-8;'
                    });

                    // Get filename from header or use default
                    const filename = xhr.getResponseHeader('content-disposition')
                        ? xhr.getResponseHeader('content-disposition').split('filename=')[1].replace(/"/g, '')
                        : 'journal-entries.csv';

                    console.log('Creating download with filename:', filename);

                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    // Show success message
                    showSuccess('Export completed successfully!');
                } catch (error) {
                    console.error('Error creating download:', error);
                    showError('Failed to create download. Please try again.');
                }
            },
            error: function (xhr, status, error) {
                console.error('Export ajax error:', { xhr, status, error });
                let errorMessage = 'Export failed. Please try again.';

                try {
                    if (xhr.responseText) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }

                showError(errorMessage);
            },
            complete: function () {
                console.log('Export ajax complete');
                // Reset button state
                $button.text(originalText).prop('disabled', false);
            }
        });
    });

    // Helper function to show error message
    function showError(message) {
        console.log('Showing error:', message);
        const $error = $('<div class="journal-notification error"></div>')
            .text(message)
            .appendTo('body');

        setTimeout(function () {
            $error.fadeOut(function () {
                $(this).remove();
            });
        }, 5000);
    }

    // Helper function to show success message
    function showSuccess(message) {
        console.log('Showing success:', message);
        const $success = $('<div class="journal-notification success"></div>')
            .text(message)
            .appendTo('body');

        setTimeout(function () {
            $success.fadeOut(function () {
                $(this).remove();
            });
        }, 3000);
    }
});