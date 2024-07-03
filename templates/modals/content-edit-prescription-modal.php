<?php
    sp_upm_get_template_part('/modals/content', 'modal', [
        'modal_id' => 'edit',
        'modal_header' => 'Edit Consultation Form',
        'modal_body' => sp_upm_pending_prescriptions()::edit_consultation_form(),
        'modal_footer_button_continue' => "Update"
    ]);