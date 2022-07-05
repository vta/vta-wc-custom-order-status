/**{}
 * @title VTA Custom Order Status - Settings JS
 * @description script for settings page
 */

(function ($) {

    const sortable               = $('#statuses-sortable');
    const orderStatusArrangement = $('input#order_status_arrangement[type="hidden"]');

    /**
     * Gets the current Order Status arrangement
     * @returns {{order_status_name: *, order_status_id: *}[]}
     */
    function returnStatusNameId() {
        const children = sortable.children('li.vta-order-status').toArray();
        return children.map(function (child) {
            const order_status_id   = child.id;
            const order_status_name = $(child).text().trim();
            return { order_status_id, order_status_name };
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

})(jQuery);
