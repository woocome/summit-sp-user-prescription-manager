<?php
    $table = $args['table_class'];
    $title = $args['title'];
?>
<div class="wrap">
    <h2><?= $title; ?></h2>
    <div id="nds-wp-list-table-demo">
        <div id="nds-post-body">
            <form id="nds-user-list-form" method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <input type="hidden" name="treatment" value="<?php echo isset($_REQUEST['treatment']) ? sanitize_text_field($_REQUEST['treatment']) : ''; ?>" />
                <?php 
                    $table->change_prescriptions_table->search_box( __( 'Search Users', SP_UPM_TEXT_DOMAIN ), 'nds-user-find');
                    $table->change_prescriptions_table->display(); 
                ?>
            </form>
        </div>
    </div>
</div>