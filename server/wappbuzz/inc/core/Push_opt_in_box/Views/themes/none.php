<style type="text/css">
	.sp__animate__animated {
	    -webkit-animation-duration: 1s;
	    animation-duration: 1s;
	    -webkit-animation-duration: 0.5s;
	    animation-duration: 0.5s;
	    -webkit-animation-fill-mode: both;
	    animation-fill-mode: both;
	}
	.sp__animate__animated.sp__animate__infinite {
	    -webkit-animation-iteration-count: infinite;
	    animation-iteration-count: infinite;
	}
	.sp__animate__animated.sp__animate__repeat-1 {
	    -webkit-animation-iteration-count: 1;
	    animation-iteration-count: 1;
	    -webkit-animation-iteration-count: 1;
	    animation-iteration-count: 1;
	}
	.sp__animate__animated.sp__animate__repeat-2 {
	    -webkit-animation-iteration-count: 2;
	    animation-iteration-count: 2;
	    -webkit-animation-iteration-count: calc(1 * 2);
	    animation-iteration-count: calc(1 * 2);
	}
	.sp__animate__animated.sp__animate__repeat-3 {
	    -webkit-animation-iteration-count: 3;
	    animation-iteration-count: 3;
	    -webkit-animation-iteration-count: calc(1 * 3);
	    animation-iteration-count: calc(1 * 3);
	}
	.sp__animate__animated.sp__animate__delay-1s {
	    -webkit-animation-delay: 1s;
	    animation-delay: 1s;
	    -webkit-animation-delay: 0.5s;
	    animation-delay: 0.5s;
	}
	.sp__animate__animated.sp__animate__delay-2s {
	    -webkit-animation-delay: 2s;
	    animation-delay: 2s;
	    -webkit-animation-delay: calc(0.5s * 2);
	    animation-delay: calc(0.5s * 2);
	}
	.sp__animate__animated.sp__animate__delay-3s {
	    -webkit-animation-delay: 3s;
	    animation-delay: 3s;
	    -webkit-animation-delay: calc(0.5s * 3);
	    animation-delay: calc(0.5s * 3);
	}
	.sp__animate__animated.sp__animate__delay-4s {
	    -webkit-animation-delay: 4s;
	    animation-delay: 4s;
	    -webkit-animation-delay: calc(0.5s * 4);
	    animation-delay: calc(0.5s * 4);
	}
	.sp__animate__animated.sp__animate__delay-5s {
	    -webkit-animation-delay: 5s;
	    animation-delay: 5s;
	    -webkit-animation-delay: calc(0.5s * 5);
	    animation-delay: calc(0.5s * 5);
	}
	.sp__animate__animated.sp__animate__faster {
	    -webkit-animation-duration: 0.5s;
	    animation-duration: 0.5s;
	    -webkit-animation-duration: calc(0.5s / 2);
	    animation-duration: calc(0.5s / 2);
	}
	.sp__animate__animated.sp__animate__fast {
	    -webkit-animation-duration: 0.8s;
	    animation-duration: 0.8s;
	    -webkit-animation-duration: calc(0.5s * 0.8);
	    animation-duration: calc(0.5s * 0.8);
	}
	.sp__animate__animated.sp__animate__slow {
	    -webkit-animation-duration: 2s;
	    animation-duration: 2s;
	    -webkit-animation-duration: calc(0.5s * 2);
	    animation-duration: calc(0.5s * 2);
	}
	.sp__animate__animated.sp__animate__slower {
	    -webkit-animation-duration: 3s;
	    animation-duration: 3s;
	    -webkit-animation-duration: calc(0.5s * 3);
	    animation-duration: calc(0.5s * 3);
	}
	@media (prefers-reduced-motion: reduce), print {
	    .sp__animate__animated {
	        -webkit-animation-duration: 1ms !important;
	        animation-duration: 1ms !important;
	        -webkit-transition-duration: 1ms !important;
	        transition-duration: 1ms !important;
	        -webkit-animation-iteration-count: 1 !important;
	        animation-iteration-count: 1 !important;
	    }
	    .sp__animate__animated[class*="Out"] {
	        opacity: 0;
	    }
	}

	@-webkit-keyframes slideInDown {
	    0% {
	        -webkit-transform: translate3d(0, -100%, 0);
	        transform: translate3d(0, -100%, 0);
	        visibility: visible;
	    }
	    to {
	        -webkit-transform: translateZ(0);
	        transform: translateZ(0);
	    }
	}
	@keyframes slideInDown {
	    0% {
	        -webkit-transform: translate3d(0, -100%, 0);
	        transform: translate3d(0, -100%, 0);
	        visibility: visible;
	    }
	    to {
	        -webkit-transform: translateZ(0);
	        transform: translateZ(0);
	    }
	}
	.sp__animate__slideInDown {
	    -webkit-animation-name: slideInDown;
	    animation-name: slideInDown;
	}

	@-webkit-keyframes slideInUp {
	    0% {
	        -webkit-transform: translate3d(0, 100%, 0);
	        transform: translate3d(0, 100%, 0);
	        visibility: visible;
	    }
	    to {
	        -webkit-transform: translateZ(0);
	        transform: translateZ(0);
	    }
	}
	@keyframes slideInUp {
	    0% {
	        -webkit-transform: translate3d(0, 100%, 0);
	        transform: translate3d(0, 100%, 0);
	        visibility: visible;
	    }
	    to {
	        -webkit-transform: translateZ(0);
	        transform: translateZ(0);
	    }
	}
	.sp__animate__slideInUp {
	    -webkit-animation-name: slideInUp;
	    animation-name: slideInUp;
	}
	@-webkit-keyframes swing {
	    20% {
	        -webkit-transform: rotate(15deg);
	        transform: rotate(15deg);
	    }
	    40% {
	        -webkit-transform: rotate(-10deg);
	        transform: rotate(-10deg);
	    }
	    60% {
	        -webkit-transform: rotate(5deg);
	        transform: rotate(5deg);
	    }
	    80% {
	        -webkit-transform: rotate(-5deg);
	        transform: rotate(-5deg);
	    }
	    to {
	        -webkit-transform: rotate(0deg);
	        transform: rotate(0deg);
	    }
	}
	@keyframes swing {
	    20% {
	        -webkit-transform: rotate(15deg);
	        transform: rotate(15deg);
	    }
	    40% {
	        -webkit-transform: rotate(-10deg);
	        transform: rotate(-10deg);
	    }
	    60% {
	        -webkit-transform: rotate(5deg);
	        transform: rotate(5deg);
	    }
	    80% {
	        -webkit-transform: rotate(-5deg);
	        transform: rotate(-5deg);
	    }
	    to {
	        -webkit-transform: rotate(0deg);
	        transform: rotate(0deg);
	    }
	}
	.sp__animate__swing {
	    -webkit-transform-origin: top center;
	    transform-origin: top center;
	    -webkit-animation-name: swing;
	    animation-name: swing;
	}
	
    .sp-opt-none{
        max-width: 55px;
        height: 55px;
        position: fixed;
        bottom: <?php _ec($opt_options["widget_bottom"])?>px;
        border-radius: 100px;
        width: 55px;
        box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
        background-color: <?php _ec($opt_options["widget_bg"])?>;
        z-index: 999999999999999;
        display: none;
    }

    .sp-opt-none.sp-opt-show{
    	display: block;
    }

    .sp-opt-none.left{
        left: <?php _ec($opt_options["widget_left"])?>px;
    }

    .sp-opt-none.right{
        right: <?php _ec($opt_options["widget_right"])?>px;
    }

    .sp-opt-none img{
        width: 100%;
        height: 100%;
        border: none!important;
        border-radius: 100px;
    }

</style>
<div class="sp-opt-none sp__animate__animated sp__animate__swing <?php _ec($opt_options["widget_position"])?>" id="sp-opt-main">
	<a id="sp-allow-btn" href="#allow">
    	<img src="<?php _ec($opt_options["widget_icon"])?>">
	</a>
</div>
