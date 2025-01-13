jQuery(document).ready(function ($) {
    // Save entry handler
    $('.save-entry').on('click', function () {
        const currentDay = getCurrentDay();
        let text;

        // Get content from WordPress editor if available, otherwise from textarea
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('journal-entry')) {
            text = tinyMCE.get('journal-entry').getContent();
        } else {
            text = $('#journal-entry').val();
        }

        $.ajax({
            url: journalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_journal_entry',
                nonce: journalAjax.nonce,
                day: currentDay,
                text: text
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);

                    // Mark the corresponding grid item as completed
                    const targetCard = $('.prompt-card[data-day="' + currentDay + '"]');
                    targetCard.addClass('completed');
                } else {
                    alert(response.data || 'Failed to save entry');
                }
            }
        });
    });

    // Navigation handlers
    $('.prev-day, .next-day').on('click', function () {
        if ($(this).prop('disabled')) {
            return;
        }

        const currentDay = getCurrentDay();
        const maxDay = parseInt(journalAjax.maxDay) || 1;
        let targetDay;

        if ($(this).hasClass('prev-day')) {
            targetDay = Math.max(1, currentDay - 1);
        } else {
            targetDay = Math.min(maxDay, currentDay + 1);
        }

        // Format the day number with leading zeros
        const formattedDay = String(targetDay).padStart(3, '0');

        // Navigate to the new URL
        window.location.href = '/journal-prompts/' + formattedDay + '/';
    });

    function getCurrentDay() {
        const path = window.location.pathname;
        const matches = path.match(/\/journal-prompts\/(\d+)/);
        if (matches && matches[1]) {
            return parseInt(matches[1].replace(/^0+/, ''));
        }
        return 1;
    }

    // Initialize WordPress editor if available
    if (typeof tinyMCE !== 'undefined') {
        tinyMCE.on('AddEditor', function (e) {
            // Add auto-save functionality
            let autoSaveTimeout;
            e.editor.on('change', function () {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(function () {
                    $('.save-entry').click();
                }, 60000); // Auto-save after 1 minute of inactivity
            });
        });
    }

    // Handle keyboard shortcuts
    $(document).on('keydown', function (e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
            e.preventDefault();
            $('.save-entry').click();
        }
    });
});