<?php
// phpcs:ignoreFile

namespace AutomateWoo\Referrals;

/**
 * Allow a users referral credit to be applied to a recurring subscription payment
 *
 * @class Subscriptions
 * @since 1.2
 */
class Subscriptions {


	/**
	 * @param \WC_Subscription $subscription
	 * @return int
	 */
	static function get_subscription_advocate_id( $subscription ) {
		$parent_order = $subscription->get_parent();

		if ( $parent_order ) {
			return (int) $parent_order->get_meta( '_aw_referrals_advocate_id' );
		}

		return 0;
	}


	/**
	 * @param $order \WC_Order
	 * @param $subscription \WC_Subscription
	 *
	 * @return \WC_Order
	 */
	static function maybe_add_referral_credit( $order, $subscription ) {
		if ( ! $subscription->payment_method_supports( 'subscription_amount_changes' ) ) {
			return $order;
		}

		// allow third party to modify the credit available for a subscription renewal
		$credit = apply_filters( 'automatewoo/referrals/subscription_renewal_available_credit', Credit::get_available_credit( $order->get_user_id() ), $order, $subscription );

		if ( ! $credit  ) {
			return $order;
		}

		$valid = Credit_Validator::is_order_valid_for_credit( $order );

		if ( $valid !== true ) {
			return $order;
		}


		Credit::add_credit_to_order( $order, $credit );
		Credit::remove_credit_used_in_order( $order->get_id() );

		return $order;
	}


	/**
	 * Maybe create a referral after a subscription payment.
	 *
	 * If subscription was synchronised or had free trial we delay the referral until the first payment.
	 * So now that a payment has been made maybe create a referral and therefore reward the advocate.
	 *
	 * @hook woocommerce_subscription_renewal_payment_complete
	 *
	 * @param \WC_Subscription $subscription
	 * @param \WC_Order        $order
	 */
	static function maybe_create_referral_for_subscription_payment( $subscription, $order ) {
		if ( ! $subscription || ! $order ) {
			return;
		}

		// If the subscription is a referral an advocate ID will be save to the parent order meta
		$advocate = Advocate_Factory::get( self::get_subscription_advocate_id( $subscription ) );

		if ( ! $advocate ) {
			return;
		}

		// check if parent order was a referral
		// if yes don't try to create a new referral
		if ( Referral_Factory::get_by_order_id( $subscription->get_parent_id() ) ) {
			return;
		}

		$valid = Referral_Validator::is_order_a_valid_referral( $order, $advocate );

		if ( $valid === true ) {
			Referral_Manager::create_referral_for_purchase( $order, $advocate );
		}
	}

}
