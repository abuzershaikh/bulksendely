<?php $platforms = db_fetch("*", TB_PLATFORM, ["status" => 1]); ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
    <meta name="keywords" content="<?php _ec( get_option("website_keyword", "social network, marketing, brands, businesses, agencies, individuals") )?>" />
    <meta name="description" content="<?php _ec( get_option("website_description", "Let start to manage your social media so that you have more time for your business.") )?>" />
    <meta name="author" content="stackposts.com" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php _ec( get_option("website_title", "#1 Social Media Management & Analysis Platform") )?></title>
    <link rel="shortcut icon" href="<?php _ec( get_option("website_favicon", base_url("assets/img/favicon.svg")) )?>" />
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_theme_url() ) ?>Assets/fonts/fontawesome/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/plugins/owlcarousel/css/owl.carousel.min.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/plugins/owlcarousel/css/owl.theme.default.min.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/plugins/aos/aos.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/plugins/pagination/pagination.min.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/plugins/countup/odometer.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/plugins/flags/flag-icon.css">
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/css/style.css">
    <script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/jquery/jquery.min.js"></script>
    <script type="text/javascript">
        var PATH  = '<?php _ec( base_url()."/" )?>';
        var csrf = "<?php _ec( csrf_hash() ) ?>";
    </script>
