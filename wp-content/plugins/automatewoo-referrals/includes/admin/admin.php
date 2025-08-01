<?php

namespace AutomateWoo\Referrals;

use AutomateWoo;
use AutomateWoo\HPOS_Helper;
use AutomateWoo\Referrals\Admin\Analytics;
use AutomateWoo\Referrals\Admin\Analytics\Rest_API;

/**
 * Class to Initialize wp-admin functionality.
 *
 * @class Admin
 */
class Admin {

	/**
	 * Constructor.
	 * Initializes Analytics, adds menu pages & screens, dashboard chart widgets, enqueues scripts, etc.
	 */
	public function __construct() {
		Analytics::init();

		add_action( 'automatewoo/admin/submenu_pages', [ $this, 'admin_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_filter( 'automatewoo/settings/tabs', [ $this, 'settings_tab' ] );
		// Technically, we don't need this condition, as the hook will not be called by AW, if HPOS is enabled.
		// But we can have it to be well secured and have a reference what to remove once HPOS will be always on.
		if ( HPOS_Helper::is_HPOS_enabled() ) {
			add_filter( 'automatewoo/reports/tabs', [ $this, 'reports_tab' ] );
		}
		add_filter( 'automatewoo/admin/screen_ids', [ $this, 'register_screen_id' ] );
		add_filter( 'automatewoo/dashboard/chart_widgets', [ $this, 'dashboard_chart_widgets' ] );
		add_filter( 'automatewoo/admin/controllers/includes', [ $this, 'filter_controller_includes' ] );

		add_action( 'admin_head', [ $this, 'menu_referrals_count' ] );

		// email preview
		add_filter( 'automatewoo/email_preview/subject', [ $this, 'email_preview_subject' ], 10, 2 );
		add_action( 'automatewoo/email_preview/html', [ $this, 'email_preview_html' ], 10, 2 );
		add_action( 'automatewoo/email_preview/send_test', [ $this, 'email_preview_send_test' ], 10, 3 );
		add_action( 'automatewoo/email_preview/template', [ $this, 'email_preview_template' ], 10, 3 );

		add_filter( 'plugin_action_links_' . plugin_basename( AW_Referrals()->file ), [ $this, 'plugin_action_links' ] );
	}


	/**
	 * Add AW submenu pages.
	 *
	 * @param string $slug
	 */
	public function admin_pages( $slug ) {
		$sub_menu = [];

		$sub_menu['referrals'] = [
			'page_title' => __( 'Referrals', 'automatewoo-referrals' ),
			'menu_title' => __( 'Referrals', 'automatewoo-referrals' ),
		];

		$sub_menu['referral-advocates'] = [
			'page_title' => __( 'Referral advocates', 'automatewoo-referrals' ),
			'menu_title' => __( 'Advocates', 'automatewoo-referrals' ),
		];

		$sub_menu['referral-codes'] = [
			'page_title' => __( 'Referral codes', 'automatewoo-referrals' ),
			'menu_title' => __( 'Referral codes', 'automatewoo-referrals' ),
		];

		$sub_menu['referral-invites'] = [
			'page_title' => __( 'Referral invites', 'automatewoo-referrals' ),
			'menu_title' => __( 'Invites', 'automatewoo-referrals' ),
		];

		foreach ( $sub_menu as $key => $item ) {

			add_submenu_page(
				$slug,
				$item['page_title'],
				$item['menu_title'],
				'manage_woocommerce',
				'automatewoo-' . $key,
				[ 'AutomateWoo\Admin', 'load_controller' ]
			);

		}
	}


	/**
	 * Enqueues scripts and styles.
	 */
	public function enqueue_scripts() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$suffix = '';
			$dir    = '';
		} else {
			$suffix = '.min';
			$dir    = 'min/';
		}

		wp_register_style( 'automatewoo-referrals-admin', AW_Referrals()->url( '/assets/css/automatewoo-referrals-admin.css' ), [], AW_Referrals()->version );
		wp_register_script(
			'automatewoo-referrals-admin',
			AW_Referrals()->url( "/assets/js/{$dir}automatewoo-referrals-admin{$suffix}.js" ),
			[ 'automatewoo' ],
			AW_Referrals()->version,
			[
				'in_footer' => true,
			]
		);

		if ( in_array( $screen_id, AW()->admin->screen_ids(), true ) ) {
			wp_enqueue_style( 'automatewoo-referrals-admin' );
			wp_enqueue_script( 'automatewoo-referrals-admin' );
		}
	}


