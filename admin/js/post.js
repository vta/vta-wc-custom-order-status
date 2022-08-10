/**
 * Converts string to kebab case.
 * @param {string} str
 * @returns {string}
 */
function kebabCase(str) {
    return str.toLowerCase()
        .trim()
        .replace(/\s/, '-');
}

// MAIN //
(function ($) {

    // COLOR PICKER //
    const colorPicker  = $('#color-picker');
    const colorVal     = $('#color-val');
    const prevColorVal = colorVal.text();
    const colorReset   = $('#color-reset');

    colorPicker.change(function () {
        colorVal.text(this.value);
    });

    colorReset.click(function (event) {
        event.preventDefault();
        colorPicker.val(prevColorVal);
        colorVal.text(prevColorVal.toUpperCase());
    });

    // Submit (handle post name if not available)
    const postNameInput = $('input[name="order_status_id"]');
    const postName      = $('input#post_name');
    const postTitle     = $('input#title');
    const form          = $('form#post');

    form.submit(function () {
        let postNameVal = kebabCase(postNameInput.val());

        if (!postNameVal) {
            const postTitleVal = postTitle.val();
            postNameVal        = kebabCase(postTitleVal);
        }
        postName.val(postNameVal);

        return true;
    });

    // REMOVE COLLAPSABLE POSTBOX BEHAVIOR
    $('.postbox .hndle').unbind();
    $('.postbox .handlediv').remove();
    $('.postbox').removeClass('closed');

})(jQuery);
