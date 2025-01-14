jQuery(document).ready(function ($) {
    // Add export button after the "Back to Grid" link if it doesn't exist
    if ($('.export-entries').length === 0) {
        $('.navigation-top').append(
            '<button class="export-entries contents-toggle">' +
            'Export Entries' +
            '</button>'
        );
    }

    // Handle export button click
    $('.export-entries').on('click', function (e) {
        e.preventDefault();
        const $button = $(this);
        const originalText = $button.text();

        // Show loading state
        $button.text('Exporting...').prop('disabled', true);

        // Remove any existing error messages
        $('.journal-export-error').remove();

        $.ajax({
            url: journalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'export_journal_entries',
                nonce: journalAjax.nonce
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function (response, status, xhr) {
                const contentType = xhr.getResponseHeader('content-type');

                // Check if response is JSON (error message)
                if (contentType.indexOf('application/json') > -1) {
                    const reader = new FileReader();
                    reader.onload = function () {
                        try {
                            const jsonResponse = JSON.parse(this.result);
                            showError(jsonResponse.data.message || 'Export failed. Please try again.');
                        } catch (e) {
                            showError('Export failed. Please try again.');
                        }
                    };
                    reader.readAsText(response);
                    return;
                }

                // Handle successful CSV download
                const blob = new Blob([response], {
                    type: 'text/csv;charset=utf-8;'
                });

                // Get filename from header or use default
                const filename = xhr.getResponseHeader('content-disposition')
                    ? xhr.getResponseHeader('content-disposition').split('filename=')[1].replace(/"/g, '')
                    : 'journal-entries.csv';

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
            },
            error: function (xhr, status, error) {
                console.error('Export error:', { xhr, status, error });
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
                // Reset button state
                $button.text(originalText).prop('disabled', false);
            }
        });
    });

    // Helper function to show error message
    function showError(message) {
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