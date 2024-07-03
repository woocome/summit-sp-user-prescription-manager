<?php
    sp_upm_get_template_part('/modals/content', 'modal', [
        'modal_id' => 'approve',
        'modal_header' => 'Prescription Form',
        'modal_body' => sp_upm_pending_prescriptions()::approve_prescription_form(),
        'modal_footer_button_continue' => "Prescribe"
    ]);