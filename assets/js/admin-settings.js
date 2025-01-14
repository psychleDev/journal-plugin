jQuery(document).ready(function ($) {
    // Cache selectors
    const $preview = $('.style-preview');
    const $previewCard = $('.preview-card');
    const $previewButton = $('.preview-button');
    const $previewProgress = $('.preview-progress');
    const $previewProgressBar = $('.preview-progress-bar');

    // Helper function to load Google Fonts
    function loadGoogleFont(font) {
        const link = document.createElement('link');
        const fontName = font.replace(' ', '+');
        link.href = `https://fonts.googleapis.com/css2?family=${fontName}:wght@400;500;600;700&display=swap`;
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }

    // Handle typography changes
    function updateTypography() {
        const headingFont = $('select[name="guided_journal_typography[heading_font]"]').val();
        const bodyFont = $('select[name="guided_journal_typography[body_font]"]').val();
        const headingWeight = $('select[name="guided_journal_typography[heading_weight]"]').val();
        const bodyWeight = $('select[name="guided_journal_typography[body_weight]"]').val();
        const headingSize = $('input[name="guided_journal_typography[heading_size]"]').val();
        const bodySize = $('input[name="guided_journal_typography[body_size]"]').val();
        const lineHeight = $('input[name="guided_journal_typography[line_height]"]').val();

        // Load fonts if needed
        loadGoogleFont(headingFont);
        if (bodyFont !== headingFont) {
            loadGoogleFont(bodyFont);
        }

        // Apply typography styles
        $previewCard.find('h3').css({
            'font-family': `"${headingFont}", sans-serif`,
            'font-weight': headingWeight,
            'font-size': headingSize,
            'line-height': lineHeight
        });

        $previewCard.find('p').css({
            'font-family': `"${bodyFont}", sans-serif`,
            'font-weight': bodyWeight,
            'font-size': bodySize,
            'line-height': lineHeight
        });
    }

    // Handle color changes
    function updateColors() {
        $preview.css('background-color', $('input[name="guided_journal_colors[background]"]').val());
        $previewCard.css({
            'background-color': $('input[name="guided_journal_colors[card_background]"]').val(),
            'color': $('input[name="guided_journal_colors[text]"]').val()
        });
        $previewButton.css({
            'background-color': $('input[name="guided_journal_colors[button_background]"]').val(),
            'color': $('input[name="guided_journal_colors[button_text]"]').val()
        });
        $previewProgress.css('background-color', $('input[name="guided_journal_colors[progress_bar_background]"]').val());
        $previewProgressBar.css('background-color', $('input[name="guided_journal_colors[progress_bar_fill]"]').val());
    }

    // Handle spacing and border changes
    function updateLayout() {
        $previewCard.css({
            'padding': $('input[name="guided_journal_spacing[card_padding]"]').val(),
            'border-radius': $('input[name="guided_journal_borders[card_radius]"]').val()
        });
        $previewButton.css('border-radius', $('input[name="guided_journal_borders[button_radius]"]').val());
        $previewProgress.css('border-radius', $('input[name="guided_journal_borders[progress_radius]"]').val());
        $previewProgressBar.css('border-radius', $('input[name="guided_journal_borders[progress_radius]"]').val());
    }

    // Update all preview elements
    function updatePreview() {
        updateTypography();
        updateColors();
        updateLayout();
    }

    // Handle font weight options update
    function updateFontWeightOptions($select, fontFamily) {
        const weights = googleFonts[fontFamily];
        const currentWeight = $select.val();

        $select.empty();
        weights.forEach(weight => {
            const $option = $('<option></option>')
                .val(weight)
                .text(weight)
                .prop('selected', weight === currentWeight);
            $select.append($option);
        });
    }

    // Event handlers
    $('select[name="guided_journal_typography[heading_font]"]').on('change', function () {
        const fontFamily = $(this).val();
        updateFontWeightOptions($('select[name="guided_journal_typography[heading_weight]"]'), fontFamily);
        updatePreview();
    });

    $('select[name="guided_journal_typography[body_font]"]').on('change', function () {
        const fontFamily = $(this).val();
        updateFontWeightOptions($('select[name="guided_journal_typography[body_weight]"]'), fontFamily);
        updatePreview();
    });

    // Handle color picker changes
    $('.color-input-group').each(function () {
        const $colorPicker = $(this).find('input[type="color"]');
        const $hexInput = $(this).find('.color-hex-value');
        const $resetButton = $(this).find('.reset-color');

        $colorPicker.on('input', function () {
            $hexInput.val($(this).val());
            updatePreview();
        });

        $hexInput.on('input', function () {
            let value = $(this).val();
            if (value.length === 6 && !value.startsWith('#')) {
                value = '#' + value;
            }
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                $colorPicker.val(value);
                updatePreview();
            }
        });

        $resetButton.on('click', function () {
            const defaultColor = $(this).data('default');
            $colorPicker.val(defaultColor);
            $hexInput.val(defaultColor);
            updatePreview();
        });
    });

    // Handle spacing and border inputs
    $('input[name^="guided_journal_spacing"], input[name^="guided_journal_borders"]').on('input', updateLayout);

    // Initialize preview
    updatePreview();
});