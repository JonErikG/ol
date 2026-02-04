// Admin JavaScript

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle event selection for results
        $('#event_id').on('change', function() {
            const eventId = $(this).val();
            if (eventId) {
                loadAthletes(eventId);
            }
        });

        function loadAthletes(eventId) {
            // AJAX call to load athletes for the selected event
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'load_athletes_for_event',
                    event_id: eventId,
                },
                success: function(response) {
                    $('#results-list').html(response);
                }
            });
        }
    });

})(jQuery);
