<?php
    $table = $args['table_class'];
?>
<div class="wrap">
    <h2>Pending Prescriptions</h2>
    <div id="nds-wp-list-table-demo">			
        <div id="nds-post-body">		
            <form id="nds-user-list-form" method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <input type="hidden" name="treatment" value="<?php echo sanitize_text_field($_REQUEST['treatment']); ?>" />
                <?php 
                    $table->pending_prescriptions_table->search_box( __( 'Search Users', SP_UPM_TEXT_DOMAIN ), 'nds-user-find');
                    $table->pending_prescriptions_table->display(); 
                ?>
            </form>
        </div>
    </div>
</div>