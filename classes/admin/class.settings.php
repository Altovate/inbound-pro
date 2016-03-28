<?php
/**
*
* Pro welcome screen
*
*/

class Inbound_Pro_Settings {
	static $tab; /* placeholder for page currently opened */
	static $settings_fields; /* configuration dataset */
	static $settings_values; /* configuration dataset */

	/**
	*	Load hooks and listners
	*/
	public static function init() {
		self::$tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'inbound-pro-setup';
		self::activation_redirect();
		self::add_hooks();
	}


	/**
	*	Loads hooks and filters
	*/
	public static function add_hooks() {

		/* enqueue js and css */
		add_action( 'admin_enqueue_scripts' , array( __CLASS__ , 'enqueue_scripts' ) );

		/* add ajax listener for setting saves */
		add_action( 'wp_ajax_inbound_pro_update_setting' , array( __CLASS__ , 'ajax_update_settings' ) );

		/* add ajax listener for custom fields setting saves */
		add_action( 'wp_ajax_inbound_pro_update_custom_fields' , array( __CLASS__ , 'ajax_update_custom_fields' ) );

		/* add ajax listener for IP Addresses setting saves */
		add_action( 'wp_ajax_inbound_pro_update_ip_addresses' , array( __CLASS__ , 'ajax_update_ip_addresses' ) );

		/* listen for fast ajax installation */
		add_action( 'inbound-settings/after-field-value-update' , array( __CLASS__ , 'ajax_toggle_fast_ajax' ) , 10 , 1 );
	}

