<div class="sp-upm-modal" id="sp-upm-modal-<?php echo $args['modal_id']; ?>">
    <div class="sp-upm-modal-overlay"></div>

    <div class="sp-upm-modal-container">
        <div class="sp-upm-banner-message">
            <div class="sp-upm-banner-message-icon">
                <?php sp_upm_get_template_part('/icons/content', 'icon-error'); ?>
            </div>
            <div class="sp-upm-message">
                <h6 class="font-medium text-red-900 sp-upm-message-header">Fatal error</h6>
                <p class="sp-upm-message-body"></p>
            </div>
        </div>
        <div class="sp-upm-loading-indicator">
            <div class="sp-upm-loading-icon">
                <?php sp_upm_get_template_part('/icons/content', 'icon-bouncing-circles'); ?>
            </div>
        </div>
        <button id="sp-upm-modal-close-button">
            <?php sp_upm_get_template_part('/icons/content', 'icon-close'); ?>
        </button>
        <div class="sp-upm-modal-main-content">
            <div class="sp-upm-modal-header">
                <h3 class="sp-upm-modal-header-text">
                    <?php echo $args['modal_header']; ?>
                </h3>
            </div>

            <div class="sp-upm-modal-body">
                <?php echo $args['modal_body']; ?>
            </div>

            <div class="sp-upm-modal-footer">
                <button class="e-button e-button--success sp-upm-modal-proceed-btn">
                    <?php echo $args['modal_footer_button_continue'] ?? "Submit"; ?>
                </button>
                <button class="e-button e-button--error sp-upm-modal-cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
</div>