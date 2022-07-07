(function ($) {

    // COLOR PICKER //
    const colorPicker = $('#color-picker');
    const colorVal = $('#color-val');
    const colorReset = $('#color-reset');

    colorPicker.change(function () {
        colorVal.text(this.value);
    });

    colorReset.click(function (event) {
        event.preventDefault();
        colorPicker.val("<?php echo $color; ?>");
        colorVal.text("<?php echo $color; ?>");
    });

    // Reorderable checkbox
    const reordeableCheckbox = $('#reorderable-checkbox');
    reordeableCheckbox.change(function () {

    });

})(jQuery);
