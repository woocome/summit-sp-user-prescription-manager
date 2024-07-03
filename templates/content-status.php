<?php
    $text = '';

    $status = absint($args['status']);
    $text = $status == 0 ? 'Pending Payment' : ( $status == 1 ? 'Paid' : 'Approved');
    $text = $status == 3 ? 'Treatment Ordered' : $text;
?>
<span class="sm-upm-telehealth-payment-status sm-upm-telehealth-payment-status--<?= strtolower(str_replace(' ', '-', $text)); ?>">
    <?= $text; ?>
</span>