	/**
	 * Adds the settings tab.
	 * `automatewoo/settings/tabs` callback.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function settings_tab( $tabs ) {
		$tabs[] = AW_Referrals()->path( '/includes/admin/settings-tab.php' );
		return $tabs;
	}


	/**
	 * Adds the reports tab.
	 * `automatewoo/reports/tabs` callback.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function reports_tab( $tabs ) {
		$tabs[] = AW_Referrals()->path( '/includes/admin/reports-tab.php' );
		return $tabs;
	}


	/**
	 * Adds screen IDs to the list of AutomateWoo screen IDs.
	 * `automatewoo/admin/screen_ids` callback.
	 *
	 * @param array $ids
	 * @return array
	 */
	public function register_screen_id( $ids ) {
		$ids[] = 'automatewoo_page_automatewoo-referrals';
		$ids[] = 'automatewoo_page_automatewoo-referral-advocates';
		$ids[] = 'automatewoo_page_automatewoo-referral-invites';
		$ids[] = 'automatewoo_page_automatewoo-referral-codes';
		return $ids;
	}


	/**
	 * Adds the dashboard chart widgets.
	 * `automatewoo/dashboard/chart_widgets` callback.
	 *
	 * @param array $widgets
	 * @return array
	 */
	public function dashboard_chart_widgets( $widgets ) {
		$path = AW_Referrals()->admin_path( '/dashboard-widgets/' );

		if ( Rest_API::is_enabled() ) {
			$widgets[] = $path . 'analytics-orders.php';
		}
		$widgets[] = $path . 'chart-invites.php';
		return $widgets;
	}


	/**
	 * Returns the email subject for the referral share email.
	 * `automatewoo/email_preview/subject` callback.
	 *
	 * @param string $subject
	 * @param string $type
	 * @return string
	 */
	public function email_preview_subject( $subject, $type ) {
		if ( $type !== 'referral_share' ) {
			return $subject;
		}

		return AW_Referrals()->options()->share_email_subject;
	}


	/**
	 * Outputs the referral share email preview HTML.
	 * `automatewoo/email_preview/html` callback.
	 *
	 * @param string $type
	 * @param array  $args
	 * @return void
	 */
	public function email_preview_html( $type, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( $type !== 'referral_share' ) {
			return;
		}

		$user = get_user_by( 'id', get_current_user_id() );

		$email = new Invite_Email( $user->user_email, Advocate_Factory::get( $user->ID ) );

		// phpcs:disable WordPress.Security.EscapeOutput
		// Don't escape email body HTML
		echo $email->get_html();
		// phpcs:enable
	}


	/**
	 * Returns the email template for the referral share email.
	 * `automatewoo/email_preview/template` callback.
	 *
	 * @param string $template
	 * @param string $type
	 * @param array  $args
	 * @return string
	 */
	public function email_preview_template( $template, $type, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( $type !== 'referral_share' ) {
			return $template;
		}

		return AW_Referrals()->options()->share_email_template;
	}


	/**
	 * Send a test email for the referral share email.
	 * `automatewoo/email_preview/send_test` callback
	 *
	 * @param string $type
	 * @param array  $to
	 * @param array  $args
	 */
	public function email_preview_send_test( $type, $to, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$sent = 0;

		if ( $type !== 'referral_share' ) {
			return;
		}

		foreach ( $to as $email ) {
			$mailer = new Invite_Email( $email, Advocate_Factory::get( get_current_user_id() ) );
			$send   = $mailer->send( true );

			if ( $send === true ) {
				++$sent;
			}
		}

		if ( $sent === 0 ) {
			wp_send_json_success( [ 'message' => __( 'Error! No emails were sent.', 'automatewoo-referrals' ) ] );
		} else {
			wp_send_json_success(
				[
					/* translators: Count of emails sent. */
					'message' => sprintf( __( 'Success! Emails sent: %s', 'automatewoo-referrals' ), $sent ),
				]
			);
		}
	}