	/**
	*	Enqueue scripts & stylesheets
	*/
	public static function enqueue_scripts() {

		$screen = get_current_screen();

		/* Load assets for inbound pro page */
		if (isset($screen) && $screen->base != 'toplevel_page_inbound-pro' ){
			return;
		}

		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('modernizr');
		wp_enqueue_script('underscore');
		add_thickbox();

		/* load shuffle.js */
		wp_enqueue_script('shuffle', INBOUND_PRO_URLPATH . 'assets/libraries/Shuffle/jquery.shuffle.modernizr.min.js' , array( 'jquery') );

		/* load custom CSS & JS for inbound pro welcome */
		wp_enqueue_style('inbound-settings', INBOUND_PRO_URLPATH . 'assets/css/admin/settings.css');
		wp_enqueue_script('inbound-settings', INBOUND_PRO_URLPATH . 'assets/js/admin/settings.js', array('jquery', 'jquery-ui-sortable') );
		wp_localize_script('inbound-settings', 'inboundSettingsLoacalVars' ,  array('apiURL' => Inbound_API_Wrapper::get_api_url() , 'siteURL' => site_url() ) );

		/* load Ink */
		wp_enqueue_style('Ink', INBOUND_PRO_URLPATH . 'assets/libraries/Ink/css/ink-flex.min.css');

		/* Load selective bootstrap */
		wp_enqueue_style('bootstrap-tooltip', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/BootStrap/css/tooltip.min.css');
		wp_enqueue_script('bootstrap-tooltip', INBOUNDNOW_SHARED_URLPATH . 'assets/includes/BootStrap/js/tooltip.min.js');

		/* load fontawesome */
		wp_enqueue_style('fontawesome', INBOUNDNOW_SHARED_URLPATH . 'assets/fonts/fontawesome/css/font-awesome.min.css');
	}

	/**
	*	Listens for the _inbound_pro_welcome transient and if it exists then redirect to the welcome page
	*/
	public static function activation_redirect() {

		if ( get_transient('_inbound_pro_welcome') ) {
			$redirect = admin_url('admin.php?page=inbound-pro&tab=inbound-pro-welcome');
			header('Location: ' . $redirect);
			delete_transient( '_inbound_pro_welcome' );
			exit;
		}
	}

	/**
	*  Setup Core Settings
	*/
	public static function extend_settings() {

		self::$settings_fields = array(
			'inbound-pro-setup' => array(
				/* add api key group to setup page */
				array(
					'group_name' => 'api-key',
					'keywords' => __('api key' , 'inbound-pro'),
					'fields' => array (
						array (
							'id'	=> 'api-key',
							'type'	=> 'api-key',
							'default'	=> '',
							'placeholder'	=> __( 'Enter api key here' , INBOUNDNOW_TEXT_DOMAIN ),
							'options' => null,
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
					),
				),
				/* add custom lead fields field group to setup page */
				array(
					'group_name' => 'leads-custom-fields',
					'keywords' => __('leads, field mapping, custom fields' , 'inbound-pro'),
					'fields' => array (
						array (
							'id'	=> 'leads-custom-fields',
							'type'	=> 'custom-fields-repeater',
							'default'	=> '',
							'placeholder'	=> null,
							'options' => null,
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
					),
				),
				/* add Analytics exclusion options */
				array(
					'group_name' => 'inbound-analytics-rules',
					'keywords' => __('analytics,tracking,ipaddress,ip address,admin tracking' , 'inbound-pro'),
					'fields' => array (
						array (
							'id'	=> 'analytics-header',
							'type'	=> 'header',
							'default'	=> __( 'Analytics' , INBOUNDNOW_TEXT_DOMAIN ),
							'placeholder'	=> null,
							'options' => false,
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'admin-tracking',
							'type'	=> 'radio',
							'label'	=> __( 'Admin Tracking' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Toggle this to on to prevent impression/conversion tracking for logged in administrators.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'on',
							'placeholder'	=> null,
							'options' => array(
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
							),
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'exclude-ip-addresses',
							'type'	=> 'ip-address-repeater',
							'default'	=> '',
							'placeholder'	=> null,
							'options' => null,
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
					),
				),
				/* add core plugin exclusion options */
				array(
					'group_name' => 'inbound-core-loading',
					'keywords' => __('core,cta,calls to action,lp,landing pages,leads,activate,deactivate' , 'inbound-pro'),
					'fields' => array (
						array (
							'id'	=> 'load-core-comonents-header',
							'type'	=> 'header',
							'default'	=> __( 'Core Components' , INBOUNDNOW_TEXT_DOMAIN ),
							'placeholder'	=> null,
							'options' => false,
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'toggle-landing-pages',
							'type'	=> 'radio',
							'label'	=> __( 'Landing Pages' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Toggle this off to stop loading Landing Pages component.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'on',
							'placeholder'	=> null,
							'options' => array(
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),
							),
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'toggle-calls-to-action',
							'type'	=> 'radio',
							'label'	=> __( 'Calls to Action' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Toggle this off to stop loading Calls to Action component.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'on',
							'placeholder'	=> null,
							'options' => array(
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),

							),
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'toggle-leads',
							'type'	=> 'radio',
							'label'	=> __( 'Leads' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Toggle this off to stop loading Leads component.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'on',
							'placeholder'	=> null,
							'options' => array(
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),

							),
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'toggle-email-automation',
							'type'	=> 'radio',
							'label'	=> __( 'Mailer & Automation' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Toggle this off to stop loading Mailer & Marketing Automation component. These components require an active pro membership.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'on',
							'placeholder'	=> null,
							'options' => array(
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),
							),
							'hidden' => (Inbound_Pro_Plugin::get_customer_status() > 4 ? false : true ),
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'toggle-google-analytics',
							'type'	=> 'radio',
							'label'	=> __( 'Google Analytics' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Toggle this on to load the Google Analytics component. These components require an active pro membership.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'off',
							'placeholder'	=> null,
							'options' => array(
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),
							),
							'hidden' => (Inbound_Pro_Plugin::get_customer_status() > 0 ? false : true ),
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						)
				    )
				),
				/* add core plugin exclusion options */
				array(
					'group_name' => 'inbound-acf',
					'keywords' => __('advanced custom fields,acf' , 'inbound-pro'),
					'fields' => array (
						array (
							'id'	=> 'acf-header',
							'type'	=> 'header',
							'default'	=> __( 'ACF Options' , INBOUNDNOW_TEXT_DOMAIN ),
							'placeholder'	=> null,
							'options' => false,
							'hidden' => (Inbound_Pro_Plugin::get_customer_status() > 1 ? false : true ),
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'toggle-acf-lite',
							'type'	=> 'radio',
							'label'	=> __( 'ACF Lite Mode' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Toggle this off to stop loading Landing Pages component.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'on',
							'placeholder'	=> null,
							'options' => array(
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),
							),
							'hidden' => (Inbound_Pro_Plugin::get_customer_status() > 1 ? false : true ),
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						)
					),
				),
				/* add core plugin exclusion options */
				array(
					'group_name' => 'inbound-fast-ajax',
					'keywords' => __('ajax,speed,optimization' , 'inbound-pro'),
					'fields' => array (
						array (
							'id'	=> 'fast-ajax-header',
							'type'	=> 'header',
							'default'	=> __( 'Fast(er) Ajax' , INBOUNDNOW_TEXT_DOMAIN ),
							'placeholder'	=> null,
							'options' => false,
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						),
						array (
							'id'	=> 'toggle-fast-ajax',
							'type'	=> 'radio',
							'label'	=> __( 'Enable Fast Ajax' , INBOUNDNOW_TEXT_DOMAIN ),
							'description'	=> __( 'Turning this feature will install a mu-plugin that Inbound Now will use to improve ajax response times. We recommend turning this on.' , INBOUNDNOW_TEXT_DOMAIN ),
							'default'	=> 'off',
							'placeholder'	=> null,
							'options' => array(
								'on' => __( 'On' , INBOUNDNOW_TEXT_DOMAIN ),
								'off' => __( 'Off' , INBOUNDNOW_TEXT_DOMAIN ),
							),
							'hidden' => false,
							'reveal' => array(
								'selector' => null ,
								'value' => null
							)
						)
					),
				)
			)
		);

		self::$settings_fields = apply_filters( 'inbound_settings/extend' , self::$settings_fields );

	}

	/**
	*	Render Pro Display
	*/
	public static function display() {

		?>

		<div class="ink-grid">


			<?php
			self::display_nav_menu();

			echo '<section class="column-group gutters article">';

			switch ( self::$tab ) {
				case 'inbound-pro-welcome':
                    echo '<section class="xlarge-70 large-70 medium-60 small-100 tiny-100 welcome-screen-content">';
					self::display_welcome();
                    echo '</section>';
                    echo '<section class="xlarge-30 large-30 medium-30 small-100 tiny-100">';
					self::display_blog_posts();
					self::display_social_ctas();
                    echo '</section>';
					BREAK;
				case 'inbound-pro-settings':
                    echo '<section class="xlarge-70 large-70 medium-60 small-100 tiny-100 welcome-screen-content">';
                    self::display_settings();
                    echo '</section>';
                    echo '<section class="xlarge-30 large-30 medium-30 small-100 tiny-100">';
                    self::display_blog_posts();
                    self::display_social_ctas();
                    echo '</section>';
					BREAK;
				case 'inbound-pro-setup':
					self::display_setup();
					BREAK;
				case 'inbound-my-account':
					self::display_setup();
					BREAK;
			}
            echo '</section>';
			self::display_footer();
			?>
		</div>
		<?php
	}

	/**
	*  Display Pro Welcome Screen
	*/
	public static function display_welcome() {
		self::extend_settings();
		self::$settings_values = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );

		?>


		<h1><?php _e('Getting Started','inbound-pro'); ?></h1>

		<p><?php _e('Welcome to the new Inbound Professional Marketing Suite for WordPress!','inbound-pro'); ?></p>

		<h2><?php _e('What about the old plugins?','inbound-pro'); ?></h2>

		<p><?php echo sprintf( __('This plugin comes with Inbound Now\'s core plugins pre-installed. If you want to control which core components to load at startup you can do so in the %score settings%s area.','inbound-pro') , '<a href="admin.php?tab=inbound-pro-setup&page=inbound-pro&setting=core" target="_blank" >' ,'</a>'); ?></p>

		<h2>Retreiving your API Key</h2>

		<p>Head into your 'my account' area to retrieve your customer API Key. API Keys are setup in the <a href='admin.php?tab=inbound-pro-setup&page=inbound-pro&setting=core' target='_blank' >core settings</a> area and will unlock downloads available to your account. With an <a href='http://inboundsite.wpengine.com/pricing/' target='_blank'>active membership</a> you will have one-click installation access to templates and extensions.</p>

		<h1>What's new?</h1>

		<ul class="features">
			<li><a href='#email'> Email Component</a></li>
			<li><a href='#automation'> Marketing Automation Component</a></li>
			<li><a href='#installation'> Oneclick template/extension installations</a></li>
			<li><a href='#tracking'> Better admin/IP exclusion settings</a></li>
			<li><a href='#mapping'> Better Lead Field Management</a></li>
			<li><a href='#settings'> Better Extension Settings Management</a></li>
		</ul>

		<a name='email'></a>
		<h2>Information about the new eMail component</h2>
		<p>Serving mail has been the intuitive next step for Inbound Now component development. Inside our mail component we provide a framework similar to our Landing Pages tool. You can quickly select and setup emails from free templates provided with the Inbound Pro plugin, or you can have your developer use our templating engine to build your own templates. We have also included variation testing tools that will allow you to split test different versions of your email.</p>

		<p>The email component can also work in Tandem with the new Marketing Automation component to create complex follow up email series.</p>

		<img class="size-full wp-image-138047" src="http://www.inboundnow.com/wp-content/uploads/2012/05/2015-10-13_1831.png" alt="Messing around with test email sends" />


		<h3>Powered By Mandrill</h3>
		Inbound Now's email component is 100% powered by <a href="http://mandrill.com/" target="_blank">Mandrill</a>. Mandrill is a transactional email service powered by the people who run MailChimp. Mandrill provides users with <strong>2000</strong> free email sends and then provides 25,000 email sends a month for a <a href="http://mandrill.com/pricing/" target="_blank">low cost</a>.

		We are investigating adding additional services such as SendGrid, though it's not available yet.

		<h2>The new marketing automation component</h2>

		<p>Our marketing automation component is brand new and still under development. We plan to study how our users leverage it and improve it over the 2016 year. Currently it can only be used to schedule time driven email series. In essence it is a trigger/action rule engine powered by WordPress hooks. Right now there are only a few triggers and actions. We'd like to see this list grow. One day this tool might be used to create a lead rating/badging system.  We are curious about how this component will be leveraged over time.</p>
		<img class="size-full wp-image-138160" src="http://www.inboundnow.com/wp-content/uploads/2012/05/2015-10-14_1507.png" alt="Setting up the trigger"/>

		<img class="size-full wp-image-138161" src="http://www.inboundnow.com/wp-content/uploads/2012/05/2015-10-14_1508.png" alt="Setting up the action!"  />
		<a name='installation'></a>
		<h2>One click extension/theme installations</h2>

		<p>Our new Inbound Pro component will read the permissions of you API key and allow for one-click installations and uninstallations of all Inbound Now templates and plugins. If you have an all inclusive developer account you'll find all assets available to install. If you have access to the designer package then all templates will be available, but you'll still need to go to market to buy your extensions.</p>

		<img class="wp-image-138043 size-full" src="http://www.inboundnow.com/wp-content/uploads/2012/05/2015-10-13_1743.png" alt="One click template installations"  />
		<a name='tracking'></a>
		<h2>Better admin/IP anti-tracking measures</h2>
		<p>By better, we mean they exist now. In your Inbound Pro Settings area you will see a new place where you can disable tracking on admin accounts and disable tracking by IP addresses.</p>

        <img class="size-full wp-image-138044" src="http://www.inboundnow.com/wp-content/uploads/2012/05/2015-10-13_1746.png" alt="Disable tracking by ip and admin"  />
		<a name='mapping'></a>
		<h2>It's now easy to add custom Lead fields</h2>
		<p>In our current setup we have to use PHP code inserts to add non-native lead fields. Now we can do it straight from the settings area. We can also edit the labels of core fields and change the order they appear within a Lead Profile.</p>

		<img class="size-full wp-image-138045" src="http://www.inboundnow.com/wp-content/uploads/2012/05/2015-10-13_1748.png" alt="Add and edit mappable lead fields"  />
		<a name='settings'></a>
		<h2>Improved extension setting management</h2>
		<p>It's now easier to work with extension settings. Quickly view installed extensions and jump right to their settings area at the click of a button. Uninstallation is easy too.</p>

		<img class="aligncenter size-full wp-image-138164" src="http://www.inboundnow.com/wp-content/uploads/2012/05/extension-settings.gif" alt="extension settings"  />
		<?php
			//self::render_fields( 'inbound-pro-welcome' );
		?>
		<?php
	}

	/**
	*	Display Inbound Pro Setup page
	*/
	public static function display_setup() {
		self::extend_settings();
		self::$settings_values = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );

		?>
		<div class="xlarge-100 large-100 medium-100 small-100 tiny-100">
		<?php
			self::render_fields( 'inbound-pro-setup' );
		?>
		</div>
		<?php
	}

	/**
	*	Display Inbound Pro Settings page
	*/
	public static function display_settings() {
		self::extend_settings();
		self::$settings_values = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );

		self::render_fields( 'inbound-pro-settings' );
	}


	/**
	*	Display Sidebar
	*/
	public static function display_sidebar() {


		?>

			<!-- 	Nav to Tools -->
			<?php
            self::display_social_ctas();

            ?>

		<?php
	}

	/**
	 *  Display latest news from Inbound Now
	 */
	public static function display_blog_posts() {
		$blogs = Inbound_API_Wrapper::get_blog_posts();
		?>
        <ul class="unstyled">
            <!--- Show blog posts --->
            <?php
            $i=0;
            $limit = 20;
            foreach ($blogs as $item) {
                if ($i>5) {
                    break;
                }

                $excerpt = explode('The post' ,  $item['description']);
                $excerpt = $excerpt[0];

                ?>
                <div class="all-80 small-50 tiny-50">
                    <h6 class='sidebar-h6'><?php echo $item['title']; ?></h6>
                    <!--<img class="half-bottom-space" src="holder.js/1200x600/auto/ink" alt="">-->
                    <p><a href='<?php echo $item['guid']; ?>' target='_blank'><?php _e( 'Read more &#8594;' , 'inbound-pro'); ?></a></p>
                </div>
                <?php
                $i++;
            }
            ?>
        </ul>
		<?php
	}


	public static function display_social_ctas() {
	       ?>

	        <a href="https://twitter.com/inboundnow" class="twitter-follow-button" data-show-count="false">Follow @inboundnow</a>
                <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
            <iframe src='//www.facebook.com/plugins/likebox.php?href=http%3A%2F%2Fwww.facebook.com%2Finboundnow&amp;width=234&amp;height=65&amp;colorscheme=light&amp;show_faces=false&amp;border_color&amp;stream=false&amp;header=false&amp;appId=364256913591848' scrolling='no' frameborder='0' style='border:none; overflow:hidden; width:234px; height:116px;' allowTransparency='true'>
            </iframe>
	       <?php
	}

	/**
	*	Display Footer
	*/
	public static function display_footer() {
		$docs = Inbound_API_Wrapper::get_docs();
		if (!$docs) {
			$docs = array();
		}
		?>


		<footer class="clearfix pro-footer">
            <div class="ink-grid">
                <ul class="unstyled inline half-vertical-space">
                    <li class="active"><a href="http://www.inboundnow.com" target='_blank'><?php _e( 'Inbound Now' , INBOUNDNOW_TEXT_DOMAIN ); ?></a></li>
                    <li class="active"><a href="http://www.twitter.com/inboundnow" target='_blank'><?php _e( 'Twitter' , INBOUNDNOW_TEXT_DOMAIN ); ?></a></li>
                    <li class="active"><a href="http://www.github.com/inboundnow" target='_blank'><?php _e( 'GitHub' , INBOUNDNOW_TEXT_DOMAIN ); ?></a></li>
                    <li class="active"><a href="http://support.inboundnow.com" target='_blank'><?php _e( 'Support' , INBOUNDNOW_TEXT_DOMAIN ); ?></a></li>
                    <li class="active"><a href="http://docs.inboundnow.com" target='_blank'><?php _e( 'Documentation' , INBOUNDNOW_TEXT_DOMAIN ); ?></a></li>
                    <li class="active"><a href="http://www.inboundnow.com/translate-inbound-now/" target='_blank'><?php _e( 'Translations' , INBOUNDNOW_TEXT_DOMAIN ); ?></a></li>
                </ul>
            </div>
        </footer>
		<?php
	}

	/**
	*	Render About InboundNow Nav
	*/
	static function display_nav_menu() {

		$pages_array = array(
			'inbound-pro-setup' => __( 'Core Settings' , INBOUNDNOW_TEXT_DOMAIN ),
			'inbound-pro-settings' => __( 'Extension Settings' , INBOUNDNOW_TEXT_DOMAIN ),
			'inbound-pro-welcome' => __( 'Quick Start' , INBOUNDNOW_TEXT_DOMAIN ),
			'inbound-my-account' => __( 'My Account' , INBOUNDNOW_TEXT_DOMAIN )
		);

		$pages_array = apply_filters( 'inbound_pro_nav' , $pages_array );

		echo '<header class="vertical-space">';
		echo '	<h1><img src="'. INBOUND_PRO_URLPATH . 'assets/images/logos/inbound-now-logo.png" style="width:262px;" title="' . __( 'Inbound Now Professional Suite' , INBOUNDNOW_TEXT_DOMAIN ) .'"></h1>';
		echo ' 	<nav class="ink-navigation">';
		echo ' 		<ul class="menu horizontal black">';

		foreach ($pages_array as $key => $value) {

			if ($key=='inbound-my-account') {
				echo '<li class=""><a target="_blank" href="https://www.inboundnow.com/account">'.$value.'</a></li>';
				continue;
			}

			$active = ( self::$tab === $key) ? 'active' : '';
			echo '<li class="'.$active.'"><a href="'.esc_url(admin_url(add_query_arg( array( 'tab' => $key , 'page' => 'inbound-pro' ), 'admin.php' ) ) ).'">';
			echo $value;
			echo '</a>';
			echo '</li>';
		}

		echo '		</ul>';
		echo '	</nav>';
		echo '</header>';

	}

	/**
	*  Displays search filter
	*/
	public static function display_search() {
		?>
		<div class="">
				<div class='templates-filter-group'>
					<input class="filter-search" type="search" placeholder="<?php _e(' Filter Options... ' , INBOUNDNOW_TEXT_DOMAIN ); ?>">
				</div>
			</div>

		<?php
	}

	/**
	*  Renders settings given a fields dataset
	*  @param STRING $fieldgroup_key identification key for which field group to render
	*/
	public static function render_fields( $page ) {
		echo '<div class="wrap">';
		/* render search filter */
		self::display_search();

		echo '	<div id="grid" class="container-fluid">';

		self::$settings_fields[ $page ] = (isset(self::$settings_fields[ $page ])) ? self::$settings_fields[ $page ] : array();

		foreach( self::$settings_fields[ $page ] as $priority => $group ) {
			echo '<div class="inbound-settings-group " data-keywords="'.$group['keywords'].','.$group['group_name'].'" data-group-name="'.$group['group_name'].'">';
			foreach( $group['fields'] as $field ) {

				/* get value if available else set to default */
				$field['default'] =  (isset($field['default'])) ? $field['default'] : '';
				$field['class'] =  (isset($field['class'])) ? $field['class'] : '';
				$field['value'] = (isset(self::$settings_values[ $group['group_name'] ][ $field['id'] ])) ? self::$settings_values[ $group['group_name'] ][ $field['id'] ] : $field['default'];

				/* rend field */
				self::render_field( $field , $group );

			}

			echo '</div>';
		}
		echo '  </div>';
		echo '</div>';
	}

	/**
	*  Renders label and input given field dataset
	*  @param ARRAY $field dataset containing field information
	*  @param ARRAY $group dataset containing fieldgroup information
	*/
	public static function render_field( $field , $group ) {

		if (isset($field['reveal']) && is_array($field['reveal'])) {
			$data = ' data-reveal-selector="'.$field['reveal']['selector'].'" data-reveal-value="'.$field['reveal']['value'].'" ';
		} else {
			$data = '';
		}

		/* prepare additional classes */
		if ( isset($field['hidden']) && $field['hidden'] ) {
			$field['class'] = $field['class'] . ' hidden';
		}

		/* run class variable through filter */
		$field['class'] = apply_filters('inbound-settings/field/class', $field['class'] );

		echo '<div class="inbound-setting '.$field['class'].' " '.$data.' data-field-id="'.$field['id'].'" id="field-'.$field['id'].'">';
		switch($field['type']) {
			// text
			case 'api-key':

				echo '<div class="api-key">';
				echo '	<label>'.__('Inbound API Key:' , INBOUNDNOW_TEXT_DOMAIN ) .'</label>';
				echo '		<input type="text" class="api" name="'.$field['id'].'" id="'.$field['id'].'" placeholder="'.$field['placeholder'].'" value="'.$field['value'].'" data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'"  data-special-handler="true"/>';
				echo '</div>';
				break;
			case 'header':
				$extra = (isset($field['default'])) ? $field['default'] : '';
				echo '<h3 class="inbound-header">'.$extra.'</h3>';
				break;
			case 'oauth-button':
				$data = '';
				$oauth_class = '';
				$unauth_class = '';

				/* build data attriubtes */
				foreach ( $field['oauth_map'] as $key => $selector ) {
					$data .= " data-".$key."='".$selector."'";
				}

				/* discover if access key and access token already exists */
				if (isset(self::$settings_values[ $group['group_name'] ][ 'oauth' ])) {
					$params['action'] = 'revoke_oauth_tokens';
					$params['group'] = $group['group_name'];
					$oauth_class = "hidden";
					$unauth_class = "";
				} else {
					$oauth_class = "";
					$unauth_class = "hidden";
				}
				echo '<button class="ink-button orange unauth thickbox '.$unauth_class.'" id="'.$field['id'].'" data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'" '.$data.' >'.__( 'Un-Authorize' , INBOUNDNOW_TEXT_DOMAIN ).'</button>';
				$class = 'hidden';

				$params['action'] = 'request_access_token';
				$params['group'] = $group['group_name'];
				$params['oauth_urls'] = $field['oauth_urls'];
				$params['oauth_map'] = $field['oauth_map'];
				$params['TB_iframe'] = 'true';
				$params['width'] = '800';
				$params['height'] = '500';

				echo '<a href="'. admin_url( add_query_arg( $params , 'admin.php' ) ) .'"  class="ink-button green oauth thickbox '.$oauth_class.'" id="'.$field['id'].'" data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'" '.$data.' >'.__( 'Authorize' , INBOUNDNOW_TEXT_DOMAIN ).'</a>';


				break;
			case 'text':
				echo '<div class="inbound-field">';
				echo '	<div class="inbound-label-field">';
				echo '		<label>'.$field['label'] .'</label>';
				echo '	</div>';
				echo '	<div class="inbound-text-field">';
				echo '		<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" placeholder="'.( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) .'"  value="'.$field['value'].'" size="30"  data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'"/>';
				echo '	</div>';
				echo '	<div class="inbound-tooltip-field">';
				echo '		<i class="inbound-tooltip fa fa-question-circle tool_text" title="'.$field['description'].'"></i>';
				echo '	</div>';
				echo '</div>';
				BREAK;
			// ol
			case 'ol':
				echo '<div class="inbound-field">';
				echo '	<div class="inbound-label-field">';
				echo '		<label><strong>'.$field['label'] .'</strong></label>';
				echo '	</div><br>';
				echo '	<div class="inbound-ol-field">';
				echo '		<ol>';
				foreach ($field['options'] as $option) {
					echo '		<li>'. $option. '</li>';
				}
				echo '		</ol>';
				echo '	</div>';
				echo '</div>';
				BREAK;
			// ul
			case 'ul':
				echo '<div class="inbound-field">';
				echo '	<div class="inbound-label-field">';
				echo '		<label><strong>'.$field['label'] .'</strong></label>';
				echo '	</div><br>';
				echo '	<div class="inbound-ul-field">';
				echo '		<ul>';
				foreach ($field['options'] as $option) {
					echo '		<li>'. $option. '</li>';
				}
				echo '		</ul>';
				echo '	</div>';
				echo '</div>';
				BREAK;
			// textarea
			case 'textarea':
				echo '<textarea name="'.$field['id'].'" id="'.$field['id'].'" cols="106" rows="6"  data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'">'.$field['value'].'</textarea>
						<i class="inbound-tooltip fa-question-circle tool_textarea" title="'.$field['description'].'"></i>';
				break;
			// wysiwyg
			case 'wysiwyg':
				wp_editor( $field['value'], $field['id'], $settings = array() );
				echo	'<span class="description">'.$field['description'].'</span><br><br>';
				break;
			// media
				case 'media':

				echo '<label for="upload_image">';
				echo '<input name="'.$field['id'].'"	id="'.$field['id'].'" type="text" size="36" name="upload_image" value="'.$field['value'].'"  data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'"/>';
				echo '<input class="upload_image_button" id="uploader_'.$field['id'].'" type="button" value="Upload Image" />';
				echo '<br /><i class="inbound-tooltip fa-question-circle tool_media" title="'.$field['description'].'"></i>';
				break;
			// checkbox
			case 'checkbox':
				$i = 1;
				echo "<table>";
				if (!isset($field['value'])){$field['value']=array();}
				elseif (!is_array($field['value'])){
					$field['value'] = array($field['value']);
				}
				foreach ($field['options'] as $value=>$label) {
					if ($i==5||$i==1)
					{
						echo "<tr>";
						$i=1;
					}
						echo '<td><input type="checkbox" name="'.$field['id'].'[]" id="'.$field['id'].'" value="'.$value.'" ',in_array($value,$field['value']) ? ' checked="checked"' : '','  data-field-type="'.$field['type'].'"  data-field-group="'.$group['group_name'].'"/>';
						echo '<label for="'.$value.'">&nbsp;&nbsp;'.$label.'</label></td>';
					if ($i==4)
					{
						echo "</tr>";
					}
					$i++;
				}
				echo "</table>";
				echo '<br><i class="inbound-tooltip fa-question-circle tool_checkbox" title="'.$field['description'].'"></i>';
			break;
			// radio
			case 'radio':

				echo '<div class="inbound-field">';
				echo '	<div class="inbound-label-field">';
				echo '		<label>'.$field['label'] .'</label>';
				echo '	</div>';

				echo '	<div class="inbound-radio-field">';
				foreach ($field['options'] as $value=>$label) {
					//echo $meta.":".$field['id'];
					//echo "<br>";
					echo '<input type="radio" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$value.'" ',$field['value']==$value ? ' checked="checked"' : '','  data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'"/>';
					echo '<label for="'.$value.'">&nbsp;&nbsp;'.$label.'</label> &nbsp;&nbsp;&nbsp;&nbsp;';
				}
				echo '	</div>';

				echo '	<div class="inbound-tooltip-field">';
				echo '		<br /><i class="inbound-tooltip fa fa-question-circle tool_dropdown" title="'.$field['description'].'"></i>';
				echo '	</div>';
				echo '</div>';


			break;
			// select
			case 'dropdown':
				echo '<div class="inbound-field">';
				echo '	<div class="inbound-label-field">';
				echo '		<label>'.$field['label'] .'</label>';
				echo '	</div>';
				echo '	<div class="inbound-dropdown-field">';

				echo '		<select name="'.$field['id'].'" id="'.$field['id'].'"  data-field-type="'.$field['type'].'" data-field-group="'.$group['group_name'].'">';

							foreach ($field['options'] as $value=>$label) {
								echo '		<option', $field['value'] == $value ? ' selected="selected"' : '', ' value="'.$value.'">'.$label.'</option>';
							}

				echo '		</select>';
				echo '	</div>';
				echo '	<div class="inbound-tooltip-field">';
				echo '		<br /><i class="inbound-tooltip fa fa-question-circle tool_dropdown" title="'.$field['description'].'"></i>';
				echo '	</div>';
				echo '</div>';
			break;
			case 'html':
				echo $field['value'];
				echo ( !empty($field['description']) ) ? $field['description'] : '';

			break;
			case 'custom-fields-repeater':
				$fields = Leads_Field_Map::get_lead_fields();
				$fields = Leads_Field_Map::prioritize_lead_fields( $fields );

				$field_types = Leads_Field_Map::build_field_types_array();

				echo '<div class="repeater-custom-fields">';
				echo '	<h4>'.__('Manage Lead Fields:' , INBOUNDNOW_TEXT_DOMAIN ) .'</h4>';

				echo '		<div class="map-row-headers column-group">';
				echo '			<div class="map-key-header all-5">';
				echo '				<th> </th>';
				echo '			</div>';
				echo '			<div class="map-key-header all-25">';
				echo '				<th>' . __( 'Field Key' , INBOUNDNOW_TEXT_DOMAIN ) .'</th>';
				echo '			</div>';
				echo '			<div class="map-key-header all-30">';
				echo '			<th>' . __( 'Field Label' , INBOUNDNOW_TEXT_DOMAIN ) .'</th>';
				echo '			</div>';
				echo '			<div class="map-key-header all-20">';
				echo '			<th>' . __( 'Field Type' , INBOUNDNOW_TEXT_DOMAIN ) .'</th>';
				echo '			</div>';
				echo '			<div class="map-key-header all-15">';
				echo '			<th>' . __( 'Action' , INBOUNDNOW_TEXT_DOMAIN ) .'</th>';
				echo '			</div>';
				echo ' 		</div>';

				echo ' 	<form data="'.$field['type'].'" id="custom-fields-form">';
				echo ' 	<ul class="field-map" id="field-map">';


				foreach( $fields as $key => $field ) {

					echo '	<li class="map-row column-group"  data-priority="'.$key.'">';
					echo '		<div class="map-handle all-5">';
					echo '			<span class="drag-handle">';
					echo '				<i class="fa fa-arrows"></i>';
					echo '			</span>';
					echo '		</div>';
					echo '		<div class="map-key all-25">';
					echo '				<input type="hidden" class="field-priority" name="fields['.$field['key'].'][priority]" value="'.$key.'">';
					echo '				<input type="text" class="field-key" data-special-handler="true" data-field-type="mapped-field" name="fields['.$field['key'].'][key]" value="'.$field['key'].'" '. ( isset($field['nature']) && $field['nature'] == 'core' ? 'disabled' : '' ) .' required>';
					echo '		</div>';
					echo '		<div class="map-label all-30">';
					echo '				<input type="text" class="field-label" data-special-handler="true" data-field-type="mapped-field"  name="fields['.$field['key'].'][label]" value="'.$field['label'].'" required>';
					echo '		</div>';
					echo '		<div class="map-label all-20">';
					echo '				<select type="text" class="field-type" data-special-handler="true" data-field-type="mapped-field"  name="fields['.$field['key'].'][type]">';

					foreach ( $field_types as $type => $label ) {
						echo 				'<option value="'.$type.'" '.( isset($field['type']) && $field['type'] == $type ? 'selected="selected"' : '' ).'>'.$label.'</option>';
					}

					echo '				</select>';
					echo '		</div>';
					echo '		<div class="map-actions all-10">';

					echo '			<div class="edit-btn-group ">';
					echo '				<span class="ink-button red delete-custom-field '.( !isset($field['nature']) || $field['nature'] != 'core'  ? '' : 'hidden' ).'" id="remove-field">'.__( 'remove' , ' inbound-pro' ).'</span>';
					echo '			</div>';
					echo '			<div class="edit-btn-group ">';
					echo '				<span class="ink-button red delete-custom-field-confirm hidden" id="remove-field-confirm">'.__( 'confirm removal' , ' inbound-pro' ).'</span>';
					echo '			</div>';

					echo '		</div>';
					echo '	</li>';
				}

				echo '	</ul>';
				echo '	</form>';
				echo '	<form id="add-new-custom-field-form">';
				echo '	<div class="map-row-addnew column-group">';
				echo '		<div class="map-handle all-5 ">';
				echo '			<span class="drag-handle">';
				echo '				<i class="fa fa-arrows"></i>';
				echo '			</span>';

				echo '		</div>';
				echo '		<div class="map-key all-25">';
				echo '				<input type="text"  name="fields[new][key]" data-special-handler="true" id="new-key" placeholder="'.__('Enter field key here' , INBOUNDNOW_TEXT_DOMAIN ).'" required>';
				echo '		</div>';
				echo '		<div class="map-label all-30">';
				echo '				<input type="text" name="fields[new][label]" data-special-handler="true" id="new-label" placeholder="'.__('Enter field label here' , INBOUNDNOW_TEXT_DOMAIN ).'" required>';
				echo '		</div>';

				echo '		<div class="map-label all-20">';
				echo '				<select type="text" class="field-type" data-special-handler="true" id="new-type"  name="fields[new][type]">';
				foreach ( $field_types as $type => $label ) {
					echo 				'<option value="'.$type.'">'.$label.'</option>';
				}
				echo '				</select>';
				echo '		</div>';
				echo '		<div class="map-actions all-30">';
				echo '			<div class="edit-btn-group">';
				echo '				<button type="submit" class="ink-button blue" id="add-custom-field">'.__( 'add new field' , ' inbound-pro' ).'</button>';
				echo '			</div>';
				echo '		</div>';
				echo '	</div>';
				echo '	</form>';
				echo '</div>';
				BREAK;

			case 'ip-address-repeater':
				self::$settings_values = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );
				$ip_addresses = ( isset(self::$settings_values['inbound-analytics-rules']['ip-addresses']) && self::$settings_values['inbound-analytics-rules']['ip-addresses'] ) ? self::$settings_values['inbound-analytics-rules']['ip-addresses'] : array('') ;

				echo '<div class="repeater-ip-addresses">';
				echo '	<h4>'.__('Exclude IPs from Tracking:' , INBOUNDNOW_TEXT_DOMAIN ) .'</h4>';
				echo ' 	<form data="'.$field['type'].'" id="ip-addresses-form">';
				echo ' 		<ul class="field-ip-addresses" id="field-ip-address">';


				foreach( $ip_addresses as $key => $ip_address ) {

					echo '		<li class="ip-address-row column-group '. ( !$ip_address ? 'hidden' : '' ) .'"  data-priority="'.$key.'">';
					echo '			<div class="ip-address all-70">';
					echo '				<input type="ip-address" class="field-ip-address" data-special-handler="true" data-field-type="ip-address" name="ip-addresses[]" value="'.$ip_address.'" required>';
					echo '			</div>';
					echo '			<div class="ip-address-actions all-20">';

					echo '				<div class="edit-btn-group ">';
					echo '					<span class="ink-button red delete-ip-address '.( !isset($field['nature']) || $field['nature'] != 'core'  ? '' : 'hidden' ).'" id="remove-field">'.__( 'remove' , ' inbound-pro' ).'</span>';
					echo '				</div>';
					echo '				<div class="edit-btn-group ">';
					echo '					<span class="ink-button red delete-ip-address-confirm hidden" id="remove-ip-confirm">'.__( 'confirm removal' , ' inbound-pro' ).'</span>';
					echo '				</div>';

					echo '			</div>';
					echo '		</li>';
				}

				echo '		</ul>';
				echo '	</form>';

				/* add new ip address html */
				echo '	<form id="add-new-ip-address-form">';
				echo '		<div class="ip-address-row-addnew column-group">';
				echo '			<div class="ip-address all-80">';
				echo '					<input type="text"  name="ip-addresses" data-special-handler="true" id="new-ip-address" placeholder="'.__('Enter IP Address ' , INBOUNDNOW_TEXT_DOMAIN ).'" required>';
				echo '			</div>';
				echo '			<div class="ip-address-actions all-20">';
				echo '				<div class="edit-btn-group">';
				echo '					<button type="submit" class="ink-button blue" id="add-ip-address">'.__( 'add new ip address' , ' inbound-pro' ).'</button>';
				echo '				</div>';
				echo '			</div>';
				echo '		</div>';
				echo '	</form>';

				echo '</div>';
			break;
		} //end switch
		echo '</div>';

    }

	/**
	*  Ajax listener for saving updated field data
	*/
	public static function ajax_update_settings() {
		/* parse string */
		parse_str($_POST['input'] , $data );

		/* Update Setting */
		$settings = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );
		$settings[ $data['fieldGroup'] ][ $data['name'] ] = $data['value'];
		Inbound_Options_API::update_option( 'inbound-pro' , 'settings' , $settings );

		do_action('inbound-settings/after-field-value-update' , $data );
	}

