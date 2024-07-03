<?php
    $_product = wc_get_product(absint($args['product']));
    $variations = $_product->get_children();
	$max_quantity = absint( get_post_meta( $_product->get_id(), 'maximum_allowed_quantity', true ) );
?>
<div class="nrt-choose nrt-choose--<?= $_product->get_slug(); ?>">
    <h4 class="nrt-heading"><?= $args['heading']; ?><span><?= $args['custom_price']; ?></span></h4>
    <div class="nrt-product-cusstomization-wrapper nrt-product-items-columns-<?= $args['columns']; ?>" data-price="<?= $_product->get_price(); ?>">
        <?php foreach ($variations as $variation) : ?>
            <?php 
                $_variant = wc_get_product($variation);
                $attributes = $_variant->get_attributes();
                $title = array_shift($attributes);

            ?>
            <div class="product-custom-item" data-product-id="<?= $variation; ?>">
                <h5 class="product-title"><?php echo $title; ?></h5>
                <?php echo $_variant->get_image('115x125'); ?>
                <p class="quantity-text"><label>Quantity <span><input type="number" id="quantity" name="quantity" min="0" <?= $max_quantity ? "max='$max_quantity'" : ''; ?> class="sp-sk-quantity-js" value="0"></span></label></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>