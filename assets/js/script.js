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
            }
        });
    });

    // Navigation handlers
    $('.prev-day').on('click', function () {
        if (!$(this).prop('disabled')) {
            const currentDay = getCurrentDayNumber();
            if (currentDay > 1) {
                navigateToDay(currentDay - 1);
            }
        }
    });

    $('.next-day').on('click', function () {
        if (!$(this).prop('disabled')) {
            const currentDay = getCurrentDayNumber();
            const maxDay = parseInt(journalAjax.maxDay) || 1;

            console.log('Current Day:', currentDay);
            console.log('Max Day:', maxDay);

            if (currentDay < maxDay) {
                navigateToDay(currentDay + 1);
            }
        }
    });

    function getCurrentDayNumber() {
        // Get the current URL path
        const path = window.location.pathname;
        console.log('Current path:', path);

        // Try to extract the day number
        const segments = path.split('/').filter(Boolean);
        const lastSegment = segments[segments.length - 1];
        const dayNumber = parseInt(lastSegment);

        console.log('Parsed day number:', dayNumber);
        return dayNumber || 1;
    }

    function navigateToDay(day) {
        // Get the base URL by removing the last segment
        const pathSegments = window.location.pathname.split('/').filter(Boolean);
        pathSegments.pop(); // Remove the last segment (current day)
        const basePath = '/' + pathSegments.join('/');

        console.log('Navigating to day:', day);
        console.log('Base path:', basePath);

        // Construct the new URL
        window.location.href = `${basePath}/${day}/`;
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

                        $list.append(`
                            <div class="entry-item">
                                <span>Day ${entry.day_number} - ${formattedDate}</span>
                                <span class="entry-status">Completed</span>
                                <a href="?day=${entry.day_number}">View</a>
                            </div>
                        `);
                    });
                    $list.addClass('active');
                }
            }
        });
    });
});