<?php
    $product_category = $args['prescribed_categories'];
    $active_until_date = !empty($args['active_date']) && strtotime($args['active_date']) !== false 
    ? date("M. d, Y", strtotime($args['active_date'])) 
    : '';
    $mc_prescriptions = get_field('mc_prescriptions', 'user_' . get_current_user_id());
    $panel_card_info = get_field('my_account_card_tagline', 'product_cat_' . $product_category->term_id);
    $custom_redirect = get_term_meta($product_category->term_id, 'buy_now_redirection_url', true);
    $url = $custom_redirect ? get_permalink($custom_redirect) : get_term_link($product_category, 'product_cat');
?>
<div class="active-treatments-wrapper active-treatments-wrapper--<?php echo slugify($product_category->name); ?>">
    <div class="active-treatments-items">
        <h6 class="treatment-heading"><?php echo $product_category->name; ?></h6>
        <div class="medication-wrapper">
            <div class="approved-treatments-wrapper">
                <div class="at-wrapper at-wrapper--with-cta">
                    <div class="at-items">
                        <p class="at-content_heading"><small><strong>Approved Medication</strong></small></p>
                        <p class="at-content_info"><?php echo !empty($panel_card_info) ? $panel_card_info : "Prescribed - {$product_category->name}"; ?></p>
                    </div>
                    <?php if ($args['prescribed_medication']) : ?>
                    <div class="at-items active-date-wrapper active-treatments-cta-wrapper sp-cp-cta">
                        <a href="<?= esc_url($url); ?>" class="no-lightbox sp-active-treatments-button sp-active-treatments-button--change-medication sp-second-button">
                            <div class="sp-active-treatment-icon"><span class="sp-active-treatment-label">Buy Now</span></div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="at-active-date">
                    <div class="active-date-wrapper">
                        <p><span>Active Until:</span> <?= !empty($active_until_date) ? $active_until_date : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
            <?php if (! empty($mc_prescriptions) && is_array($mc_prescriptions)) : ?>
            <div class="medication-details">
                <div class="medication-table">
                    <div class="product-medication-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Repeats</th>
                                    <th>Expiration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mc_prescriptions as $prescription) : ?>
                                    <?php
                                        $_medication_id = absint($prescription['prescribed_medication']);
                                    ?>
                                <tr>
                                    <td><?= get_field('product', $_medication_id) ? get_the_title(get_field('product', $_medication_id)) : get_the_title($_medication_id); ?></td>
                                    <td style="text-align: center;"><?= $prescription['repeats_count']; ?></td>
                                    <td style="text-align: center;"><?= $prescription['expiration_date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>