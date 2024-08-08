<?php
    $status = absint($args['status']);

    switch ($status) {
        case 1:
            $text = 'Paid';
            break;
        case 2:
            $text = 'Approved';
            break;
        case 3:
            $text = 'Treatment Ordered';
            break;
        case 4:
            $text = 'Form Submitted';
            break;
        default:
            $text = 'Pending Payment';
            break;
    }
?>
<span class="sm-upm-telehealth-payment-status sm-upm-telehealth-payment-status--<?= strtolower(str_replace(' ', '-', $text)); ?>">
    <?= $text; ?>
</span>