jQuery(document).ready(function($) {
    // Save entry handler
    $('.save-entry').on('click', function() {
        const day = new URLSearchParams(window.location.search).get('day') || 1;
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
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Failed to save entry');
                }
            }
        });
    });
    
    // Navigation handlers
    $('.prev-day').on('click', function() {
        if (!$(this).prop('disabled')) {
            const day = parseInt(new URLSearchParams(window.location.search).get('day')) || 1;
            if (day > 1) {
                window.location.href = `?day=${day - 1}`;
            }
        }
    });
    
    $('.next-day').on('click', function() {
        if (!$(this).prop('disabled')) {
            const day = parseInt(new URLSearchParams(window.location.search).get('day')) || 1;
            if (day < 30) {
                window.location.href = `?day=${day + 1}`;
            }
        }
    });
    
    // Entries list toggle
    $('.list-toggle').on('click', function() {
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
            success: function(response) {
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
    
    // Handle Circle SSO token from URL
    const urlParams = new URLSearchParams(window.location.search);
    const circleToken = urlParams.get('circle_token');
    
    if (circleToken) {
        // Remove token from URL
        const newUrl = window.location.href.replace(/[?&]circle_token=[^&#]*/g, '');
        window.history.replaceState({}, document.title, newUrl);
        
        // Verify token with server
        $.ajax({
            url: journalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'verify_circle_token',
                token: circleToken,
                nonce: journalAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                }
            }
        });
    }
});
