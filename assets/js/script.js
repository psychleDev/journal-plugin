jQuery(document).ready(function ($) {
    // Save entry handler
    $('.save-entry').on('click', function () {
        const currentPath = window.location.pathname;
        const day = parseInt(currentPath.split('/').filter(Boolean).pop()) || 1;
        const text = $('#journal-entry').val();

        $.ajax({
            url: journalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_journal_entry',
                nonce: journalAjax.nonce,
                day: day,
                text: text
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message || 'Failed to save entry');
                }
            },
            error: function () {
                alert('Error saving entry. Please try again.');
            }
        });
    });

    // Navigation handlers
    $('.prev-day, .next-day').on('click', function () {
        if ($(this).prop('disabled')) {
            return;
        }

        const currentPath = window.location.pathname;
        const matches = currentPath.match(/\/journal-prompts\/(\d+)/);
        if (!matches) {
            console.error('Could not determine current day number');
            return;
        }

        const currentDay = parseInt(matches[1]);
        const maxDay = parseInt(journalAjax.maxDay) || 1;
        let nextDay;

        if ($(this).hasClass('prev-day')) {
            if (currentDay <= 1) return;
            nextDay = currentDay - 1;
        } else {
            if (currentDay >= maxDay) return;
            nextDay = currentDay + 1;
        }

        // Construct the new URL based on WordPress permalink structure
        window.location.href = `/journal-prompts/${nextDay}/`;
    });

    // Entries list toggle
    $('.list-toggle').on('click', function () {
        const $list = $('.entries-list');
        if ($list.hasClass('active')) {
            $list.removeClass('active');
            return;
        }

        $.ajax({
            url: journalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_journal_entries',
                nonce: journalAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $list.empty();
                    response.data.entries.forEach(entry => {
                        const date = new Date(entry.created_at);
                        const formattedDate = date.toLocaleDateString();

                        $list.append(`
                            <div class="entry-item">
                                <span>Day ${entry.day_number} - ${formattedDate}</span>
                                <span class="entry-status">Completed</span>
                                <a href="?day=${entry.day_number}">View</a>
                            </div>
                        `);
                    });
                    $list.addClass('active');
                } else {
                    alert(response.data.message || 'Error loading entries');
                }
            },
            error: function () {
                alert('Error loading entries. Please try again.');
            }
        });
    });
});