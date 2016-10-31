<?php


if ( !class_exists('Inbound_Expanded_View') ) {

	class Inbound_Expanded_View extends Inbound_Reporting_Templates {

		static $range;

		/**
		*	Initializes class
		*/
		public static function init() {

			/* build timespan for analytics report */
			self::define_range();

			/* add this template to the list of available templates */
			add_filter( 'inbound-analytics/templates' , array( __CLASS__ , 'define_template' ) );

		}

		/**
		*	Given the $_GET['analytics_range'] parameter set the timespan to request analytics data on
		*/
		public static function define_range() {
			if ( !isset( $_GET['range'] ) ) {
				self::$range = 30;
			} else {
				self::$range = $_GET['range'];
			}
		}

		/**
		*	Adds template to list of available templates
		*/
		public static function define_template( $templates ) {

			$templates[ get_class() ] = array (
				'class_name' => get_class(),
				'label' => __('Content Impressions Expanded' , 'inbound-pro' ),
				'report_type' => 'content'
			);

		}

		/**
		*	Loads the analytics template
		*
		*/
		public static function load_template( $args ) {

			?>
				Hello!

			<?php
		}

		/**
		*	Loads data from Inbound Cloud given parameters
		*
		*	@param ARRAY $args
		*/
		public static function load_data() {
			$Inbound_Analytics = new Inbound_Analytics_Connect();
		}

	}

	add_action( 'admin_init' , array( 'Inbound_Expanded_View' , 'init' ) , 10 );

}