jQuery(document).ready(function($) {
    // Save entry handler
    $('.save-entry').on('click', function() {
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
            // Get the current path and extract the last segment (the number)
            const currentPath = window.location.pathname;
            const day = parseInt(currentPath.split('/').filter(Boolean).pop()) || 1;
            if (day > 1) {
                // Navigate to the previous number in the path
                window.location.href = `../${day - 1}`;
            }
        }
    });
    
    $('.next-day').on('click', function() {
        if (!$(this).prop('disabled')) {
            const currentPath = window.location.pathname;
            const day = parseInt(currentPath.split('/').filter(Boolean).pop()) || 1;
            if (day < 30) {
                // Navigate to the next number in the path
                window.location.href = `../${day + 1}`;
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
    
   
});
