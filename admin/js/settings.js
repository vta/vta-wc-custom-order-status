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
    const resetDialog = $("#reset-confirm-dialog");
    const resetForm   = $('form#reset-settings-form');

    function defaultSubmit(e) {
        resetDialog.dialog('open');
        e.preventDefault();
        return false;
    }

    // reset dialog settings
    resetDialog.dialog({
        resizable: false,
        draggable: false,
        autoOpen:  false,
        height:    "auto",
        width:     400,
        modal:     true,
        buttons:   {
            "Reset": function () {
                resetForm.unbind('submit', defaultSubmit).submit();
                $(this).dialog("close");
            },
            Cancel:  function () {
                $(this).dialog("close");
            }
        },
    });

    // prevent default behavior
    resetForm.submit(defaultSubmit);

})(jQuery);
