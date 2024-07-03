<script type="text/javascript">
(function($) {
    acf.add_filter('date_time_picker_args', function( args, $field ){
        // do something to args
        args['minTime'] = "13:00:00";
        args['maxTime'] = "17:00:00";
        args['stepMinute'] = 15;
        
        // return
        return args;
    });
})(jQuery);
</script>