<?php
    $_product = wc_get_product(absint($args['product']));
    $variations = $_product->get_children();
    $is_valid_for_purchase = true;

    // if (function_exists('sp_upm_validate_product_limit_purchase')) {
    //     $is_valid_for_purchase = sp_upm_validate_product_limit_purchase(get_current_user_id(), $_product->get_id());
    // }

    // if (check_if_cart_contains_product_or_variation($_product->get_id())) {
    //     $is_valid_for_purchase = false;
    // }

    $is_purchased = customer_has_purchased_product(get_current_user_id(), $_product->get_id());
    $in_cart = check_if_cart_contains_product_or_variation($_product->get_id());

    $max_quantity = $is_valid_for_purchase ? absint( get_post_meta( $_product->get_id(), 'maximum_allowed_quantity', true ) ) : 0;
    $min_quantity = !$is_purchased && !$in_cart ? absint( get_post_meta( $_product->get_id(), 'minimum_allowed_quantity', true ) ) : 0;
?>
<div class="nrt-choose nrt-choose--<?= $_product->get_slug(); ?>">
    <h4 class="nrt-heading"><?= $args['heading']; ?><span><?= $args['custom_price']; ?></span></h4>
    <div class="nrt-product-cusstomization-wrapper nrt-product-items-columns-<?= $args['columns']; ?>" data-price="<?= $_product->get_price(); ?> <?php echo $args['required_on_initial'] ? 'starter-kit_quantity-required-initial' : ''; ?>">
        <?php foreach ($variations as $variation) : ?>
            <?php 
                $_variant = wc_get_product($variation);
                $attributes = $_variant->get_attributes();
                $title = array_shift($attributes);
            ?>
            <div class="product-custom-item" data-product-id="<?= $variation; ?>">
                <h5 class="product-title"><?php echo $title; ?></h5>
                <?php echo $_variant->get_image('115x125'); ?>
                <p class="quantity-text"><label>Quantity <span><input type="number" id="quantity" name="quantity" min="<?= $min_quantity; ?>" <?= $max_quantity ? "max='$max_quantity'" : ''; ?> class="sp-sk-quantity-js" value="<?= $min_quantity; ?>"></span></label></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>