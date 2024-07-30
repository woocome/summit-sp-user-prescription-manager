<?php
    $product_category = $args['prescribed_categories'];
    $product = $args['product'];
    $top_up_product = get_field('select_top_up_product', $product->get_id());
    $product_name = sp_get_product_name($product->get_id());
    $active_until_date = !empty($args['active_date']) && strtotime($args['active_date']) !== false 
    ? date("M. d, Y", strtotime($args['active_date'])) 
    : '';
    $panel_card_info = get_field('my_account_card_tagline', 'product_cat_' . $product_category->term_id);
    $custom_redirect = get_term_meta($product_category->term_id, 'buy_now_redirection_url', true);
    $url = $custom_redirect ? get_permalink($custom_redirect) : get_term_link($product_category, 'product_cat');

    $subscription = sp_upm_user_active_treatments()->get_product_subscription($product->get_id());
?>
<div class="active-treatments-wrapper active-treatments-wrapper--<?php echo slugify($product_category->name); ?> active-treatments-wrapper--mens-health">
    <div class="active-treatments-items">
        <h6 class="treatment-heading"><?php echo $product_category->name; ?></h6>
        <div class="medication-wrapper">
            <div class="approved-treatments-wrapper">
                <div class="at-wrapper at-wrapper--with-cta">
                    <div class="at-items">
                        <p class="at-content_heading"><small><strong>Approved Medication</strong></small></p>
                        <p class="at-content_info"><?php echo !empty($panel_card_info) ? $panel_card_info : $product_name; ?></p>
                    </div>
                    <?php if ($args['prescribed_medication']) : ?>
                    <div class="at-items active-treatments-cta-wrapper sp-cp-cta">
                        <?php if ($product->is_type('variable-subscription') && !$subscription) : ?>
                            <?php sp_upm_user_active_treatments()::render_myaccount_panel_button(get_permalink($top_up_product->ID), 'Buy - One Time'); ?>
                            <?php sp_upm_user_active_treatments()::render_myaccount_panel_button(get_permalink($product->get_id()), 'Buy - Subscription'); ?>
                        <?php else : ?>
                            <?php sp_upm_user_active_treatments()::render_myaccount_panel_button(get_permalink($top_up_product->ID), 'Buy - One Time'); ?>
                        <?php endif; ?>

                        <?php sp_upm_get_template_part('content', 'change-medication-link', ['product' => $product]); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="at-active-date">
                    <div class="active-date-wrapper">
                        <p><span>Active Until:</span> <?= !empty($active_until_date) ? $active_until_date : 'N/A'; ?></p>
                        <?php sp_upm_get_template_part('content', 'change-medication-link', ['product' => $product]); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($subscription) : ?>
            <?php
                $change_subscription_url = esc_url(home_url("my-account/view-subscription/".$subscription->get_id()."/"));
                
                $next_payment = $subscription->get_date( 'next_payment' );
                if ($next_payment) {
                    $dateTime = new DateTime($next_payment);
                    $next_payment = $dateTime->format('jS \of F Y');
                }
            ?>
            <div class="medication-wrapper">
                <div class="approved-treatments-wrapper">
                    <div class="at-wrapper at-wrapper--with-cta">
                        <div class="at-items">
                            <p class="at-content_heading"><small><strong>Approved Subscription</strong></small></p>
                            <p class="at-content_info"><?php echo $product->get_name(); ?></p>
                        </div>
                        <div class="at-items active-date-wrapper active-treatments-cta-wrapper sp-cp-cta">
                            <?php sp_upm_user_active_treatments()::render_myaccount_panel_button(get_permalink($product->get_id()), 'Change Subscription'); ?>
                        </div>
                    </div>
                    <div class="at-active-date">
                        <div class="active-date-wrapper">
                            <p><span>Renews Every:</span> <?= $next_payment; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>