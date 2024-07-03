<?php
    $product_categories = get_field('treatment_categories', 'option');
?>
<form method="post" id="form-approve-prescription">
    <table class="form-table">
        <tr>
            <th>
                <div class="regular-text mx-auto">
                    User: <span class="user-data"></span><br />
                    Initial Treatment: <span class="user-treatment"></span>
                </div>
            </th>
        </tr>
        <tr>
            <td>
                <select name="treatment_category" id="treatment_category" class="regular-text">
                    <option value="">Select a treatment</option>
                    <?php foreach ($product_categories as $category) : ?>
                        <option value="<?= $category->term_id; ?>"><?= $category->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <select name="treatment_medication_initial" id="treatment_medication_initial" class="js-treatment-medication-select treatment-medication regular-text">
                    <option value="">Select a medication</option>
                </select>

                <?php foreach ($product_categories as $category) : ?>
                    <?php
                        $args = array(
                            'limit' => -1,
                            'status' => ['publish', 'private'],
                            'category' => array( $category->slug ),
                        );

                        $products = wc_get_products( $args );

                        $key = "treatment_medication_$category->term_id";
                    ?>
                    <select name="<?= $key; ?>" id="<?= $key; ?>" class="js-treatment-medication-select treatment-medication regular-text">
                        <option value="">Select a medication</option>
                        <?php foreach ($products as $_product) : ?>
                            <?php if ($_product->is_type(['subscription', 'variable-subscription'])) : ?>
                            <option value="<?= $_product->get_id(); ?>"><?= $_product->get_name(); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php sp_upm_get_template_part('content', 'prescribers'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <input name="expiration_date" class="date input prescription-end-date prescription-field regular-text" type="date" id="prescription-end-date regular-text" />
            </td>
        </tr>
        <tr>
            <td>
                <input name="repeat_count" class="number input prescription-repeat-count prescription-field regular-text" type="number" id="js-repeat-count regular-text" placeholder="No. of Repeat Count" />
            </td>
        </tr>
    </table>
</form>