/**
 * DelyntroBot — Isolated UI Animations v2.0
 * 
 * ⚠️ UI ANIMATIONS ONLY — No backend/business logic modifications.
 * - No route changes, no API calls, no auth modifications
 * - No class/ID renames that JS depends on
 * - Only additive visual enhancements
 * 
 * This file adds:
 * 1. Animated number counters
 * 2. Card tilt (hover 3D effect)
 * 3. Smooth scroll reveal
 * 4. Button ripple effect
 * 5. Cursor glow tracking on cards
 * 6. Sidebar tooltip micro-animation
 */

(function() {
    'use strict';

    // Wait for DOM to be fully ready
    document.addEventListener('DOMContentLoaded', function() {
        initAnimatedCounters();
        initCardTiltEffect();
        initScrollReveal();
        initButtonRipple();
        initCardGlowTracking();
        initSidebarEnhancements();
        initPageTransitions();
    });

    /**
     * 1. ANIMATED NUMBER COUNTERS
     * Finds elements with numeric content in dashboard cards
     * and animates them counting up from 0
     */
    function initAnimatedCounters() {
        // Target numeric values in card titles/stats
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && !entry.target.dataset.dlCounted) {
                    var el = entry.target;
                    var text = el.textContent.trim();
                    var num = parseInt(text.replace(/[^0-9]/g, ''));
                    
                    if (!isNaN(num) && num > 0 && text.match(/^\d+$/)) {
                        el.dataset.dlCounted = 'true';
                        animateCounter(el, 0, num, 800);
                    }
                }
            });
        }, { threshold: 0.3 });

        // Observe stat numbers in dashboard
        document.querySelectorAll('.card-body h2, .card-body h3, .card-body .fs-30, .card-body .fs-40, .card-body .fw-bold').forEach(function(el) {
            observer.observe(el);
        });
    }

    function animateCounter(el, start, end, duration) {
        var startTime = null;
        var originalText = el.textContent;
        
        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            
            // Ease-out curve for smooth deceleration
            var easedProgress = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(easedProgress * (end - start) + start);
            
            el.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = originalText; // Restore original with any formatting
            }
        }
        
        requestAnimationFrame(step);
    }

    /**
     * 2. CARD TILT EFFECT
     * Subtle 3D perspective tilt on hover
     */
    function initCardTiltEffect() {
        document.querySelectorAll('.container .row .col-md-3 .card').forEach(function(card) {
            card.addEventListener('mousemove', function(e) {
                var rect = card.getBoundingClientRect();
                var x = e.clientX - rect.left;
                var y = e.clientY - rect.top;
                var centerX = rect.width / 2;
                var centerY = rect.height / 2;
                
                var rotateX = ((y - centerY) / centerY) * -3;
                var rotateY = ((x - centerX) / centerX) * 3;
                
                card.style.transform = 'perspective(1000px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateY(-6px) scale(1.01)';
            });
            
            card.addEventListener('mouseleave', function() {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0) scale(1)';
            });
        });
    }

    /**
     * 3. SCROLL REVEAL
     * Cards and sections fade in as they enter viewport
     */
    function initScrollReveal() {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

        // Apply to main content rows (not sidebar)
        document.querySelectorAll('.main-wrapper .row').forEach(function(row, idx) {
            if (idx > 0) { // Skip the first row to avoid flash
                row.style.opacity = '0';
                row.style.transform = 'translateY(16px)';
                row.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                row.style.transitionDelay = (idx * 0.05) + 's';
                observer.observe(row);
            }
        });
    }

    /**
     * 4. BUTTON RIPPLE EFFECT
     * Creates a Material-style ripple on click
     */
    function initButtonRipple() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn');
            if (!btn) return;

            // Remove existing ripple
            var existingRipple = btn.querySelector('.dl-ripple');
            if (existingRipple) existingRipple.remove();

            var ripple = document.createElement('span');
            ripple.className = 'dl-ripple';
            
            var rect = btn.getBoundingClientRect();
            var size = Math.max(rect.width, rect.height);
            var x = e.clientX - rect.left - size / 2;
            var y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = [
                'position:absolute',
                'border-radius:50%',
                'pointer-events:none',
                'width:' + size + 'px',
                'height:' + size + 'px',
                'left:' + x + 'px',
                'top:' + y + 'px',
                'background:rgba(255,255,255,0.25)',
                'transform:scale(0)',
                'animation:dlRippleAnim 0.5s ease-out forwards',
                'z-index:1'
            ].join(';');
            
            btn.appendChild(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });

        // Inject ripple animation keyframes
        if (!document.getElementById('dl-ripple-style')) {
            var style = document.createElement('style');
            style.id = 'dl-ripple-style';
            style.textContent = '@keyframes dlRippleAnim { to { transform: scale(2.5); opacity: 0; } }';
            document.head.appendChild(style);
        }
    }

    /**
     * 5. CARD GLOW TRACKING
     * Makes the card glow follow the mouse cursor
     */
    function initCardGlowTracking() {
        document.querySelectorAll('.card').forEach(function(card) {
            card.addEventListener('mousemove', function(e) {
                var rect = card.getBoundingClientRect();
                var x = ((e.clientX - rect.left) / rect.width) * 100;
                var y = ((e.clientY - rect.top) / rect.height) * 100;
                card.style.setProperty('--mouse-x', x + '%');
                card.style.setProperty('--mouse-y', y + '%');
            });
        });
    }

    /**
     * 6. SIDEBAR ENHANCEMENTS
     * - Active indicator animation
     * - Sub-menu smooth toggle
     */
    function initSidebarEnhancements() {
        // Smooth expand/collapse for sub-menus
        document.querySelectorAll('.sidebar .have-menus-sub > a').forEach(function(toggle) {
            var submenu = toggle.nextElementSibling;
            if (submenu && submenu.classList.contains('menu-sub-accordion')) {
                submenu.style.overflow = 'hidden';
                submenu.style.transition = 'max-height 0.3s ease-out, opacity 0.3s ease-out';

                // Check if already visible
                if (!submenu.classList.contains('show')) {
                    submenu.style.maxHeight = '0';
                    submenu.style.opacity = '0';
                }
            }
        });

        // Add pulse effect to active nav item on page load
        var activeLink = document.querySelector('.sidebar .nav-link.active');
        if (activeLink) {
            activeLink.style.animation = 'dlActivePulse 0.5s ease-out';
        }

        // Inject active pulse keyframes
        if (!document.getElementById('dl-sidebar-style')) {
            var style = document.createElement('style');
            style.id = 'dl-sidebar-style';
            style.textContent = '@keyframes dlActivePulse { 0% { box-shadow: 0 0 0 0 rgba(79,70,229,0.4); } 100% { box-shadow: 0 4px 12px rgba(79,70,229,0.2); } }';
            document.head.appendChild(style);
        }
    }

    /**
     * 7. PAGE TRANSITIONS
     * Smooth content transitions when navigating
     */
    function initPageTransitions() {
        // Fade in main content
        var mainWrapper = document.querySelector('.main-wrapper');
        if (mainWrapper) {
            mainWrapper.style.opacity = '0';
            mainWrapper.style.transition = 'opacity 0.3s ease-out';
            
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    mainWrapper.style.opacity = '1';
                });
            });
        }
    }

})();
