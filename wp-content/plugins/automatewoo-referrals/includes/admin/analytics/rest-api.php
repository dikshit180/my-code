<?php

namespace AutomateWoo\Referrals\Admin\Analytics;

use AutomateWoo\Referrals\Admin\Analytics;

/**
 * AutomateWoo Referrals Analytics.
 * Formerly AutomateWoo > Reports > Referrals.
 *
 * @since 2.7.0
 */
class Rest_API {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup' ) );
	}

	/**
	 * Setup Analytics.
	 * Register controllers and data stores.
	 */
	public static function setup() {
		if ( self::is_enabled() ) {
			add_filter( 'woocommerce_admin_rest_controllers', array( __CLASS__, 'add_rest_api_controllers' ) );
			add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ) );
		}
	}

	/**
	 * Whether or not the Rest APIs for Analytic reports are enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return Analytics::is_enabled();
	}

	/**
	 * Adds Analytics REST contoller.
	 * To be used with `woocommerce_admin_rest_controllers` filter.
	 *
	 * @param  array $controllers
	 * @return array Extended with AW Referrals Analytics controller.
	 */
	public static function add_rest_api_controllers( $controllers ) {
		$controllers[] = Rest_API\Referrals\Stats_Controller::class;

		return $controllers;
	}

	/**
	 * Register Analytics data store.
	 * To be used with `woocommerce_data_stores` filter.
	 *
	 * @param  array $stores
	 * @return array Extended with AW Referrals Analytics store.
	 */
	public static function register_data_stores( $stores ) {
		$stores['report-referrals-stats'] = Rest_API\Referrals\Stats_Store::class;

		return $stores;
	}
}
