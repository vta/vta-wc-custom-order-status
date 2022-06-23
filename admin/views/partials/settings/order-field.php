<?php
/**
 * @title VTA Custom Order Status Settings - Order (Arrangement) Field
 * @author James Pham
 * @description UI for organizing the arrangement.
 */

$order_statuses = wc_get_order_statuses();
?>

<ul id="statuses-sortable">
    <?php foreach ( $order_statuses as $status_key => $status_name ): ?>

        <li class="ui-state-default" id="<?php echo $status_key; ?>"><?php echo $status_name; ?></li>

    <?php endforeach; ?>
</ul>
