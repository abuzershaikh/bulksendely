<!DOCTYPE html>
<html class="h-100">
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
    <link rel="stylesheet" type="text/css" href="<?php _ec( get_frontend_url() )?>Assets/css/style.css">



    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=DM Sans:wght@400;500;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Romy:wght@400&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=SF Pro Display:wght@400&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=SF Pro Text:wght@600&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Plus Jakarta Sans:wght@500;700&display=swap"
    />
    <script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/jquery/jquery.min.js"></script>
    <script type="text/javascript">
        var PATH  = '<?php _ec( base_url()."/" )?>';
        var csrf = "<?php _ec( csrf_hash() ) ?>";
    </script>
</head>
<body class="h-100">

	<div class="d-flex w-100 h-100">
		
		<div class="d-flex flex-grow-1 h-100 login-form">

			<div class="m-auto d-flex flex-column h-100 w-100">
				<div class="mb-auto px-5 py-5">
					<a href="<?php _ec( base_url() )?>"><img src="<?php _ec( get_option("website_logo_color", base_url("assets/img/logo-color.svg")) )?>" class="mh-40"></a>
				</div>
				<div class="mb-auto login-form-box mx-auto p-50">
					<?php _ec( $content )?>
				</div>
				<div class="px-2 py-3 mx-auto d-flex fs-12 fw-6 text-gray-500">
					<div class="mx-2">
						<a href="<?php _ec( base_url("terms_of_service") )?>"><?php _e("Terms of Service")?></a>
					</div>
					<div class="fs-15 fw-9 position-relative mn-6 mx-1">.</div>
					<div class="mx-2">
						<a href="<?php _ec( base_url("privacy_policy") )?>"><?php _e("Privacy Policy")?></a>
					</div>
				</div>
			</div>

		</div>
		<div class="flex-grow-1 h-100 login-slogan border-left d-flex justify-content-center align-items-center bg-light-primary" >
			<div class="h-100 mih-600 text-white text-center d-flex justify-content-center align-items-center">
			    <div class="p-50 m-auto">
			    	<img src="<?php _ec( get_frontend_url() )?>Assets/images/login.png">
			        <h1 class="mb-4 text-dark fs-40 fw-5 mw-700 mx-auto login-slogan-head"><?php _e("Utilize one app to <span>Achieve</span> multiple <span>Goals</span>", 0)?></h1>
			        <div class="text-gray-700 fs-20 fw-5 mw-500 m-auto login-slogan-desc">
			        	<i class="fad fa-quote-left text-primary"></i>
			        	<?php _e("Maximize efficiency with our automation features, and measure the success of your strategy using real-time analytics and insights.")?>
			        	<i class="fad fa-quote-right text-primary"></i>
			        </div>
			    </div>
			</div>
		</div>
	</div>

    <!--JS-->
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/limarquee/limarquee.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/ihavecookies/jquery.ihavecookies.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/pagination/pagination.min.js"></script>
	<script type="text/javascript" src="<?php _ec( get_frontend_url() )?>Assets/plugins/aos/aos.js"></script>
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