	/**
	*  Ajax listener for saving updated custom field data
	*/
	public static function ajax_update_custom_fields() {
		/* parse string */
		parse_str($_POST['input'] , $data );

		/* Update Setting */
		$settings = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );
		$settings[ 'leads-custom-fields' ] =  $data;

		Inbound_Options_API::update_option( 'inbound-pro' , 'settings' , $settings );
	}

	/**
	*  Ajax listener for saving updated ip addresses to not track
	*/
	public static function ajax_update_ip_addresses() {
		/* parse string */
		parse_str($_POST['input'] , $data );

		$ip_addresses = array_filter($data['ip-addresses']);
		$ip_addresses = array_map('trim',$ip_addresses);

		/* Update Setting */
		$settings = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );
		$settings['inbound-analytics-rules'][ 'ip-addresses' ] = $ip_addresses;

		Inbound_Options_API::update_option( 'inbound-pro' , 'settings' , $settings );
	}

	/**
	 * listen for request to install / uninstall fast ajax
	 */
	public static function ajax_toggle_fast_ajax( $data ) {

		if ( !isset($data['toggle-fast-ajax']) ) {
			return;
		}

		switch ( $data['toggle-fast-ajax'] ) {
			case "on":
				self::install_mu_plugin_fast_ajax();
				break;
			case "off":
				self::install_mu_plugin_fast_ajax( true );
				break;
		}
	}

	/**
	 * Installs fast-ajax.php mu plugin
	 */
	public static function install_mu_plugin_fast_ajax( $delete = false ) {

		$mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
		$mu_dir = untrailingslashit( $mu_dir );

		$source = INBOUND_PRO_PATH . 'assets/mu-plugins/fast-ajax.php';
		$dest = $mu_dir . '/fast-ajax.php';

		$result = array( 'status' => 'OK', 'error' => '' );

		if ( file_exists( $dest ) && !unlink( $dest ) ) {
			$result['error'] = sprintf(
				__( '<strong>Error!</strong> Could not remove the Nelio\'s performance MU-Plugin from <code>%s</code>.', 'nelioab' ),
				$dest );
			$result['status'] = 'ERROR';
		} else {

			/* delete only */
			if ($delete) {
				return;
			}

			// INSTALL
			if ( !wp_mkdir_p( $mu_dir ) ) {
				$result['error'] = sprintf(
					__( '<strong>Error!</strong> The following directory could not be created: <code>%s</code>.', 'nelioab' ),
					$mu_dir );
				$result['status'] = 'ERROR';
			}
			if ( $result['status'] !== 'ERROR' && !copy( $source, $dest ) ) {
				$result['error'] = sprintf(
					__( '<strong>Error!</strong> Could not copy Nelio\'s performance MU-Plugin from <code>%1$s</code> to <code>%2$s</code>.', 'nelioab' ),
					$source, $dest );
				$result['status'] = 'ERROR';
			}

			/* if there was an error installing set to off */
			if ($result['status'] == 'ERROR') {
				/* Update Setting */
				$settings = Inbound_Options_API::get_option( 'inbound-pro' , 'settings' , array() );
				$settings[ $data['fieldGroup'] ][ $data['name'] ] = 'Off';
				Inbound_Options_API::update_option( 'inbound-pro' , 'settings' , $settings );
			}
		}


		header( 'Content-Type: application/json' );
		echo json_encode( $result );
		die();
	}
}

Inbound_Pro_Settings::init();