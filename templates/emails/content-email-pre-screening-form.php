<?php
    $treatment_id = absint($args['treatment_id']);
    $prescreening_form = get_field( 'category_wp_form_url', 'product_cat_' . $treatment_id );
    $support_email = get_field( 'email_reply_to', 'product_cat_' . $treatment_id );
    $support_email = empty($support_email) ? 'menshealth@summitpharma.com.au' : $support_email;

    $treatment = get_term_by('id', $treatment_id, 'product_cat');
?>
<p><strong>Hi <?php echo $args['name']; ?>,</strong></p>

<p>Thank you for booking a consultation with Summit. To help us prepare for your telehealth appointment, we would appreciate it if you could complete the attached pre-screening form prior to your scheduled time. This step is optional, and you are welcome to discuss any concerns directly with our expert doctors during your consultation.</p>

<p>
    <a href="<?= $prescreening_form; ?>" style="display: inline-block;padding: 7px 20px;background-color: #006AD3;color: #fff;border-radius: 3px; text-decoration: none;"><?php echo $treatment->name; ?> Pre-screening Form</a>
</p>

<p>Should you have any questions, chat us on <a href="<?= esc_url(home_url()); ?>" target="_blank">summitpharma.com.au</a> or email us at <a href="mailto:<?php echo $support_email; ?>" style="color: #FFAC1C;"><?php echo $support_email; ?>.</a></p>

<p>We look forward to assisting you on your journey to better health. Thank you for choosing Summit Pharmacy for your well-being.
</p>

<p>Best regards,<br/>The Summit Team</p>