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
                    if (response.success) {
                        const shareUrl = `${window.location.origin}/shared-entry/${response.data.token}`;
                        $shareLink.val(shareUrl);
                        showNotification('success', 'Share link generated successfully.');
                    } else {
                        showNotification('error', response.data?.message || 'Failed to generate share link.');
                    }
                },
                error: function (xhr) {
                    const errorMessage = parseAjaxError(xhr, 'Failed to generate share link.');
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

        navigator.clipboard.writeText(shareText)
            .then(() => updateCopyButton(true))
            .catch(() => fallbackCopy($shareLink, this));
    });

    // Handle email share
    $('.email-share').on('click', function () {
        const shareUrl = $('.share-link').val();
        if (!shareUrl) {
            showNotification('error', 'Please wait for the share link to generate.');
            return;
        }

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

        const text = encodeURIComponent('Check out my journal entry:');
        window.open(`https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(shareUrl)}`);
    });

    // Close popup when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.share-button-container').length) {
            $('.share-popup').slideUp(200);
        }
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

    // Helper function to get current day from URL
    function getCurrentDay() {
        const path = window.location.pathname;
        const matches = path.match(/\/journal-prompts\/(\d+)/);
        return matches && matches[1] ? parseInt(matches[1]) : 1;
    }

    // Helper function for fallback copy mechanism
    function fallbackCopy($shareLink, button) {
        try {
            $shareLink[0].select();
            document.execCommand('copy');
            updateCopyButton(true);
        } catch (err) {
            updateCopyButton(false);
            showNotification('error', 'Failed to copy to clipboard.');
        }
    }

    // Helper function to update copy button state
    function updateCopyButton(success) {
        const $button = $('.copy-link');
        const originalContent = '<span class="dashicons dashicons-clipboard"></span> Copy';

        if (success) {
            $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
        } else {
            $button.html('<span class="dashicons dashicons-no"></span> Failed');
        }

        setTimeout(() => {
            $button.html(originalContent);
        }, 2000);
    }

    // Helper function to show notifications
    function showNotification(type, message) {
        const $notification = $(`<div class="journal-notification ${type}"></div>`)
            .text(message)
            .appendTo('body');

        setTimeout(() => $notification.addClass('visible'), 10);
        setTimeout(() => $notification.removeClass('visible').remove(), 3000);
    }
});
