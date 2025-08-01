/**
 * External dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ReferralsReport from './report.js';

/**
 * Use the 'woocommerce_admin_reports_list' filter to add a report page.
 */
addFilter( 'woocommerce_admin_reports_list', 'automatewoo', ( reports ) => {
	return [
		...reports,
		{
			report: 'automatewoo-referrals',
			title: _x(
				'Referrals',
				'analytics report title',
				'automatewoo-referrals'
			),
			component: ReferralsReport,
			navArgs: {
				id: 'automatewoo-analytics-referrals',
			},
		},
	];
} );
