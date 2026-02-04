// Main Theme JavaScript

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize parallax effect
        $('.parallax-hero').parallax({
            speed: 0.5,
        });

        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100,
                }, 1000);
            }
        });

        // Add scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px',
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    $(entry.target).addClass('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe articles and sections
        $('.post-content, .site-main > *').each(function() {
            observer.observe(this);
        });

        // Animated counters (for leaderboard positions)
        function animateCounter(element, target, duration = 1000) {
            let current = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    $(element).text(target);
                    clearInterval(timer);
                } else {
                    $(element).text(Math.floor(current));
                }
            }, 16);
        }

        // Trigger counter animation on scroll into view
        $('.stat-number').each(function() {
            const $this = $(this);
            const target = parseInt($this.text());
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target, target);
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            observer.observe(this);
        });

        // Mobile menu toggle (if needed)
        const toggleMobileMenu = function() {
            const $menu = $('.primary-menu');
            $menu.slideToggle();
        };

        // Handle responsive navigation
        if ($(window).width() < 768) {
            // Mobile menu handling
        }
    });

})(jQuery);
