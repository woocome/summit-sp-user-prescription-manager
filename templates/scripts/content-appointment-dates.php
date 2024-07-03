<style>
    .ui-timepicker-disabled {
        display: none !important;
    }
</style>
<script type="text/javascript">
    const appointment_dates = <?php echo $args['appointment_dates']; ?>;
    const available_weekdays = <?php echo $args['available_weekdays']; ?>;
    const available_time_range = <?php echo $args['available_time_range']; ?>;
    const disabled_dates = <?php echo $args['disabled_dates']; ?>;
    const disabled_date_time_range = <?php echo $args['disabled_date_time_range']; ?>;

    const options = { timeZone: 'Australia/Brisbane', day: '2-digit', month: '2-digit', year: 'numeric' };
    const today = new Date().toLocaleDateString('en-AU', options);

    var d = new Date();

    function formatDateTimeRange(data) {
        if (! data) return;

        return data.map(item => ({
            from: `${item.date_time_from.date}`,
            to: `${item.date_time_to.date}`
        }));
    }

    const formattedDisabledDatetimerange = formatDateTimeRange(disabled_date_time_range) ?? [];

    window.wpforms_datepicker = {
        // Don't allow users to pick specific range of dates
        dateFormat: "d/m/Y",
        disable: [
            // disable day of the week
            function(date) {
                
                // return true to disable
                return available_weekdays ? !available_weekdays.includes(date.getDay().toString()) : "";
            },
            // disable same day booking
            today,
            ...disabled_dates,
            ...formattedDisabledDatetimerange
        ],
    }

    var initialTime = new Date('January 1, 2024 13:00:00');

    window.wpforms_timepicker = {
        minTime: available_time_range.from,
        maxTime: available_time_range.to,
        step: available_time_range.interval,
    };

    (function($) {
        $( window ).on( 'load', function() {
			if ($('.wpforms-field-date-time-time').length) {
				$('.wpforms-field-date-time-time').timepicker('show');
				$('.wpforms-field-date-time-time').timepicker('hide');

				$('.wpforms-field-date-time-date').on('change', function(e) {
					const dateStr = $(this).val();
					$(this).parents('fieldset').find('.wpforms-field-date-time-time').val('');

					if (appointment_dates[dateStr]) {
						$('.ui-timepicker-list li').each(function(i, el) {
							$(el).removeClass('ui-timepicker-disabled');

							appointment_dates[dateStr].forEach(function(date, key) {
								if (date === $(el).text()) {
									$(el).addClass('ui-timepicker-disabled');
								}
							});
						});
					} else {
						$('.ui-timepicker-list li').removeClass('ui-timepicker-disabled');
					}
				});
			}
        } )
    })(jQuery);
</script>