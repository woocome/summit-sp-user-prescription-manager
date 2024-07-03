<?php
    $args = [
        'post_type' => 'doctor',
        'posts_per_page' => -1,
    ];

    $loop = new WP_Query($args);

    if ($loop->have_posts()) :
?>
    <select name="prescriber_id" id="treatment_prescriber" class="regular-text"  required>
        <option value="">Select a prescriber</option>
    <?php while($loop->have_posts()) : $loop->the_post(); ?>
        <?php global $post; ?>
        <option value="<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></option>
    <?php endwhile; ?>
    </select>
<?php endif; ?>