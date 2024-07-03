<?php
    global $wp;

    $order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;

    if (! $order_id) return;

    $order = wc_get_order($order_id);

    $is_consultation = false;
    $consultation = false;

    // Get and Loop Over Order Items
    foreach ( $order->get_items() as $item_id => $item ) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();

        $is_consultation = check_if_product_contains_category('consultations', $item->get_product());

        if ($is_consultation) {
            $consultation = $item->get_product();
        }
    }
?>
<?php if ($consultation) : ?>
<div class="sp-content-summary">
	<?php
		$treatment_id = get_field('treatment_category', $consultation->get_id());
		$treatment = get_term_by('id', $treatment_id, 'product_cat');
		$prescreening_form_page = get_field('category_wp_form_url', 'product_cat_' . $treatment_id);

		$prescreening_form_id = get_field('category_wp_form', 'product_cat_' . $treatment_id);
		$booking_form = get_field('consultation_booking_form', 'product_cat_' . $treatment_id);
	?>
	<h4>Proceed to <?php echo $treatment->name; ?> pre-screening-form:</h4>

	<div class="sp-content-summary__actions">
		<a href="<?php echo $prescreening_form_page; ?>" class="sp-content-summary__button">now: accomplish pre-screening form <i aria-hidden="true" class="fas fa-arrow-alt-circle-right"></i></a>

		<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
			<input type="hidden" name="action" value="send_pre_screening_form_to_user">
			<input type="hidden" name="treatment_id" value="<?php echo $treatment_id; ?>">

			<div>
				<input type="submit" value="later: send pre-screening form to email" class="sp-content-summary__button">
			</div>
		</form>
	</div>
</div>
<?php endif; ?>