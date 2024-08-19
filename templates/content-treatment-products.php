<?php
    $treatment_id = $args['treatment_id'];
    // Query WooCommerce for products in the category
    $product_ids = get_objects_in_term( $treatment_id, 'product_cat' );

?>
<select name="prescribe_product" id="prescribed_product_<?php echo $args['entry_id']; ?>" class="prescribe-product prescription-field regular-text" required>
    <option value="">Select a medication</option>

    <?php if ($product_ids) : ?>
        <?php foreach ($product_ids as $product_id) : ?>
            <?php
                $product = wc_get_product( $product_id );
                if (has_term('weight-loss', 'product_cat', $product_id) || ($product && $product->is_type(['subscription', 'variable-subscription']))) :
            ?>
            <option value="<?php echo $product->get_id(); ?>"><?php echo $product->get_name(); ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</select>