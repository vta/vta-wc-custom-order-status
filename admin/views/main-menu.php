<?php

?>
<h1>VTA Custom Order Statuses</h1>

<?php
error_log(json_encode(get_post_statuses(), JSON_PRETTY_PRINT));

$order_statuses = wc_get_order_statuses();
//foreach( $order_statuses as $status_key => $status) {
//    error_log("$status_key - $status");
//}

?>
