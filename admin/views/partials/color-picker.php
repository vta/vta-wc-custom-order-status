<?php
// TODO - put JS in script file
global $post_id;
$color   = get_post_meta($post_id, 'vta_cos_color', true);
?>
<label for="color-picker">Color</label>
<input type="color"
       id="color-picker"
       title="Custom Order Status Color Picker"
       value="<?php echo $color; ?>"
       name="meta-cos-color"
>
<strong id="color-val"><?php echo $color; ?></strong>
<button id="color-reset" class="button-small button-link-delete">Reset</button>

<script>
    (function ($) {

        const colorPicker = $('#color-picker');
        const colorVal = $('#color-val');
        const colorReset = $('#color-reset');

        colorPicker.change(function () {
            colorVal.text(this.value);
        });

        colorReset.click(function (event) {
            event.preventDefault();
            colorPicker.val("<?php echo $color; ?>");
            colorVal.text("<?php echo $color; ?>");
        });
    })(jQuery);
</script>
