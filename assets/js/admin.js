jQuery(document).ready(function ($) {
    // Handle reset button clicks
    $('.reset-color').on('click', function () {
        const defaultColor = $(this).data('default');
        const targetId = $(this).data('target');
        const $colorInput = $('#' + targetId);
        const $hexInput = $('input[data-color-input="' + targetId + '"]');

        $colorInput.val(defaultColor);
        $hexInput.val(defaultColor);
    });

    // Sync color picker with hex input
    $('input[type="color"]').on('input', function () {
        const $hexInput = $('input[data-color-input="' + $(this).attr('id') + '"]');
        $hexInput.val($(this).val());
    });

    // Sync hex input with color picker
    $('.color-hex-value').on('input', function () {
        const colorId = $(this).data('color-input');
        $('#' + colorId).val($(this).val());
    });

    // Validate hex input
    $('.color-hex-value').on('blur', function () {
        let value = $(this).val();

        // Add # if missing
        if (value[0] !== '#') {
            value = '#' + value;
        }

        // Validate hex color
        const isValid = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(value);

        if (!isValid) {
            // Reset to the current color picker value
            const colorId = $(this).data('color-input');
            value = $('#' + colorId).val();
        }

        // Update both inputs
        $(this).val(value);
        const colorId = $(this).data('color-input');
        $('#' + colorId).val(value);
    });
});

jQuery(document).ready(function ($) {
    // Font preview and loading functionality
    function loadGoogleFont(font) {
        const link = document.createElement('link');
        link.href = `https://fonts.googleapis.com/css2?family=${font.replace(' ', '+')}:wght@400;700&display=swap`;
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }

    // Initialize all font previews
    $('#heading_font, #body_font').each(function () {
        const font = $(this).val();
        loadGoogleFont(font);
        $(this).css('font-family', `"${font}", sans-serif`);
    });

    // Update font weights when font family changes
    $('#heading_font, #body_font').on('change', function () {
        const font = $(this).val();
        const isHeading = $(this).attr('id') === 'heading_font';
        const weightSelect = $(this).next('select');

        // Store current weight
        const currentWeight = weightSelect.val();

        // Load new font
        loadGoogleFont(font);
        $(this).css('font-family', `"${font}", sans-serif`);

        // Update available weights
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_font_weights',
                font: font,
                nonce: guidedJournalSettings.nonce
            },
            success: function (response) {
                if (response.success) {
                    const weights = response.data;
                    weightSelect.empty();
                    weights.forEach(function (weight) {
                        weightSelect.append(
                            $('<option></option>')
                                .attr('value', weight)
                                .text(weight)
                                .prop('selected', weight === currentWeight)
                        );
                    });
                }
            }
        });
    });

    // Color picker functionality
    $('.color-input-group').each(function () {
        const colorPicker = $(this).find('input[type="color"]');
        const hexInput = $(this).find('.color-hex');
        const resetButton = $(this).find('.reset-color');

        // Sync color picker with hex input
        colorPicker.on('input', function () {
            hexInput.val($(this).val());
        });

        // Validate and sync hex input with color picker
        hexInput.on('input', function () {
            let value = $(this).val();
            if (value.length === 6) value = '#' + value;
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorPicker.val(value);
            }
        });

        // Reset to default color
        resetButton.on('click', function () {
            const defaultColor = $(this).data('default');
            colorPicker.val(defaultColor);
            hexInput.val(defaultColor);
        });
    });

    // Form validation
    $('form').on('submit', function (e) {
        let isValid = true;
        const hexInputs = $('.color-hex');

        hexInputs.each(function () {
            const value = $(this).val();
            if (!/^#[0-9A-F]{6}$/i.test(value)) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please ensure all color values are valid hexadecimal colors (e.g., #FF0000)');
        }
    });

    // Remove error class on input
    $('.color-hex').on('input', function () {
        $(this).removeClass('error');
    });
});