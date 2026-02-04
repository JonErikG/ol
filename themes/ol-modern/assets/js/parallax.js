// Parallax Effect JavaScript

(function($) {
    'use strict';

    $.fn.parallax = function(options) {
        const defaults = {
            speed: 0.5,
        };

        const settings = $.extend({}, defaults, options);

        return this.each(function() {
            const $element = $(this);
            const elementOffset = $element.offset().top;

            $(window).on('scroll', function() {
                const scrollTop = $(window).scrollTop();
                const distance = elementOffset - scrollTop;

                if (distance < $(window).height()) {
                    const yPos = distance * settings.speed;
                    $element.css('background-position', 'center ' + yPos + 'px');
                }
            });
        });
    };

})(jQuery);
