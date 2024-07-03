<div class="nrt-wrapper">
    <?php sp_upm_get_template_part('content', 'loading-indicator'); ?>
    <div class="starter-kit-items" data-product-category-id="<?php echo get_field('assigned_product_category'); ?>">
        <?php echo sp_upm_starter_kit()::starter_kit_items(); ?>
    </div>
</div>
<?php sp_upm_get_template_part('/modals/content', 'starter-kit-modal'); ?>
