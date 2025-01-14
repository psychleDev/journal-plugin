jQuery(document).ready(function ($) {
    // Add export button after the "Back to Grid" link
    $('.navigation-top').append(
        '<button class="export-entries contents-toggle">' +
        'Export Entries' +
        '</button>'
    );

    // Handle export button click
    $('.export-entries').on('click', function () {
        const $button = $(this);
        const originalText = $button.text();
        const $notification = $('<div class="journal-notification"></div>').appendTo('body');

        $button.text('Exporting...').prop('disabled', true);

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
                // Get filename from Content-Disposition header
                let filename = 'journal-entries.csv';
                const disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('filename') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }

                // Create blob and download
                const blob = new Blob([response], {
                    type: 'text/csv;charset=utf-8;'
                });

                // Handle IE11 and Edge
                if (navigator.msSaveBlob) {
                    navigator.msSaveBlob(blob, filename);
                    return;
                }

                // Create download link
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

                // Show success notification
                showNotification($notification, 'Export completed successfully!', 'success');
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Failed to export entries. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                } catch (e) {
                    console.error('Export error:', error);
                }
                showNotification($notification, errorMessage, 'error');
            },
            complete: function () {
                $button.text(originalText).prop('disabled', false);
            }
        });
    });

    // Helper function to show notifications
    function showNotification($element, message, type) {
        $element
            .removeClass('success error')
            .addClass(type)
            .addClass('visible')
            .text(message);

        setTimeout(function () {
            $element.removeClass('visible');
            setTimeout(function () {
                $element.remove();
            }, 300);
        }, 3000);
    }
});