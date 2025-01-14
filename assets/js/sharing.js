jQuery(document).ready(function ($) {
    console.log('Sharing script initialized');

    // Initialize share button in the container
    $('.share-button-container').html(`
        <button class="share-entry contents-toggle">
            <span class="dashicons dashicons-share"></span>
            Share Entry
        </button>
        <div class="share-popup" style="display: none;">
            <div class="share-content">
                <h3>Share Entry</h3>
                <div class="share-link-container">
                    <input type="text" class="share-link" readonly>
                    <button class="copy-link contents-toggle">
                        <span class="dashicons dashicons-clipboard"></span>
                        Copy
                    </button>
                </div>
                <div class="share-options">
                    <button class="email-share contents-toggle">
                        <span class="dashicons dashicons-email"></span>
                        Email
                    </button>
                    <button class="twitter-share contents-toggle">
                        <span class="dashicons dashicons-twitter"></span>
                        Twitter
                    </button>
                </div>
                <div class="share-info">
                    Link expires in 24 hours and can be viewed up to 3 times
                </div>
            </div>
        </div>
    `);

    // Handle share button click
    $('.share-entry').on('click', function (e) {
        e.preventDefault();
        console.log('Share button clicked');

        const $popup = $('.share-popup');
        const $shareLink = $('.share-link');

        if (!$shareLink.val()) {
            const currentDay = getCurrentDay();
            console.log('Generating share token for day:', currentDay);

            // Generate share token
            $.ajax({
                url: journalAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_share_token',
                    nonce: journalAjax.nonce,
                    entry_day: currentDay,
                },
                success: function (response) {
                    console.log('AJAX success response:', response);
                    if (response.success) {
                        const shareUrl = `${window.location.origin}/shared-entry/${response.data.token}`;
                        $shareLink.val(shareUrl);
                        showNotification('success', 'Share link generated successfully.');
                    } else {
                        console.error('Server returned error:', response.data?.message);
                        showNotification('error', response.data?.message || 'Failed to generate share link.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', { xhr, status, error });
                    console.error('Response Text:', xhr.responseText);
                    const errorMessage = parseAjaxError(xhr, 'Failed to generate share link.');
                    showNotification('error', errorMessage);
                },
                complete: function () {
                    console.log('AJAX request completed');
                }
            });
        }

        $popup.slideToggle(200);
    });

    // Helper function to parse AJAX error
    function parseAjaxError(xhr, defaultMessage) {
        console.log('Parsing AJAX error:', xhr);
        try {
            if (xhr.responseText) {
                const response = JSON.parse(xhr.responseText);
                return response.data?.message || defaultMessage;
            }
        } catch (err) {
            console.error('Error parsing AJAX error response:', err);
        }
        return defaultMessage;
    }

    // Helper function to get current day from URL
    function getCurrentDay() {
        const path = window.location.pathname;
        const matches = path.match(/\/journal-prompts\/(\d+)/);
        const day = matches && matches[1] ? parseInt(matches[1]) : 1;
        console.log('Extracted current day from URL:', day);
        return day;
    }

    // Helper function to show notifications
    function showNotification(type, message) {
        console.log('Showing notification:', { type, message });

        const $notification = $(`<div class="journal-notification ${type}"></div>`)
            .text(message)
            .appendTo('body');

        setTimeout(() => $notification.addClass('visible'), 10);
        setTimeout(() => $notification.removeClass('visible').remove(), 3000);
    }

    // Rest of the sharing functionality...
});