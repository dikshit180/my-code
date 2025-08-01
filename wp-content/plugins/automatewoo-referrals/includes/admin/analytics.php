<?php

namespace AutomateWoo\Referrals\Admin;

/**
 * Referrals Analytics.
 * Formerly AutomateWoo > Reports > Referrals.
 *
 * @since    2.7.0
 */
class Analytics {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup' ) );
	}

	/**
	 * Setup Analytics.
	 * Add report items and register scripts.
	 */
	public static function setup() {
		if ( self::is_enabled() ) {
			// Analytics init.
			add_filter( 'woocommerce_analytics_report_menu_items', array( __CLASS__, 'add_report_menu_item' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_script' ) );
		}
	}

	/**
	 * Add "AW Referrals" as a Analytics submenu item.
	 *
	 * @param  array $report_pages  Report page menu items.
	 * @return array
	 */
	public static function add_report_menu_item( $report_pages ) {

		$report_pages[] = array(
			'id'     => 'automatewoo-analytics-referrals',
			'title'  => '<automatewoo-icon aria-label="AutomateWoo"></automatewoo-icon>' . __( 'Referrals', 'automatewoo-referrals' ),
			'parent' => 'woocommerce-analytics',
			'path'   => '/analytics/automatewoo-referrals',
		);
		return $report_pages;
	}

	/**
	 * Register analytics JS.
	 */
	public static function register_script() {
		$script_asset = require untrailingslashit( AW_Referrals()->plugin_path ) . '/assets/js/min/analytics.min.asset.php';

		wp_register_script(
			'automatewoo-analytics-referrals',
			AW_Referrals()->url( '/assets/js/min/analytics.min.js' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Load JS translations.
		wp_set_script_translations( 'automatewoo-analytics-referrals', 'automatewoo-referrals', AW_Referrals()->path( '/languages' ) );

		// Enqueue script.
		wp_enqueue_script( 'automatewoo-analytics-referrals' );
	}

	/**
	 * Whether or not the new Analytics reports are enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$is_enabled = WC()->is_wc_admin_active();

		/**
		 * Whether AutomateWoo's analytics reports should be added to the WooCommerce Analytics menu.
		 *
		 * @filter automatewoo_referrals/admin/analytics_enabled
		 */
		return (bool) apply_filters( 'automatewoo_referrals/admin/analytics_enabled', $is_enabled );
	}
}
