// Countdown Timer JavaScript

(function($) {
    'use strict';

    class CountdownTimer {
        constructor(element, deadline) {
            this.element = $(element);
            this.deadline = new Date(deadline).getTime();
            this.init();
        }

        init() {
            this.update();
            this.interval = setInterval(() => this.update(), 1000);
        }

        update() {
            const now = new Date().getTime();
            const distance = this.deadline - now;

            if (distance < 0) {
                clearInterval(this.interval);
                this.element.html(this.getExpiredHTML());
                this.element.addClass('expired');
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            this.element.html(this.getHTML(days, hours, minutes, seconds));

            if (distance < 3600000) { // Less than 1 hour
                this.element.addClass('warning');
            }
            if (distance < 600000) { // Less than 10 minutes
                this.element.addClass('critical');
            }
        }

        getHTML(days, hours, minutes, seconds) {
            return `
                <div class="countdown-display">
                    <div class="countdown-unit">
                        <span class="countdown-value">${this.pad(days)}</span>
                        <span class="countdown-label">dager</span>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-unit">
                        <span class="countdown-value">${this.pad(hours)}</span>
                        <span class="countdown-label">timer</span>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-unit">
                        <span class="countdown-value">${this.pad(minutes)}</span>
                        <span class="countdown-label">min</span>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-unit">
                        <span class="countdown-value">${this.pad(seconds)}</span>
                        <span class="countdown-label">sek</span>
                    </div>
                </div>
            `;
        }

        getExpiredHTML() {
            return `
                <div class="countdown-expired">
                    <span class="expired-message">Tippefrist utgått</span>
                </div>
            `;
        }

        pad(num) {
            return String(num).padStart(2, '0');
        }

        destroy() {
            clearInterval(this.interval);
        }
    }

    // Initialize all countdown timers
    $(document).ready(function() {
        $('.ol-countdown-timer').each(function() {
            const deadline = $(this).data('deadline');
            if (deadline) {
                new CountdownTimer(this, deadline);
            }
        });
    });

    // Export to window for global use
    window.CountdownTimer = CountdownTimer;

})(jQuery);
