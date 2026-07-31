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

    .sp-opt-popup{
        width: 100%;
        height: 100%;
        position: fixed;
        top: 0;
        margin: auto;
        background-color: rgba(0, 0, 0, <?php _ec($opt_options["opt_opacity"]/100)?>);
        font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        display: none;
        z-index: 999999999999999;

    }

    .sp-opt-popup.sp-opt-show{
        display: block;
    }

    .sp-opt-popup .sp-opt-content{
        max-width: 400px;
        background: <?php _ec($opt_options["opt_bg"])?>;
        margin: 150px auto;
        border-radius: 10px;
        box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
    }

    @media only screen and (max-width: 420px) {
        .sp-opt-popup .sp-opt-content{
            margin: 150px 20px;
        }
    }

    .sp-opt-popup .sp-opt-content .sp-opt-popup-banner{
        width: 100%;
    }

    .sp-opt-popup .sp-opt-content .sp-opt-popup-banner img{
        width: 100%;
        border: none!important;
        border-radius: 10px 10px 0 0;
    }

    .sp-opt-popup .sp-opt-content .sp-opt-popup-wrap{
        padding: 25px;
        text-align: center;
    }

    .sp-opt-popup .sp-opt-content .sp-opt-popup-title{
        font-size: 18px;
        text-transform: uppercase;
        color: #0089cf;
        font-weight: bold;
        max-width: 200px;
        margin: 0 auto 15px auto;
    }

    .sp-opt-popup .sp-opt-content .sp-opt-popup-desc{
        font-size: 14px;
        margin-bottom: 20px;
    }

    .sp-opt-popup .sp-opt-content .sp-opt-btn-allow{
        text-align: right;
        appearance: none;
        background-color: <?php _ec($opt_options["opt_allow_btn_bg"])?>;
        border: 1px solid rgba(27, 31, 35, 0.15);
        border-radius: 6px;
        box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
        box-sizing: border-box;
        color: <?php _ec($opt_options["opt_allow_btn_text"])?>;
        cursor: pointer;
        display: inline-block;
        font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        font-size: 14px;
        font-weight: 500;
        line-height: 20px;
        list-style: none;
        padding: 10px 20px;
        position: relative;
        transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
        user-select: none;
        -webkit-user-select: none;
        touch-action: manipulation;
        vertical-align: middle;
        white-space: nowrap;
        word-wrap: break-word;
        margin-left: 20px;
        min-width: 150px;
        text-align: center;
        text-decoration: none;
    }

    .sp-opt-popup .sp-opt-content .sp-opt-btn-deny{
        position: absolute;
        top: 10px;
        right: 10px;
        width: 20px;
        height: 20px;
        background: #fff;
        border-radius: 100px;
        color: #000;
        text-align: center;
        padding: 0;
        text-decoration: none;
        line-height: 16px;
    }
</style>
<div class="sp-opt-popup" id="sp-opt-main">
    <div class="sp-opt-content sp__animate__animated sp__animate__slideInDown">
    	<?php if (isset($opt_options['opt_banner']) && $opt_options['opt_banner'] != ""): ?>
    		<div class="sp-opt-popup-banner">
	            <img src="<?php _ec( get_file_url($opt_options['opt_banner']) )?>">
	        </div>
    	<?php endif ?>
        
        <a class="sp-opt-btn sp-opt-btn-deny" id="sp-deny-btn" href="javascript:void(0);">x</a>
        <div class="sp-opt-popup-wrap">
            <div class="sp-opt-popup-title"><?php _ec($opt_options["opt_title"])?></div>
            <div class="sp-opt-popup-desc"><?php _ec($opt_options["opt_desc"])?></div>
            <div class="sp-opt-popup-action">
                <a class="sp-opt-btn sp-opt-btn-allow" id="sp-allow-btn" href="#allow"><?php _e("Allow")?></a>
            </div>
        </div>
    </div>
</div>