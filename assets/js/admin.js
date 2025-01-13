jQuery(document).ready(function ($) {
    // Font preview and loading functionality
    function loadGoogleFont(font) {
        const link = document.createElement('link');
        link.href = `https://fonts.googleapis.com/css2?family=${font.replace(' ', '+')}:wght@400;700&display=swap`;
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }

    // Initialize font previews
    $('#heading_font, #body_font').each(function () {
        const font = $(this).val();
        loadGoogleFont(font);
        $(this).css('font-family', `"${font}", sans-serif`);
    });

    // Handle font changes
    $('#heading_font, #body_font').on('change', function () {
        const font = $(this).val();
        loadGoogleFont(font);
        $(this).css('font-family', `"${font}", sans-serif`);
    });

    // Color picker functionality
    $('.color-input-group').each(function () {
        const colorPicker = $(this).find('input[type="color"]');
        const hexInput = $(this).find('.color-hex-value');
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

    // Handle hex input validation on blur
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

    // Form validation
    $('form').on('submit', function (e) {
        let isValid = true;
        const hexInputs = $('.color-hex-value');

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
    $('.color-hex-value').on('input', function () {
        $(this).removeClass('error');
    });
});