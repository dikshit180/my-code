<?php
namespace AutomateWoo\Referrals;

use AutomateWoo;
use AutomateWoo\Error;
use AutomateWoo\Exception;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Geolocation;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Advocate class.
 */
class Advocate {

	/**
	 * User.
	 *
	 * @var WP_User
	 */
	private $user;

	/**
	 * Existing user.
	 *
	 * @var bool
	 */
	public $exists = false;


	/**
	 * Immediately loads the advocate user data or sets up the user as an advocate.
	 *
	 * @param int|bool|WP_User $user
	 */
	public function __construct( $user ) {

		if ( $user instanceof WP_User ) {
			$this->user = $user;
		}

		if ( is_numeric( $user ) ) {
			$this->user = get_user_by( 'id', $user );
		}

		if ( $this->user ) {
			$this->exists = true;
		}
	}


	/**
	 * The advocate ID is the same as the advocate's user ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->user ? $this->user->ID : 0;
	}


	/**
	 * Get user.
	 *
	 * @return WP_User
	 */
	public function get_user() {
		return $this->user;
	}


	/**
	 * Get user ID.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->get_id();
	}


	/**
	 * Get user full name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return trim(
			sprintf(
				/* translators: %1$s first name, %2$s last name */
				_x( '%1$s %2$s', 'full name', 'automatewoo-referrals' ),
				$this->get_first_name(),
				$this->get_last_name()
			)
		);
	}

	/**
	 * Get first name.
	 *
	 * @return string
	 */
	public function get_first_name(): string {
		return $this->user->first_name ?? '';
	}


	/**
	 * Get last name.
	 *
	 * @return string
	 */
	public function get_last_name(): string {
		return $this->user->last_name ?? '';
	}


	/**
	 * Get user email.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->user->user_email;
	}


	/**
	 * Get advocate key (using temporary cache).
	 *
	 * @return false|string
	 */
	public function get_advocate_key() {

		// non persistently cached
		$cache = AutomateWoo\Temporary_Data::get( 'advocate_current_key', $this->get_id() );

		if ( $cache ) {
			return $cache;
		}

		// find a key for the advocate
		$query = new Advocate_Key_Query();
		$query->where( 'advocate_id', $this->get_id() );
		$query->set_limit( 1 );

		if ( AW_Referrals()->options()->is_advocate_key_expiry_enabled() ) {
			// if advocate keys are set to expire make sure the key is not stale
			$expiry = new \DateTime();
			$expiry->modify( apply_filters( 'automatewoo/referrals/advocate_key_stale_timeout', '-24 hours' ) );

			$query->where( 'created', $expiry, '>' );
		}

		$results = $query->get_results();

		if ( $results ) {
			$key = current( $results )->get_key();
		} else {
			$key = $this->create_advocate_key();
		}

		AutomateWoo\Temporary_Data::set( 'advocate_current_key', $this->get_id(), $key );

		return $key;
	}


	/**
	 * Create advocate key.
	 *
	 * @return string
	 */
	private function create_advocate_key() {

		$key = $this->generate_advocate_key();

		$object = new Advocate_Key();
		$object->set_advocate_id( $this->get_id() );
		$object->set_date_created( new \DateTime() );
		$object->set_key( $key );
		$object->save();

		return $key;
	}


	/**
	 * Generate advocate key.
	 *
	 * @return string
	 */
	private function generate_advocate_key() {
		$key = aw_generate_coupon_key( Coupons::get_key_length() );

		if ( aw_referrals_advocate_key_exists( $key ) ) {
			return $this->generate_advocate_key();
		}

		/**
		 * Filter a generated advocate key.
		 *
		 * Please note: Filtered advocate key value is not checked for uniqueness to avoid cases of infinite recursion.
		 * Therefore we recommend you check the value before returning it to this hook for uniqueness.
		 */
		return apply_filters( 'automatewoo/referrals/generate_advocate_key', $key, $this );
	}


	/**
	 * Check if a user is valid.
	 *
	 * @return bool
	 */
	public function is_valid() {

		if ( ! $this->user ) {
			return false;
		}

		return true;
	}


	/**
	 * Store IP address for the user.
	 */
	public function store_ip() {
		update_user_meta( $this->get_id(), '_aw_referrals_advocate_ip', WC_Geolocation::get_ip_address() );
	}


	/**
	 * Get stored IP address for the user.
	 *
	 * @return false|string
	 */
	public function get_stored_ip() {
		return get_user_meta( $this->get_id(), '_aw_referrals_advocate_ip', true );
	}



	/**
	 * Get shareable coupon.
	 *
	 * @return false|string
	 */
	public function get_shareable_coupon() {

		if ( AW_Referrals()->options()->type === 'coupon' ) {
			return strtoupper( Coupons::get_prefix() . $this->get_advocate_key() );
		}

		return false;
	}


	/**
	 * If $url is blank the social_share_url will be used that is now consider the default share URL for all share methods
	 *
	 * @param string|bool $url
	 * @return string|false
	 */
	public function get_shareable_link( $url = false ) {
		if ( AW_Referrals()->options()->type !== 'link' ) {
			return false;
		}

		if ( ! $url ) {
			$url = AW_Referrals()->options()->social_share_url;
		}

		return esc_url( add_query_arg( array( AW_Referrals()->options()->share_link_parameter => $this->get_advocate_key() ), trim( $url ) ) );
	}


	/**
	 * Get Facebook share URL.
	 *
	 * @return string
	 */
	public function get_facebook_share_url() {
		$facebook = Social_Integrations::get( 'facebook' );
		return $facebook->get_share_url( $this );
	}


	/**
	 * Get Twitter share URL.
	 *
	 * @return string
	 */
	public function get_twitter_share_url() {
		$twitter = Social_Integrations::get( 'twitter' );
		return $twitter->get_share_url( $this );
	}


	/**
	 * Get social share URL.
	 *
	 * @return string
	 */
	public function get_social_share_url() {

		$url = trim( AW_Referrals()->options()->social_share_url );

		if ( AW_Referrals()->options()->type === 'link' ) {
			$url = $this->get_shareable_link( $url );
		}

		return $url;
	}


	/**
	 * Get current credit for user.
	 *
	 * @return float
	 */
	public function get_current_credit() {
		return Credit::get_available_credit( $this->get_id() );
	}


	/**
	 * Get total credit for user.
	 *
	 * @return float
	 */
	public function get_total_credit() {
		return Credit::get_total_credit( $this->get_id() );
	}


	/**
	 * Get referral count.
	 *
	 * @param string|array|bool $status - optional
	 * @return int
	 */
	public function get_referral_count( $status = false ) {

		$query = ( new Referral_Query() );
		$query->where( 'advocate_id', $this->get_id() );

		if ( $status ) {
			$query->where( 'status', $status );
		}

		return $query->get_count();
	}


	/**
	 * Get total referral revenue.
	 *
	 * @return float
	 */
	public function get_referral_revenue() {

		$query = ( new Referral_Query() )
			->where( 'status', 'approved' )
			->where( 'advocate_id', $this->get_id() );

		$referrals = $query->get_results();

		if ( ! $referrals ) {
			return (float) 0;
		}

		$order_ids = array();

		foreach ( $referrals as $referral ) {
			$order_ids[] = $referral->get_order_id();
		}

		global $wpdb;
		$order_ids_sql   = implode( ',', $order_ids );
		$order_types_sql = sprintf( "'%s'", implode( "','", wc_get_order_types( 'sales-reports' ) ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$spent = $wpdb->get_var(
				"SELECT SUM(total_amount)
				FROM {$wpdb->prefix}wc_orders
				WHERE `id` IN ({$order_ids_sql})
				AND `type` IN ({$order_types_sql})
				AND `status` IN ( 'wc-completed', 'wc-processing' )
				"
			);
		} else {
			$spent = $wpdb->get_var(
				"SELECT SUM(meta.meta_value)
				FROM {$wpdb->posts} as posts
				LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
				WHERE posts.ID IN ({$order_ids_sql})
				AND posts.post_type IN ({$order_types_sql})
				AND posts.post_status IN ( 'wc-completed', 'wc-processing' )
				AND meta.meta_key = '_order_total'
				"
			);
		}
		// phpcs:enable

		return (float) $spent;
	}


	/**
	 * Get invites count.
	 *
	 * @return int
	 */
	public function get_invites_count() {
		$query = ( new Invite_Query() )
			->where( 'advocate_id', $this->get_id() );

		return $query->get_count();
	}


	/**
	 * Process share text.
	 *
	 * @param string $text
	 * @return mixed
	 */
	public function process_share_text( $text ) {
		return Option_Variables::process( $text, $this );
	}


	/**
	 * Returns true if advocate can share or instance of AutomateWoo\Error when not permitted.
	 *
	 * Error will contain a message to the user of why they can't share.
	 *
	 * This doesn't check if the advocate is logged int.
	 *
	 * @since 2.1
	 * @return true|Error
	 */
	public function can_share() {
		$can_share = true;

		try {
			$this->validate_not_blocked();
			$this->validate_paying_customer();
			$this->validate_referral_limit();
		} catch ( AutomateWoo\Exception $e ) {
			$can_share = new Error( $e->getMessage(), $e->getCode() );
		}

		return apply_filters( 'automatewoo/referrals/advocate_can_share', $can_share, $this, $this->get_user() );
	}


	/**
	 * Check if the user is a paying customer.
	 *
	 * @since 2.1 filter added
	 * @return bool
	 */
	public function is_paying_customer() {
		$is_paying = (bool) get_user_meta( $this->get_id(), 'paying_customer', true );

		if ( ! $is_paying ) {
			// WC doesn't include processing orders as 'paying_customer' so we need to add a query here
			// This query should only run when the meta value is blank
			$orders = wc_get_orders(
				array(
					'type'     => 'shop_order',
					'customer' => array( $this->get_user_id(), $this->get_email() ),
					'status'   => wc_get_is_paid_statuses(),
					'return'   => 'ids',
					'limit'    => 1,
				)
			);

			if ( $orders ) {
				$is_paying = true;
			}
		}

		return apply_filters( 'automatewoo/referrals/advocate_is_paying_customer', $is_paying, $this, $this->get_user() );
	}


	/**
	 * Check if advocate is blocked.
	 *
	 * @since 1.9
	 * @return bool
	 */
	public function is_blocked() {
		$blocked = 'yes' === get_user_meta( $this->get_id(), '_aw_referrals_advocate_blocked', true );
		return (bool) apply_filters( 'automatewoo/referrals/advocate_is_blocked', $blocked, $this, $this->get_user() );
	}

	/**
	 * Block the advocate.
	 */
	public function block() {
		update_user_meta( $this->get_id(), '_aw_referrals_advocate_blocked', 'yes' );
	}

	/**
	 * Unblock the advocate.
	 */
	public function unblock() {
		update_user_meta( $this->get_id(), '_aw_referrals_advocate_blocked', 'no' );
	}

	/**
	 * Validate that the Advocate is not blocked.
	 *
	 * @throws Exception When user account is not permitted to refer friends.
	 */
	private function validate_not_blocked() {
		if ( $this->is_blocked() ) {
			throw new Exception( esc_html__( 'Sorry, your account is not permitted to refer friends.', 'automatewoo-referrals' ), 1 );
		}
	}

	/**
	 * Validate that the Advocate is a paying customer, if that option is set.
	 *
	 * @throws Exception When user is not a paying customer.
	 */
	private function validate_paying_customer() {
		if ( AW_Referrals()->options()->advocate_must_paying_customer && ! $this->is_paying_customer() ) {
			throw new Exception( esc_html__( 'You must be a paying customer to refer friends.', 'automatewoo-referrals' ), 2 );
		}
	}

	/**
	 * Validate that the Advocate hasn't reached the referral limit, if that option is set.
	 *
	 * @throws Exception When a referral limit has been reached.
	 */
	public function validate_referral_limit() {
		if ( ! AW_Referrals()->options()->limit_number_referrals ) {
			return;
		}

		$timeframe = AW_Referrals()->options()->referral_limit_timeframe;
		$limit     = (int) AW_Referrals()->options()->referral_limit;
		$query     = ( new Referral_Query() )
			->where( 'advocate_id', $this->get_id() )
			->where( 'status', 'rejected', '!=' );

		switch ( $timeframe ) {
			case 'lifetime':
				if ( $query->get_count() >= $limit ) {
					throw new Exception( esc_html__( 'Sorry, your account has reached the lifetime referral limit.', 'automatewoo-referrals' ), 4 );
				}
				break;

			case 'year':
				if ( $query->where_this_year()->get_count() >= $limit ) {
					throw new Exception( esc_html__( 'Sorry, your account has reached the yearly referral limit.', 'automatewoo-referrals' ), 8 );
				}
				break;

			case 'month':
				if ( $query->where_this_month()->get_count() >= $limit ) {
					throw new Exception( esc_html__( 'Sorry, your account has reached the monthly referral limit.', 'automatewoo-referrals' ), 16 );
				}
				break;

			case 'week':
				if ( $query->where_this_week()->get_count() >= $limit ) {
					throw new Exception( esc_html__( 'Sorry, your account has reached the weekly referral limit.', 'automatewoo-referrals' ), 32 );
				}
				break;
		}
	}
}
