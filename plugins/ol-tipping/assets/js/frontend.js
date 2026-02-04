// Frontend JavaScript

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle form submission
        $('#ol-tipping-form').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const eventId = form.data('event-id');
            const nonce = $('input[name="nonce"]').val();
            const tips = {};

            // Collect selected athletes
            $('.ol-athlete-select').each(function(index) {
                const value = $(this).val();
                if (value) {
                    tips[index] = parseInt(value);
                }
            });

            // Submit via AJAX
            $.ajax({
                url: olTippingData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'submit_tip',
                    event_id: eventId,
                    tips: tips,
                    nonce: nonce,
                },
                success: function(response) {
                    if (response.success) {
                        alert('Tips lagret successfully!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while saving your tips.');
                }
            });
        });

        // Countdown timer
        function updateCountdown() {
            const elements = $('.ol-countdown p');
            elements.each(function() {
                const text = $(this).text();
                // Simple countdown update - in production, would use actual time
                // console.log('Countdown active: ' + text);
            });
        }

        // Update countdown every second
        setInterval(updateCountdown, 1000);
    });

})(jQuery);
