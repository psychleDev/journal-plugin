jQuery(document).ready(function ($) {
    // Add share button to navigation
    $('.navigation').append(`
        <div class="share-button-container">
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
        </div>
    `);

    // Handle share button click
    $('.share-entry').on('click', function (e) {
        e.preventDefault();
        const $popup = $('.share-popup');
        const $shareLink = $('.share-link');

        if (!$shareLink.val()) {
            // Generate share token
            $.ajax({
                url: journalAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_share_token',
                    nonce: journalAjax.nonce,
                    entry_day: getCurrentDay()
                },
                success: function (response) {
                    if (response.success) {
                        const shareUrl = window.location.origin + '/shared-entry/' + response.data.token;
                        $shareLink.val(shareUrl);
                    } else {
                        alert('Failed to generate share link');
                    }
                }
            });
        }

        $popup.slideToggle(200);
    });

    // Handle copy button
    $('.copy-link').on('click', function () {
        const $shareLink = $('.share-link');
        $shareLink.select();
        document.execCommand('copy');

        const $button = $(this);
        $button.text('Copied!');
        setTimeout(() => {
            $button.html('<span class="dashicons dashicons-clipboard"></span> Copy');
        }, 2000);
    });

    // Handle email share
    $('.email-share').on('click', function () {
        const shareUrl = $('.share-link').val();
        const subject = encodeURIComponent('Check out my journal entry');
        const body = encodeURIComponent(`I wanted to share this journal entry with you:\n\n${shareUrl}`);
        window.location.href = `mailto:?subject=${subject}&body=${body}`;
    });

    // Handle Twitter share
    $('.twitter-share').on('click', function () {
        const shareUrl = $('.share-link').val();
        const text = encodeURIComponent('Check out my journal entry:');
        window.open(`https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(shareUrl)}`);
    });

    // Close popup when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.share-button-container').length) {
            $('.share-popup').slideUp(200);
        }
    });
});