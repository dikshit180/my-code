/**
 * External dependencies
 */
import { ReportFilters } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import ComposedReportChart from '../upstream/woocommerce-admin-analytics/analytics/components/report-chart';
import ComposedReportSummary from '../upstream/woocommerce-admin-analytics/analytics/components/report-summary';

const charts = [
	{
		key: 'orders_count',
		href: '',
		label: __( 'Orders', 'automatewoo-referrals' ),
		labelTooltipText: __(
			'Number of referred orders',
			'automatewoo-referrals'
		),
		type: 'number',
	},
	{
		key: 'net_revenue',
		href: '',
		label: __( 'Value', 'automatewoo-referrals' ),
		labelTooltipText: __(
			'Value of referred orders',
			'automatewoo-referrals'
		),
		type: 'currency',
	},
];
/**
 * Referrals report.
 *
 * @param {Object} props       Props provided by WooCommerce routing.
 * @param {Object} props.query
 * @param {string} props.path
 */
export default function ReferralsReport( { query, path } ) {
	// Pick queried chart, fallback to the first one.
	const chart =
		charts.find( ( item ) => item.key === query.chart ) || charts[ 0 ];

	return (
		<>
			<ReportFilters query={ query } path={ path } />
			<ComposedReportSummary
				charts={ charts }
				endpoint="referrals"
				query={ query }
				selectedChart={ chart }
			/>
			<ComposedReportChart
				endpoint="referrals"
				path={ path }
				query={ query }
				selectedChart={ chart }
				charts={ charts }
			/>
		</>
	);
}
