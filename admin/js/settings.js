/**
 * @title VTA Custom Order Status - Settings JS
 * @description script for settings page
 */

function returnOrder() {

}

(function ($) {

    const sortable = $('#statuses-sortable');
    const orderStatusArr = () => {
        const children = sortable.children('li')
        return children.map(function () {
            return this.id;
        });
    }

    sortable.sortable({
        // update: function (event, ui) {
        //     // console.log('change', event, ui);
        //     const children = sortable.children('li')
        // }
    });

})(jQuery);
