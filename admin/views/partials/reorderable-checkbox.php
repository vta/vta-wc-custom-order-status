<?php
global $post_id;
$reorderable = get_post_meta($post_id, 'vta_cos_is_reorderable', true);
?>
<label for="reorderable-checkbox">Is Reordable? </label>
<input type="checkbox"
       id="reorderable-checkbox"
    <?php echo $reorderable ? 'checked' : '' ?>
>

<script>
    (function ($) {

        const reordeableCheckbox = $('#reorderable-checkbox');

        reordeableCheckbox.change(function () {

        });

    })(jQuery);
</script>
