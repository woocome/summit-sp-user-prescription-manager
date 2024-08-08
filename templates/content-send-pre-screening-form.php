<?php
    $treatment_id = absint($args['treatment_id']);
    $user_id = isset($args['user_id']) ? absint( $args['user_id'] ) : null;
    $button_label = isset($args['button_label']) ? $args['button_label'] : 'later: send pre-screening form to email';
?>
<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
    <input type="hidden" name="action" value="send_pre_screening_form_to_user">
    <?php if ($user_id) : ?>
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    <?php endif; ?>
    <input type="hidden" name="treatment_id" value="<?php echo $treatment_id; ?>">

    <div>
        <input type="submit" value="<?= $button_label; ?>" class="sp-content-summary__button button button-primary">
    </div>
</form>