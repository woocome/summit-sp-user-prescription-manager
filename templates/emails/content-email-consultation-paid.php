<?php
/**
 * @param string $args['customer_name']
 * @param string $args['medication_name']
 * @param string $args['concern']
 * @param string $args['prescriber']
 */
?>
<div style="max-width: 560px; padding: 20px; background: #ffffff; border-radius: 5px; margin: 40px auto; font-family: Open Sans,Helvetica,Arial; font-size: 15px; color: #000;">
    <p>A payment for a telehealth consultation has been successfully processed. Below are the details of the transaction for your records:</p>
    <ul>
        <li><strong>Customer Name:</strong> <?php echo $args['customer_name']; ?></li>
        <li><strong>Customer Email:</strong> <?php echo $args['customer_email']; ?></li>
        <li><strong>Concern:</strong> <?php echo $args['concern']; ?></li>
        <li><strong>Appointment Schedule:</strong> <?php echo $args['appointment_schedule']; ?></li>
    </ul>

    <p>This payment confirms the patient's telehealth consultation has been officially booked and paid for. Please ensure that the necessary arrangements are made for the patient to have a smooth and effective consultation session.</p>
</div>