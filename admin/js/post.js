(function ($) {

    // COLOR PICKER //
    const colorPicker = $('#color-picker');
    const colorVal = $('#color-val');
    const prevColorVal = colorVal.text();
    const colorReset = $('#color-reset');

    colorPicker.change(function () {
        colorVal.text(this.value);
    });

    colorReset.click(function (event) {
        console.log(prevColorVal);
        event.preventDefault();
        colorPicker.val(prevColorVal);
        colorVal.text(prevColorVal.toUpperCase());
    });

    // Reorderable checkbox
    const reordeableCheckbox = $('#reorderable-checkbox');
    reordeableCheckbox.change(function () {

    });

})(jQuery);
