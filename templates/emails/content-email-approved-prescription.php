<?php
/**
 * @param string $args['customer_name']
 * @param string $args['medication_name']
 * @param string $args['concern']
 * @param string $args['prescriber']
 * @param string $args['contact_email']
 * 
 */
?>
<div style="max-width: 560px; background: #ffffff; border-radius: 5px; margin: 40px auto; font-family: Open Sans,Helvetica,Arial; font-size: 15px; color: #000; text-align: left;">
    <div style="text-align: center;">
        <a href="<?= esc_url(home_url()); ?>" target="_blank">
            <img class="aligncenter" style="max-width: 100%; display: block;" src="<?= esc_url(home_url()); ?>/wp-content/uploads/2024/07/Final-Header-copy.jpg" />
        </a>
    </div>
    <div style="padding: 10px 20px;">
        <p style="text-align: left;">Dear <?php echo $args['customer_name']; ?>,</p>
        <p>We hope this message finds you well. </p>
        <p>We're thrilled to share some exciting news with you – After carefully reviewing your evaluation you have been officially approved for the treatment plan below:</p>

        <p>
            <strong>Concern:</strong> <?php echo $args['concern']; ?><br/>
            <strong>Treatment:</strong> <?php echo $args['medication_name']; ?><br/>
            <strong>Prescriber:</strong> <?php echo $args['prescriber']; ?>
        </p>

        <p><strong style="color: #C41E3A;">Order Process:</strong></p>
        <ul>
            <li>Log in to your Summit account profile page</li>
            <li>Choose your preferred subscription plan or make a one-time purchase for your medication.</li>
            <li>Checkout Pay</li>
            <li>Expect delivery within 2-3 business days</li>
        </ul>

        <p><strong>Your Summit Account:</strong>  <a style="background: #0D3276; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 3px; letter-spacing: 0.3px; display: inline-block;" href="<?= esc_url(home_url('/my-account/')); ?>" target="_blank">Login - Summit Pharma</a></p>

        <p>Details above can also be seen in your Summit Pharma Account.</p>

        <p>Should you have any questions, chat us on <a href="<?= esc_url(home_url()); ?>" target="_blank">summitpharma.com.au</a> or email us at <a href="mailto:<?= $args['contact_email']; ?>" style="color: #FFAC1C;"><?= $args['contact_email']; ?>.</a></p>

        <p>Exciting times lie ahead! Thank you for trusting Summit Pharmacy for your well-being.</p>

        <p>Best,<br/>Summit Team</p>
    </div>
    <div style="text-align: center; margin-top: 10px;">
        <a href="<?= esc_url(home_url()); ?>" target="_blank">
            <img class="aligncenter" style="max-width: 100%; display: block;" src="<?= esc_url(home_url()); ?>/wp-content/uploads/2024/07/Desktop-Footer-copy.jpg" />
        </a>
    </div>
</div>