/**{}
 * @title VTA Custom Order Status - Settings JS
 * @description script for settings page
 */

(function ($) {

    // ORDER STATUS ARRANGEMENT UI //

    const sortable               = $('#statuses-sortable');
    const orderStatusArrangement = $('input#order_status_arrangement[type="hidden"]');

    /**
     * Gets the current Order Status arrangement
     * @returns {{order_status_name: *, order_status_id: *}[]}
     */
    function returnStatusNameId() {
        const children = sortable.children('li.vta-order-status').toArray();
        return children.map(function (child) {
            return child.id;
        });
    }

    let orderStatusArr = JSON.stringify(returnStatusNameId());
    orderStatusArrangement.val(orderStatusArr);

    sortable.sortable({
        update: function (event, ui) {
            orderStatusArr = JSON.stringify(returnStatusNameId());
            orderStatusArrangement.val(orderStatusArr);
        }
    });

    // RESET BEHAVIOR //

    // Reset all custom order statuses & settings to default
    const resetDialog  = $("#reset-confirm-dialog");
    const resetForm    = $('form#reset-settings-form');
    const resetConfirm = $('#confirm-value');
    const resetBtn     = $('#reset-settings-btn');

    /**
     * Default Reset Form submit callback. Prevents auto-submission without confirmation dialog.
     * @param e
     * @returns {boolean}
     */
    function defaultSubmit(e) {
        const confirmed = !!resetConfirm.val();
        if (!confirmed) {
            resetDialog.dialog('open');
        }
        return confirmed;
    }

    resetForm.submit(defaultSubmit);

    // reset dialog settings
    resetDialog.dialog({
        appendTo:  'form#reset-settings-form',
        resizable: false,
        draggable: false,
        autoOpen:  false,
        height:    "auto",
        width:     400,
        modal:     true,
        buttons:   {
            "Reset": function () {
                // prevent modal closing while processing reset...
                // disable all buttons on the modal & prevent closing by Esc button
                $('button.ui-button.ui-widget').button('disable');
                $(this).dialog("option", "closeOnEscape", false);

                // add loading spinner to reset button
                $('.ui-dialog-buttonset > button.ui-button.ui-widget:first-child')
                    .button('option', 'label', 'Loading...');

                resetConfirm.val(true); // a bit hacky, but input must be clicked after closing dialog...
                resetBtn.click();
            },
            Cancel:  function () {
                $(this).dialog("close");
            }
        }
    });

})(jQuery);
