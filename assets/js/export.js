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
                // Create download link
                const blob = new Blob([response], { type: 'text/csv' });
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');

                // Get filename from header if present, otherwise use default
                const filename = xhr.getResponseHeader('Content-Disposition')?.split('filename=')[1] || 'journal-entries.csv';

                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(downloadUrl);
                document.body.removeChild(a);
            },
            error: function () {
                alert('Failed to export entries. Please try again.');
            },
            complete: function () {
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
});