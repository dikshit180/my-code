<?php

namespace AutomateWoo\Referrals;

use AutomateWoo;

/**
 * Class for handling the invitation email.
 *
 * @class Invite_Email
 */
class Invite_Email {

	/**
	 * The email to send the invite
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The advocate sending the invitation
	 *
	 * @var Advocate
	 */
	public $advocate;


	/**
	 * Constructor
	 *
	 * @param string   $email The email to send the invite
	 * @param Advocate $advocate The advocate sending the invitation
	 */
	public function __construct( $email, $advocate ) {
		WC()->mailer(); // wc mailer must be loaded
		$this->email    = $email;
		$this->advocate = $advocate;
	}


	/**
	 * Get the email subject replacing the variables
	 *
	 * @return string The email subject
	 */
	public function get_subject() {
		return $this->replace_variables( AW_Referrals()->options()->share_email_subject );
	}


	/**
	 * Get the email heading replacing the variables
	 *
	 * @return string The email heading
	 */
	public function get_heading() {
		return $this->replace_variables( AW_Referrals()->options()->share_email_heading );
	}


	/**
	 * Get the email content replacing the variables and adding tracking in the URLs
	 *
	 * @return string The email content
	 */
	public function get_content() {
		$content = $this->replace_variables( AW_Referrals()->options()->share_email_body );

		if ( AW_Referrals()->options()->type === 'link' ) {
			$content = $this->make_trackable_urls( $content );
		}

		return $content;
	}


	/**
	 * Get the email template
	 *
	 * @return string The email template
	 */
	public function get_template() {
		return AW_Referrals()->options()->share_email_template;
	}


	/**
	 * Get the email body
	 *
	 * @return string The email body
	 */
	public function get_html() {
		$mailer = $this->get_mailer();
		return $mailer->get_email_body();
	}


	/**
	 * Get the mailer object with all their fields set.
	 *
	 * @return AutomateWoo\Mailer The mailer object
	 */
	public function get_mailer() {

		$mailer = new AutomateWoo\Mailer();
		$mailer->set_subject( $this->get_subject() );
		$mailer->set_email( $this->email );
		$mailer->set_content( $this->get_content() );
		$mailer->set_template( $this->get_template() );
		$mailer->set_heading( $this->get_heading() );

		return apply_filters( 'automatewoo/referrals/invite_email/mailer', $mailer, $this );
	}


	/**
	 * Replace the variables for a given string
	 *
	 * @param string $content The string to replace the variables
	 * @return string THe string with the variables replaced
	 */
	public function replace_variables( $content ) {
		return Option_Variables::process( $content, $this->advocate );
	}


	/**
	 * Add tracking to the URLs in a given string
	 *
	 * @param string $content The content to add the tracking
	 * @return string The content with the tracking added
	 */
	public function make_trackable_urls( $content ) {
		$replacer = new AutomateWoo\Replace_Helper( $content, array( $this, 'callback_trackable_urls' ), 'href_urls' );
		return $replacer->process();
	}


	/**
	 * Add the Advocate parameter in a specific URL
	 *
	 * @param string $url The URL to add the tracking
	 * @return string HREF attribute with the tracking added in the URL
	 */
	public function callback_trackable_urls( $url ) {

		if ( ! $url ) {
			return '';
		}

		$url = esc_url(
			add_query_arg(
				array(
					AW_Referrals()->options()->share_link_parameter => $this->advocate->get_advocate_key(),
				),
				$url
			)
		);

		return 'href="' . $url . '"';
	}


	/**
	 * Send the email
	 *
	 * @param bool $is_resend True if this email is resend
	 * @return \WP_Error|true True if the email was sent. WP_Error otherwise.
	 */
	public function send( $is_resend = false ) {

		$mailer = $this->get_mailer();
		$sent   = $mailer->send();

		if ( ! is_wp_error( $sent ) && ! $is_resend ) {
			$invite = $this->create_record();
			do_action( 'automatewoo/referrals/invite/sent', $invite );
		}

		return $sent;
	}


	/**
	 * Record each email shared
	 *
	 * @return Invite The created invite record
	 */
	public function create_record() {
		$invite = new Invite();
		$invite->set_email( $this->email );
		$invite->set_advocate_id( $this->advocate->get_id() );
		$invite->set_date( new \DateTime() );
		$invite->save();
		return $invite;
	}
}