</head>
<body>
    <div class="header <?php _e(uri("segment", 1)==""||uri("segment", 1)=="product"?"home-page":"")?>">
	    <div class="container h-100">
    		<div class="d-flex justify-content-between align-items-center h-100">
    			<div class="d-flex flex-grow-1 align-items-center h-100">
    				<nav class="navbar navbar-expand-lg me-2 p-0">
    					<button class="navbar-toggler border w-40 h-40 px-1 fs-14 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#header-menu" aria-controls="header-menu">
				            <span class="navbar-toggler-icon"></span>
				        </button>
				    </nav>
    				<div class="logo h-40">
    					<a href="<?php _ec( base_url() )?>"><img src="<?php _ec( get_option("website_logo_color", base_url("assets/img/logo-color.svg")) )?>" class="h-100"></a>
    				</div>
    				
    				<div class="flex-grow-1 h-100">
    					<nav class="header-menu d-flex h-100 navbar navbar-expand-lg">
					        <div class="collapse navbar-collapse justify-content-center" id="header-menu">
					            <ul class="navbar-nav">
					                <li class="nav-item">
					                    <a class="nav-link mx-1 <?php _ec( uri("segment",1)==""?"active":"" )?>" aria-current="page" href="<?php _ec( base_url() )?>"><?php _e("Home")?></a>
					                </li>
					                <?php if ( count($platforms) > 1 ): ?>
					                <li class="nav-item dropdown">
					                    <a class="nav-link mx-1 dropdown-toggle <?php _ec( uri("segment",1)=="product"?"active":"" )?>" href="javascript:void(0);" id="header_sub_menu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
					                        <?php _e("Platforms")?>
					                    </a>
					                    <ul class="dropdown-menu" aria-labelledby="header_sub_menu">
					                    	<?php foreach ($platforms as $platform): ?>
					                        <li>
					                        	<a class="dropdown-item" href="<?php _ec( base_url("product/".$platform->platform_id."/".slugify($platform->name)) )?>">
				                        			<i class="<?php _e($platform->icon)?>" style="color: <?php _e($platform->color)?>;"></i>
					                        		<span><?php _e($platform->name)?></span>
					                        	</a>
					                        </li>
					                    	<?php endforeach ?>
					                    </ul>
					                </li>
					                <?php else: ?>
					                <li class="nav-item">
					                    <a class="nav-link mx-1 click-menu" data-id="features" href="<?php _ec( uri("segment", 1) == ""?"":base_url() )?>#features"><?php _e("Features")?></a>
					                </li>
					                <?php endif ?>
					                <?php if (find_modules("payment")): ?>
					                <li class="nav-item">
					                    <a class="nav-link mx-1 <?php _ec( uri("segment",1)=="pricing"?"active":"" )?>" href="<?php _ec( base_url("pricing") )?>"><?php _e("Pricing")?></a>
					                </li>
					                <?php endif ?>
					                <li class="nav-item">
					                    <a class="nav-link mx-1 <?php _ec( uri("segment",1)=="faqs"?"active":"" )?>" href="<?php _ec( base_url("faqs") )?>"><?php _e("FAQs")?></a>
					                </li>
					                <?php if (find_modules("blog_manager")): ?>
					                <li class="nav-item">
					                    <a class="nav-link mx-1 <?php _ec( uri("segment",1)=="blogs"?"active":"" )?>" href="<?php _ec( base_url("blogs") )?>"><?php _e("Blogs")?></a>
					                </li>
					                <?php endif ?>
					                <li class="nav-item d-block d-md-block d-lg-none">
					                    <a class="nav-link mx-1" href="<?php _ec( base_url("login") )?>"><?php _e("Login")?></a>
					                </li>
					                <li class="nav-item d-block d-md-block d-lg-none">
					                    <a class="nav-link mx-1" href="<?php _ec( base_url("signup") )?>"><?php _e("Sign Up")?></a>
					                </li>
					            </ul>
					        </div>
						</nav>
    				</div>
    			</div>
    			<div class="d-flex align-items-center">
    				<?php $lang_data = load_language();?>
					<?php if (!empty($lang_data) && isset($lang_data['result']) && !empty($lang_data['result'])): ?>
					<?php
					$result = $lang_data['result'];
					$default = $lang_data['default'];
					?>

					<div class="ms-3 header-language">
    					<div class="dropdown">
		                    <a class="dropdown-toggle border b-r-100 w-30 h-30 d-block d-flex align-items-center justify-content-center fs-20" href="#" id="header_lang" role="button" data-bs-toggle="dropdown" aria-expanded="false">
		                        <i class="fal fa-globe"></i>
		                    </a>
		                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="header_lang">
		                    	<?php foreach ($result as $key => $value): ?>
		                        <li>
		                        	<a class="dropdown-item actionItem d-flex justify-content-between align-items-center" href="<?php _ec( base_url("auth/language/".$value->ids) )?>" data-redirect="">
			                        	<span class="me-3"><i class="<?php _ec($value->icon)?>"></i> <?php _ec($value->name)?></span>
			                        	<?php if ($value->code == $default->code): ?>
			                        		<span class="text-primary"><i class="fal fa-check"></i></span>
			                        	<?php endif ?>
			                        </a>
			                    </li>
		                        <?php endforeach ?>
		                    </ul>
		                </div>
    				</div>
					<?php endif ?>
    				
    				<div class="ms-3 d-none d-md-none d-lg-block">
    					<a href="<?php _ec( base_url("login") )?>" class="text-dark"><?php _e("Login")?></a>
    				</div>
    				<div class="ms-3 d-none d-md-none d-lg-block">
    					<a class="btn btn-primary b-r-60 btn-sm" href="<?php _ec( base_url("signup") )?>" ><?php _e("Sign Up")?></a>
    				</div>
    			</div>
    		</div>
    	</div>
	</div>
	<?php if (uri("segment", 1) != "" && uri("segment", 1) != "product"): ?>
		<div class="header-overplay"></div>
	<?php endif ?>

    <div class="mih-1000">
    	<?php _ec( $content )?>
    </div>
    <div class="footer p-t-70 p-b-10">
    	<div class="container">
    		<div class="row">
	    		<div class="col-md-5 mb-5">
	    			<div class="h-40 mb-4">
						<a href="<?php _ec( base_url() )?>"><img src="<?php _ec( get_option("website_logo_color", base_url("assets/img/logo-color.svg")) )?>" class="h-100"></a>
					</div>
					<div class="mw-400 mb-5 text-gray-700"><?php _e("Helping you execute a comprehensive marketing plan, manage your brands by scheduling your posts to optimize performance on many social media platforms")?></div>
	    		</div>
	    		<div class="col-md-7">
	    			<div class="row">
	    				<div class="col-md-4 mb-5">
							<div class="fw-6 fs-18 mb-4"><?php _e("Quick Links")?></div>
							<ul>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( base_url() )?>"><?php _e("Home")?></a></li>
								<?php if ( count($platforms) <= 1 ): ?>
								<li class="mb-2"><a class="text-gray-700 click-menu" data-id="features" href="<?php _ec( uri("segment", 1) == ""?"":base_url() )?>#features"><?php _e("Features")?></a></li>
								<?php endif ?>
								<?php if (find_modules("payment")): ?>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( base_url("pricing") )?>"><?php _e("Pricing")?></a></li>
								<?php endif ?>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( uri("segment", 1) == ""?"":base_url() )?>#faqs"><?php _e("FAQs")?></a></li>
								<?php if (find_modules("blog_manager")): ?>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( base_url("blogs") )?>"><?php _e("Blogs")?></a></li>
								<?php endif ?>
							</ul>
	    				</div>
	    				<div class="col-md-4 mb-5">
	    					<div class="fw-6 fs-18 mb-4"><?php _e("Useful Links")?></div>
							<ul>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( base_url("login") )?>"><?php _e("Login")?></a></li>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( base_url("signup") )?>"><?php _e("Signup")?></a></li>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( base_url("terms_of_service") )?>"><?php _e("Terms of Service")?></a></li>
								<li class="mb-2"><a class="text-gray-700" href="<?php _ec( base_url("privacy_policy") )?>"><?php _e("Privacy Policy")?></a></li>
							</ul>
	    				</div>
	    				<?php if ( 
				            get_option("social_page_facebook", "") != "" ||
				            get_option("social_page_twitter", "") != "" ||
				            get_option("social_page_pinterest", "") != "" ||
				            get_option("social_page_youtube", "") != "" ||
				            get_option("social_page_tiktok", "") != "" ||
			          	  get_option("social_page_instagram", "") != ""
			          	): ?>
			          	<div class="col-md-4 mb-5">
							<div class="fw-6 fs-18 mb-4"><?php _e("Our channels")?></div>
							<ul>
								<?php if (get_option("social_page_facebook", "") != ""): ?>
		                      		<li class="d-inline fs-30 me-3"><a href="<?php _ec( get_option("social_page_facebook", "") )?>" class="text-gray-600"> <i class="fab fa-facebook-f"></i> </a></li>
			                    <?php endif ?>
			                    <?php if (get_option("social_page_twitter", "") != ""): ?>
			                    	<li class="d-inline fs-30 me-3"><a href="<?php _ec( get_option("social_page_twitter", "") )?>" class="text-gray-600"> <i class="fab fa-twitter"></i> </a></li>
			                    <?php endif ?>
			                    <?php if (get_option("social_page_tiktok", "") != ""): ?>
			                    	<li class="d-inline fs-30 me-3"><a href="<?php _ec( get_option("social_page_tiktok", "") )?>" class="text-gray-600"> <i class="fab fa-tiktok"></i> </a></li>
			                    <?php endif ?>
			                    <?php if (get_option("social_page_pinterest", "") != ""): ?>
			                    	<li class="d-inline fs-30 me-3"><a href="<?php _ec( get_option("social_page_pinterest", "") )?>" class="text-gray-600"> <i class="fab fa-pinterest-p"></i> </a></li>
			                    <?php endif ?>
			                    <?php if (get_option("social_page_youtube", "") != ""): ?>
			                    	<li class="d-inline fs-30 me-3"><a href="<?php _ec( get_option("social_page_youtube", "") )?>" class="text-gray-600"> <i class="fab fa-youtube"></i> </a></li>
			                    <?php endif ?>

			                    <?php if (get_option("social_page_instagram", "") != ""): ?>
			                    	<li class="d-inline fs-30 me-3"><a href="<?php _ec( get_option("social_page_instagram", "") )?>" class="text-gray-600"> <i class="fab fa-instagram"></i> </a></li>
			                    <?php endif ?>
							</ul>
						</div>
			          	<?php endif ?>
	    			</div>
	    		</div>
	    	</div>

	    	<div class="row border-top">
	    		<div class="col-12">
	    			<div class="px-3 fs-12 mt-2 text-center">
	    				<?php echo html_entity_decode(get_option('website_copyright', '© 2025-26 <a href="https://mqtechguru.com/" target="_blank" class="text-decoration-none">MQ TECH GURU</a> All Rights Reserved')); ?>
	    			</div>
	    		</div>
	    	</div>
    	</div>
	</div>

    <!--JS-->
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/owlcarousel/js/owl.carousel.min.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/limarquee/limarquee.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/ihavecookies/jquery.ihavecookies.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/pagination/pagination.min.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/aos/aos.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/marquee/marquee.min.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/anime/anime.min.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/countup/odometer.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/js/core.js"></script>

	<?php if (get_option("gdpr_status", 1)): ?>
    <script type="text/javascript">
        $(function(){
            $('body').ihavecookies({
                title:"<?php _e("Cookies & Privacy")?>",
                message:"<?php _e("We use cookies to ensure that we give you the best experience on our website. By clicking Accept or continuing to use our site, you consent to our use of cookies and our privacy policy. For more information, please read our privacy policy.")?>",
                acceptBtnLabel:"<?php _e("Accept cookies")?>",
                advancedBtnLabel:"<?php _e("Customize cookies")?>",
                moreInfoLabel: "<?php _e("More information")?>",
                cookieTypesTitle: "<?php _e("Select cookies to accept")?>",
                fixedCookieTypeLabel: "<?php _e("Necessary")?>",
                fixedCookieTypeDesc: "<?php _e("These are cookies that are essential for the website to work correctly.")?>",
                link: '<?php _ec( base_url("privacy_policy") )?>',
                expires: 30,
                cookieTypes: [
                {
                    type: '<?php _e("Site Preferences")?>',
                    value: 'preferences',
                    description: '<?php _e("These are cookies that are related to your site preferences, e.g. remembering your username, site colours, etc.")?>'
                },
                {
                    type: '<?php _e("Analytics")?>',
                    value: 'analytics',
                    description: '<?php _e("Cookies related to site visits, browser types, etc.")?>'
                },
                {
                    type: '<?php _e("Marketing")?>',
                    value: 'marketing',
                    description: '<?php _e("Cookies related to marketing, e.g. newsletters, social media, etc")?>'
                }
            ],
            });
        });
    </script>
    <?php endif ?>

</body>
</html>