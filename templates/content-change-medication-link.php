<?php
    $_product = $args['product'];
    $product_cat_id = absint(get_post_meta($_product->get_id(), '_yoast_wpseo_primary_product_cat', true));
    $disable_change_medication_button = get_field('disable_change_medication_button', 'product_cat_' . $product_cat_id);
    $change_medication_url = esc_url(home_url("/change-medication-request/?product_id={$_product->get_id()}&variation_id={$_product->get_name()}"));

    if ($disable_change_medication_button) return;
?>
<a href="<?= $change_medication_url; ?>" class="sp-link sp-link--change-medication">*Change Medication - Request</a>
