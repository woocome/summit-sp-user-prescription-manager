<?php
	/**
	 * @param int $args['treatment_id']
	 * @param int $args['name']
	 **/
    $treatment_id = absint($args['treatment_id']);
    $prescreening_form = get_field( 'category_wp_form_url', 'product_cat_' . $treatment_id );
    $support_email = get_field( 'email_reply_to', 'product_cat_' . $treatment_id );
    $support_email = empty($support_email) ? 'menshealth@summitpharma.com.au' : $support_email;

    $treatment = get_term_by('id', $treatment_id, 'product_cat');
?>
<p><strong>Hi <?php echo $args['name']; ?>,</strong></p>

<p>Thank you for booking a consultation with Summit. To help us prepare for your telehealth appointment, please complete the attached pre-screening form.</p>
<p>Please note that if the pre-screening form is not completed at least <strong>12 hours prior</strong> to your scheduled time, your appointment may be forfeited. In such cases, rescheduling will be allowed only once. Thank you for your understanding!</p>

<p>
    <a href="<?= $prescreening_form; ?>" style="display: inline-block;padding: 7px 20px;background-color: #006AD3;color: #fff;border-radius: 3px; text-decoration: none;"><?php echo $treatment->name; ?> Pre-screening Form</a>
</p>

<p>Should you have any questions, chat us on <a href="<?= esc_url(home_url()); ?>" target="_blank">summitpharma.com.au</a> or email us at <a href="mailto:<?php echo $support_email; ?>" style="color: #FFAC1C;"><?php echo $support_email; ?>.</a></p>

<p>We look forward to assisting you on your journey to better health. Thank you for choosing Summit Pharmacy for your well-being.
</p>

<p>Best regards,<br/>The Summit Team</p>