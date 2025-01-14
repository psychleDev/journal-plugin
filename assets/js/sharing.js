jQuery(document).ready(function ($) {
    console.log('Sharing script initialized');

    // Add the popup HTML after the existing button
    $('.share-button-container').append(`
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
                url: journalShare.ajaxurl,  // Changed from journalAjax to journalShare
                type: 'POST',
                data: {
                    action: 'generate_share_token',
                    nonce: journalShare.nonce,  // Changed from journalAjax to journalShare
                    entry_day: currentDay,
                },
                success: function (response) {
                    console.log('AJAX success response:', response);
                    if (response.success) {
                        const shareUrl = `${window.location.origin}/shared-entry/${response.data.token}`;
                        $shareLink.val(shareUrl);
                        showNotification('success', journalShare.i18n.copySuccess);
                    } else {
                        console.error('Server returned error:', response.data?.message);
                        showNotification('error', response.data?.message || journalShare.i18n.generateError);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', { xhr, status, error });
                    console.error('Response Text:', xhr.responseText);
                    const errorMessage = parseAjaxError(xhr, journalShare.i18n.generateError);
                    showNotification('error', errorMessage);
                },
                complete: function () {
                    console.log('AJAX request completed');
                }
            });
        }

        $popup.slideToggle(200);
    });

    // Handle copy button click
    $('.copy-link').on('click', function () {
        const $shareLink = $('.share-link');
        const linkText = $shareLink.val();

        if (!linkText) {
            showNotification('error', 'No share link available to copy.');
            return;
        }

        navigator.clipboard.writeText(linkText)
            .then(() => {
                showNotification('success', journalShare.i18n.copySuccess);
            })
            .catch(() => {
                // Fallback copy mechanism
                $shareLink[0].select();
                try {
                    document.execCommand('copy');
                    showNotification('success', journalShare.i18n.copySuccess);
                } catch (err) {
                    showNotification('error', journalShare.i18n.copyError);
                }
            });
    });

    // Handle email share
    $('.email-share').on('click', function () {
        const shareUrl = $('.share-link').val();
        if (!shareUrl) {
            showNotification('error', 'Please wait for the share link to generate.');
            return;
        }

        const subject = encodeURIComponent(journalShare.i18n.shareSubject);
        const body = encodeURIComponent(`${journalShare.i18n.shareText}\n\n${shareUrl}`);
        window.location.href = `mailto:?subject=${subject}&body=${body}`;
    });

    // Handle Twitter share
    $('.twitter-share').on('click', function () {
        const shareUrl = $('.share-link').val();
        if (!shareUrl) {
            showNotification('error', 'Please wait for the share link to generate.');
            return;
        }

        const text = encodeURIComponent(journalShare.i18n.shareSubject);
        window.open(`https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(shareUrl)}`);
    });

    // Close popup when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.share-button-container').length) {
            $('.share-popup').slideUp(200);
        }
    });

    // Helper functions
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

    function getCurrentDay() {
        const path = window.location.pathname;
        const matches = path.match(/\/journal-prompts\/(\d+)/);
        const day = matches && matches[1] ? parseInt(matches[1]) : 1;
        console.log('Extracted current day from URL:', day);
        return day;
    }

    function showNotification(type, message) {
        console.log('Showing notification:', { type, message });

        const $notification = $(`<div class="journal-notification ${type}"></div>`)
            .text(message)
            .appendTo('body');

        setTimeout(() => $notification.addClass('visible'), 10);
        setTimeout(() => $notification.removeClass('visible').remove(), 3000);
    }
});