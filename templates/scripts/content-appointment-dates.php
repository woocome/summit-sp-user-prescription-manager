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
        function formatDate(dateString) {
            // Parse the date string into a Date object
            const date = new Date(dateString);

            // Get the day, month, and year from the Date object
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0'); // getMonth() returns 0-11
            const year = date.getFullYear();

            // Format the date as d/m/y
            return `${day}/${month}/${year}`;
        }

        function disable_date_time(dateStr) {
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
        }

        $( window ).on( 'load', function() {
            if ($('.wpforms-field-date-time-time').length) {
                $('.wpforms-field-date-time-time').timepicker('show');
                $('.wpforms-field-date-time-time').timepicker('hide');

                const earliestDate = $('.flatpickr-day:not(.flatpickr-disabled)').eq(0).attr('aria-label');

                window.wpforms_datepicker.defaultDate = new Date(earliestDate);
                const wpformsDate = flatpickr('.wpforms-datepicker-wrap', window.wpforms_datepicker);
                wpformsDate.jumpToDate(new Date(earliestDate));

                disable_date_time(formatDate(earliestDate));
                const earlistTime = $('.ui-timepicker-list li:not(.ui-timepicker-disabled)').eq(0).text();
                $('.earliest-date span').text(`${earliestDate} ${earlistTime}`);

                $('.wpforms-field-date-time-date').on('change', function(e) {
                    const dateStr = $(this).val();
                    $(this).parents('fieldset').find('.wpforms-field-date-time-time').val('');

                    disable_date_time(dateStr);
                });
            }
        } )
    })(jQuery);
</script>