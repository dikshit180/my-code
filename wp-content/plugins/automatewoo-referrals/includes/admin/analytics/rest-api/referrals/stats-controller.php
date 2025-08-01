<?php
namespace AutomateWoo\Referrals\Admin\Analytics\Rest_API\Referrals;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\Admin\Analytics\Rest_API\Log_Stats_Controller;
use AutomateWoo\Admin\Analytics\Rest_API\Upstream\Generic_Query;

/**
 * REST API Referrals Report stats controller class.
 *
 * @extends Log_Stats_Controller
 * @since 2.7.0
 */
class Stats_Controller extends Log_Stats_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/referrals/stats';

	/**
	 * Forwards a Referrals Query constructor.
	 *
	 * @param array $query_args Set of args to be forwarded to the constructor.
	 * @return Generic_Query
	 */
	protected function construct_query( $query_args ) {
		return new Generic_Query( $query_args, 'report-referrals-stats' );
	}

	/**
	 * Get the Report's item properties schema.
	 * Will be used by `get_item_schema` as `totals` and `subtotals`.
	 *
	 * @return array
	 */
	public function get_item_properties_schema() {
		return array(
			'net_revenue'  => array(
				'description' => __( 'Referred orders net sales.', 'automatewoo-referrals' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'format'      => 'currency',
			),
			'orders_count' => array(
				'title'       => __( 'Orders', 'automatewoo-referrals' ),
				'description' => __( 'Number of referred orders', 'automatewoo-referrals' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'indicator'   => true,
			),
		);
	}
	/**
	 * Get the Referrals Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema          = parent::get_item_schema();
		$schema['title'] = 'report_referrals_stats';

		return $this->add_additional_fields_schema( $schema );
	}
}
