<?php
/**
 * Admin View: Notice - Deprecated Razorpay API Keys
 *
 * @author    Knit Pay
 * @copyright 2020-2024 Knit Pay
 * @license   GPL-3.0-or-later
 */

use Pronamic\WordPress\Pay\Admin\AdminGatewayPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get Razorpay config IDs without company name.
$config_ids = get_transient( 'knit_pay_razorpay_with_deprecated_keys' );

if ( ! is_array( $config_ids ) ) {
	return;
}

// Build gateways list.
$gateways = [];

foreach ( $config_ids as $config_id ) :
	if ( AdminGatewayPostType::POST_TYPE !== get_post_type( $config_id ) ) {
		continue;
	}

	$gateways[] = sprintf(
		'<a href="%1$s" title="%2$s">%2$s</a>',
		get_edit_post_link( $config_id ),
		get_the_title( $config_id )
	);

endforeach;

// Don't show notice if non of the gateways exists.
if ( empty( $gateways ) ) {
	// Delete transient.
	delete_transient( 'knit_pay_razorpay_with_deprecated_keys' );

	return;
}

?>
<div class="notice notice-warning">
	<p>
		<strong><?php esc_html_e( 'Knit Pay', 'knit-pay-lang' ); ?></strong> —
		<?php

		$message = sprintf(
			/* translators: 1: configuration link(s) */
			_n(
				'You are using deprecated configurations for Razorpay Gateway. Kindly configure it again on the %1$s configuration page. Deprecated setting will stop working in the future.',
				'You are using deprecated configurations for Razorpay Gateway. Kindly configure it again on the %1$s configuration pages. Deprecated settings will stop working in the future.',
				count( $config_ids ),
				'knit-pay'
			),
			implode( ', ', $gateways ) // WPCS: xss ok.
		);

		echo wp_kses(
			$message,
			[
				'a' => [
					'href'  => true,
					'title' => true,
				],
			]
		);

		?>
	</p>
</div>
