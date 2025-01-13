jQuery(document).ready(function ($) {
    let lastSaveTime = new Date();
    let startWritingTime = new Date();
    let isDirty = false;
    let writeTimeInterval;
    let currentWordCount = 0;

    // Initialize notification element
    const notification = $('<div class="journal-notification"></div>').appendTo('body');

    // Initialize stats container
    const statsContainer = $('<div class="journal-stats"></div>').insertBefore('.navigation');
    statsContainer.html(`
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Word Count</span>
                <span class="stat-value word-count">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Writing Streak</span>
                <span class="stat-value streak-count">0 days</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Progress</span>
                <span class="stat-value completion-rate">0%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Time Writing</span>
                <span class="stat-value writing-time">0:00</span>
            </div>
        </div>
        <div class="save-status">All changes saved</div>
    `);

    // Initialize progress tracking
    function updateProgress() {
        $.ajax({
            url: journalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_journal_progress',
                nonce: journalAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('.completion-rate').text(response.data.completion_rate + '%');
                    $('.streak-count').text(response.data.streak + ' days');
                }
            }
        });
    }

    // Word count update function
    function updateWordCount() {
        let content;
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('journal-entry')) {
            content = tinyMCE.get('journal-entry').getContent({ format: 'text' });
        } else {
            content = $('#journal-entry').val();
        }
        currentWordCount = content.trim().split(/\s+/).filter(word => word.length > 0).length;
        $('.word-count').text(currentWordCount);
    }

    // Time tracking
    function startTimeTracking() {
        writeTimeInterval = setInterval(function () {
            const elapsed = Math.floor((new Date() - startWritingTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            $('.writing-time').text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
        }, 1000);
    }

    // Show notification
    function showNotification(message, type = 'success') {
        notification
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .fadeIn()
            .delay(3000)
            .fadeOut();
    }

    // Content change handler
    function handleContentChange() {
        if (!isDirty) {
            isDirty = true;
            $('.save-status').text('Unsaved changes').addClass('unsaved');
        }
        updateWordCount();
    }

    // Initialize editor change handlers
    if (typeof tinyMCE !== 'undefined') {
        tinyMCE.on('AddEditor', function (e) {
            e.editor.on('change keyup', handleContentChange);
        });
    }
    $('#journal-entry').on('change keyup', handleContentChange);

    // Start time tracking when user begins writing
    $(document).one('keydown', function () {
        startTimeTracking();
    });

    // Save entry handler
    $('.save-entry').on('click', function () {
        const currentDay = getCurrentDay();
        let text;

        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('journal-entry')) {
            text = tinyMCE.get('journal-entry').getContent();
        } else {
            text = $('#journal-entry').val();
        }

        $.ajax({
            url: journalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_journal_entry',
                nonce: journalAjax.nonce,
                day: currentDay,
                text: text,
                word_count: currentWordCount,
                time_spent: Math.floor((new Date() - startWritingTime) / 1000)
            },
            success: function (response) {
                if (response.success) {
                    showNotification(response.data.message);
                    isDirty = false;
                    $('.save-status').text('All changes saved').removeClass('unsaved');
                    lastSaveTime = new Date();
                    updateProgress();
                } else {
                    showNotification(response.data.message || 'Failed to save entry', 'error');
                }
            },
            error: function () {
                showNotification('Failed to save entry', 'error');
            }
        });
    });

    // Auto-save every 2 minutes if there are changes
    setInterval(function () {
        if (isDirty) {
            $('.save-entry').click();
        }
    }, 120000);

    // Load initial stats
    updateProgress();
    updateWordCount();

    // Navigation handlers (previous code remains the same)
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

        const formattedDay = String(targetDay).padStart(3, '0');
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

    // Handle keyboard shortcuts
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
            e.preventDefault();
            $('.save-entry').click();
        }
    });

    // Warn about unsaved changes when leaving
    $(window).on('beforeunload', function () {
        if (isDirty) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
});