	/**
	 * Returns a URL for a specific page.
	 *
	 * @param string $page
	 * @param string $data
	 * @return string
	 */
	public function page_url( $page, $data = '' ) {
		switch ( $page ) {
			case 'advocates':
				return admin_url( 'admin.php?page=automatewoo-referral-advocates' );

			case 'referrals':
				return admin_url( 'admin.php?page=automatewoo-referrals' );

			case 'invites':
				return admin_url( 'admin.php?page=automatewoo-referral-invites' );

			case 'view-referral':
				return add_query_arg(
					[
						'referral_id' => $data,
						'action'      => 'view',
					],
					admin_url( 'admin.php?page=automatewoo-referrals' )
				);

			case 'settings':
				return admin_url( 'admin.php?page=automatewoo-settings&tab=referrals' );

			case 'documentation':
				return 'https://woocommerce.com/document/automatewoo/refer-a-friend/?utm_source=wordpress&utm_medium=all-plugins-page&utm_campaign=doc-link&utm_content=automatewoo-referrals';

		}

		return '';
	}


	/**
	 * Returns a formatted customer name.
	 *
	 * @param \WP_User $user
	 * @return string
	 */
	public function get_formatted_customer_name( $user ) {

		if ( ! $user ) {
			return '-';
		}

		if ( $user->first_name ) {
			/* translators: %1$s first name, %2$s last name */
			return sprintf( _x( '%1$s %2$s', 'full name', 'automatewoo-referrals' ), $user->first_name, $user->last_name );
		}

		return $user->user_email;
	}


	/**
	 * Returns a formatted customer name from an order.
	 *
	 * @param \WC_Order $order
	 * @return string
	 */
	public function get_formatted_customer_name_from_order( $order ) {

		if ( ! $order ) {
			return '-';
		}

		if ( $order->get_billing_first_name() ) {
			return $order->get_formatted_billing_full_name();
		}

		return $order->get_billing_email();
	}



	/**
	 * Adds the pending referrals count to the menu.
	 */
	public function menu_referrals_count() {

		global $submenu;

		if ( ! isset( $submenu['automatewoo'] ) ) {
			return;
		}

		foreach ( $submenu['automatewoo'] as &$menu_item ) {
			if ( $menu_item[2] === 'automatewoo-referrals' ) {

				$count = Referral_Manager::get_referrals_count( 'pending' ) + Referral_Manager::get_referrals_count( 'potential-fraud' );

				if ( current_user_can( 'manage_woocommerce' ) && $count ) {
					$menu_item[0] .= ' <span class="awaiting-mod update-plugins count-' . $count . '"><span class="processing-count">' . number_format_i18n( $count ) . '</span></span>';
				}
			}
		}
	}


	/**
	 * Returns an array of controller includes.
	 *
	 * @param array $controllers
	 * @return array
	 */
	public function filter_controller_includes( $controllers ) {
		$path                              = AW_Referrals()->admin_path( '/controllers/' );
		$controllers['referrals']          = $path . 'referrals.php';
		$controllers['referral-advocates'] = $path . 'advocates.php';
		$controllers['referral-invites']   = $path . 'invites.php';
		$controllers['referral-codes']     = $path . 'referral-codes.php';
		return $controllers;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @since 2.7.11
	 *
	 * @param  mixed $links Plugin Action links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$action_links = [
			'settings'      => '<a href="' . esc_url( $this->page_url( 'settings' ) ) . '" title="' . esc_attr( __( 'View AutomateWoo Referral Settings', 'automatewoo-referrals' ) ) . '">' . esc_html__( 'Settings', 'automatewoo-referrals' ) . '</a>',
			'documentation' => '<a href="' . esc_url( $this->page_url( 'documentation' ) ) . '" title="' . esc_attr( __( 'View AutomateWoo Referral Documentation', 'automatewoo-referrals' ) ) . '">' . esc_html__( 'Documentation', 'automatewoo-referrals' ) . '</a>',
		];

		return array_merge( $action_links, $links );
	}
}
