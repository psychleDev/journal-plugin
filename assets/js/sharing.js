jQuery(document).ready(function ($) {
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
        const $popup = $('.share-popup');
        const $shareLink = $('.share-link');

        if (!$shareLink.val()) {
            const currentDay = getCurrentDay();
            console.debug('Share button clicked. Current day:', currentDay);

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
                    console.debug('AJAX success response:', response);
                    if (response.success) {
                        const shareUrl = `${window.location.origin}/shared-entry/${response.data.token}`;
                        $shareLink.val(shareUrl);
                        showNotification('success', 'Share link generated successfully.');
                    } else {
                        const errorMessage = response.data?.message || 'Failed to generate share link.';
                        console.error('Server returned error:', errorMessage);
                        showNotification('error', errorMessage);
                    }
                },
                error: function (xhr) {
                    const errorMessage = parseAjaxError(xhr, 'Failed to generate share link.');
                    console.error('AJAX error response:', xhr, errorMessage);
                    showNotification('error', errorMessage);
                },
            });
        }

        $popup.slideToggle(200);
    });

    // Handle copy button
    $('.copy-link').on('click', function () {
        const $shareLink = $('.share-link');
        const shareText = $shareLink.val();

        if (!shareText) {
            showNotification('error', 'No share link available to copy.');
            return;
        }

        console.debug('Attempting to copy share link:', shareText);

        navigator.clipboard.writeText(shareText)
            .then(() => {
                console.debug('Link copied to clipboard.');
                updateCopyButton(true);
            })
            .catch(() => {
                console.error('Clipboard copy failed. Attempting fallback.');
                fallbackCopy($shareLink, this);
            });
    });

    // Helper function to parse AJAX error
    function parseAjaxError(xhr, defaultMessage) {
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

    // Handle email share
    $('.email-share').on('click', function () {
        const shareUrl = $('.share-link').val();
        if (!shareUrl) {
            showNotification('error', 'Please wait for the share link to generate.');
            return;
        }

        console.debug('Preparing email share with link:', shareUrl);

        const subject = encodeURIComponent('Check out my journal entry');
        const body = encodeURIComponent(`I wanted to share this journal entry with you:\n\n${shareUrl}`);
        window.location.href = `mailto:?subject=${subject}&body=${body}`;
    });

    // Handle Twitter share
    $('.twitter-share').on('click', function () {
        const shareUrl = $('.share-link').val();
        if (!shareUrl) {
            showNotification('error', 'Please wait for the share link to generate.');
            return;
        }

        console.debug('Preparing Twitter share with link:', shareUrl);

        const text = encodeURIComponent('Check out my journal entry:');
        window.open(`https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(shareUrl)}`);
    });

    // Close popup when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.share-button-container').length) {
            $('.share-popup').slideUp(200);
        }
    });

    // Helper function to get current day from URL
    function getCurrentDay() {
        const path = window.location.pathname;
        const matches = path.match(/\/journal-prompts\/(\d+)/);
        const day = matches && matches[1] ? parseInt(matches[1]) : 1;
        console.debug('Extracted current day from URL:', day);
        return day;
    }

    // Helper function for fallback copy mechanism
    function fallbackCopy($shareLink, button) {
        try {
            $shareLink[0].select();
            document.execCommand('copy');
            updateCopyButton(true);
            console.debug('Fallback copy to clipboard succeeded.');
        } catch (err) {
            console.error('Fallback copy failed:', err);
            updateCopyButton(false);
            showNotification('error', 'Failed to copy to clipboard.');
        }
    }

    // Helper function to update copy button state
    function updateCopyButton(success) {
        const $button = $('.copy-link');
        const originalContent = '<span class="dashicons dashicons-clipboard"></span> Copy';

        if (success) {
            console.debug('Copy button updated to "Copied!" state.');
            $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
        } else {
            console.debug('Copy button updated to "Failed" state.');
            $button.html('<span class="dashicons dashicons-no"></span> Failed');
        }

        setTimeout(() => {
            $button.html(originalContent);
        }, 2000);
    }

    // Helper function to show notifications
    function showNotification(type, message) {
        console.debug('Showing notification:', type, message);

        const $notification = $(`<div class="journal-notification ${type}"></div>`)
            .text(message)
            .appendTo('body');

        setTimeout(() => $notification.addClass('visible'), 10);
        setTimeout(() => $notification.removeClass('visible').remove(), 3000);
    }
});
