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