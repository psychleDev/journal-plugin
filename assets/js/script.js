jQuery(document).ready(function ($) {
    // Save entry handler
    $('.save-entry').on('click', function () {
        const currentDay = getCurrentDay();
        const text = $('#journal-entry').val();

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
                } else {
                    alert(response.data.message || 'Failed to save entry');
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
                        const formattedDay = String(entry.day_number).padStart(3, '0');

                        $list.append(`
                            <div class="entry-item">
                                <span>Day ${entry.day_number} - ${formattedDate}</span>
                                <span class="entry-status">Completed</span>
                                <a href="/journal-prompts/${formattedDay}/">View</a>
                            </div>
                        `);
                    });
                    $list.addClass('active');
                }
            }
        });
    });
});