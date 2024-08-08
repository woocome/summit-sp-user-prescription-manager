<?php
    $bmi_metrics = $args['bmi_metrics'];

    if (! $bmi_metrics) return;
?>

<table class="bmi-metrics">
    <thead>
        <tr>
            <th></th>
            <th>Medication</th>
            <th>Quantity</th>
            <th>Purchase Date</th>
            <th>Weight (kg)</th>
            <th>Height (cm)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach( $bmi_metrics as $key => $bmi ) : ?>
            <?php
                $_product = wc_get_product(absint($bmi['product']));
                $qty_multiplier = absint(get_field('quantity_multiplier', $_product->get_id()));
                $quantity = $qty_multiplier ? (absint($bmi['quantity']) * $qty_multiplier) : $bmi['quantity'];
            ?>
            <tr>
                <td><?php echo ++$key; ?>.</td>
                <td><?php echo $_product->get_name(); ?></td>
                <td><?php echo $quantity; ?></td>
                <td><?php echo $bmi['date']; ?></td>
                <td><?php echo $bmi['weight']; ?></td>
                <td><?php echo $bmi['height']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>