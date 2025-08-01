<?php

namespace AutomateWoo\Referrals\Jobs;

use AutomateWoo\Jobs\AbstractBatchedActionSchedulerJob;
use AutomateWoo\Jobs\JobException;
use AutomateWoo\Jobs\Traits\ValidateItemAsIntegerId;
use AutomateWoo\Referrals\Invite_Factory;
use AutomateWoo\Referrals\Invite_Query;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * AnonymizeInviteEmails job class.
 *
 * Requires a 'invite' arg which contains the Invite object.
 *
 * @since 2.7.2
 */
class Anonymize_Invite_Emails extends AbstractBatchedActionSchedulerJob {

	use ValidateItemAsIntegerId;

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'referrals_anonymize_invite_emails';
	}

	/**
	 * Get a new batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job. Args are already validated.
	 *
	 * @return int[]
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function get_batch( int $batch_number, array $args ) {
		$query = new Invite_Query();
		$query->set_limit( $this->get_batch_size() );
		$query->set_offset( $this->get_query_offset( $batch_number ) );

		return $query->get_results_as_ids();
	}

	/**
	 * Process a single item.
	 *
	 * @param int   $item A single item from the get_batch() method. Expects a validated item.
	 * @param array $args The args for this instance of the job. Args are already validated.
	 *
	 * @throws JobException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_item( $item, array $args ) {
		$invite = Invite_Factory::get( $item );
		if ( ! $invite ) {
			throw JobException::item_not_found();
		}

		$email = $invite->get_email();

		if ( ! aw_is_email_anonymized( $email ) ) {
			$invite->set_email( $email );
			$invite->save();
		}

		return false;
	}
}
