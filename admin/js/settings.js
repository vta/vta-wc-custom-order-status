/**{}
 * @title VTA Custom Order Status - Settings JS
 * @description script for settings page
 */

(function ($) {

    // Order Status Arrangement

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

    // Reset all custom order statuses & settings to default
    $('form#reset-settings-form').submit(function () {
        return confirm('Are you sure you want to reset all "Order Statuses" and plugin settings?');
    });

})(jQuery);
