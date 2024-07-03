<script type="text/javascript">

    (function($) {
        $('.wpforms-field-date-time-time').val('<?= $args['initial_time']; ?>');
        window.wpforms_datepicker.defaultDate = '<?= $args['initial_date']; ?>';

        $( '.wpf-disabled-field input, .wpf-disabled-field textarea' ).attr({
            readonly: "readonly",
            tabindex: "-1"
        });

        $('.rebooking-message').text('<?= $args['rebooking_message']; ?>');
    })(jQuery)
</script>