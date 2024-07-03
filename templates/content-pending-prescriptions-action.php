<div hidden id="form_entry_data_<?= $item['entry_id']; ?>">
    <?php
        echo wp_json_encode([
            'entry_id' => $item['entry_id'],
            'treatment_id' => $item['treatment_id'],
            'appointment_date' => $item['appointment_date'],
            'appointment_time' => sp_upm_doctors_appointments()->convert_date_time($item['appointment_time'], 'H:i:s', 'H:i'),
            'user_id' => $item['user_id'],
            'form_id' => $item['form_id'],
            'final_treatment_cat_id' => $item['final_treatment_cat_id']
        ]);
    ?>
</div>

<button class="edit-entry-btn btn-entry-action button button-secondary" data-entryid="<?php echo $args['item']['entry_id']; ?>" data-treatment-id="<?php echo $args['item']['treatment_id']; ?>" data-action="edit">
    Edit
</button>
<?php if ($item['status'] != 2) : ?>
<button class="approve-subscription-btn btn-entry-action button button-primary" data-entryid="<?php echo $args['item']['entry_id']; ?>" data-treatment-id="<?php echo $args['item']['treatment_id']; ?>" data-action="approve">
    Prescribe
</button>
<?php endif; ?>
<button class="delete-entry-btn button btn-entry-action button-danger" data-entryid="<?php echo $args['item']['entry_id']; ?>" data-treatment-id="<?php echo $args['item']['treatment_id']; ?>" data-action="delete">
    Delete
</button>