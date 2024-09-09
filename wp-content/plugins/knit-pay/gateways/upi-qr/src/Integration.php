<?php

namespace KnitPay\Gateways\UpiQR;

use Pronamic\WordPress\Pay\AbstractGatewayIntegration;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;

/**
 * Title: UPI QR Integration
 * Copyright: 2020-2024 Knit Pay
 *
 * @author  Knit Pay
 * @version 1.0.0
 * @since   4.1.0
 */
class Integration extends AbstractGatewayIntegration {
	const HIDE_FIELD          = '0';
	const SHOW_FIELD          = '1';
	const SHOW_REQUIRED_FIELD = '2';

	/**
	 * Construct UPI QR integration.
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'id'       => 'upi-qr',
				'name'     => 'UPI QR (Unstable)',
				'url'      => 'http://go.thearrangers.xyz/',
				'provider' => 'upi-qr',
			]
		);

		parent::__construct( $args );

		// Add Ajax listener.
		add_action( 'wp_ajax_nopriv_knit_pay_upi_qr_payment_status_check', [ $this, 'ajax_payment_status_check' ] );
		add_action( 'wp_ajax_knit_pay_upi_qr_payment_status_check', [ $this, 'ajax_payment_status_check' ] );
	}

	/**
	 * Setup gateway integration.
	 *
	 * @return void
	 */
	public function setup() {
		// Display ID on Configurations page.
		\add_filter(
			'pronamic_gateway_configuration_display_value_' . $this->get_id(),
			[ $this, 'gateway_configuration_display_value' ],
			10,
			2
		);
	}

	/**
	 * Gateway configuration display value.
	 *
	 * @param string $display_value Display value.
	 * @param int    $post_id       Gateway configuration post ID.
	 * @return string
	 */
	public function gateway_configuration_display_value( $display_value, $post_id ) {
		$config = $this->get_config( $post_id );

		return $config->vpa;
	}

	/**
	 * Get settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		$fields = [];

		$fields = $this->get_about_settings_fields( $fields );

		// Payee name or business name.
		$fields[] = [
			'section'  => 'general',
			'meta_key' => '_pronamic_gateway_upi_qr_payee_name',
			'title'    => __( 'Payee Name or Business Name', 'knit-pay-lang' ),
			'type'     => 'text',
			'classes'  => [ 'regular-text', 'code' ],
		];

		// UPI VPA ID
		$fields[] = [
			'section'  => 'general',
			'meta_key' => '_pronamic_gateway_upi_qr_vpa',
			'title'    => __( 'UPI VPA ID', 'knit-pay-lang' ),
			'type'     => 'text',
			'classes'  => [ 'regular-text', 'code' ],
			'tooltip'  => __( 'UPI/VPA ID which you want to use to receive the payment.', 'knit-pay-lang' ),
		];

		// Merchant category code.
		$fields[] = [
			'section'     => 'general',
			'meta_key'    => '_pronamic_gateway_upi_qr_merchant_category_code',
			'title'       => __( 'Merchant Category Code', 'knit-pay-lang' ),
			'type'        => 'text',
			'classes'     => [ 'regular-text', 'code' ],
			'tooltip'     => __( 'four-digit ISO 18245 merchant category code (MCC) to classify your business.', 'knit-pay-lang' ),
			'description' => 'You can refer to below links to find out your MCC.<br>' .
							 '<a target="_blank" href="https://www.citibank.com/tts/solutions/commercial-cards/assets/docs/govt/Merchant-Category-Codes.pdf">Citi Bank - Merchant Category Codes</a><br>' .
							 '<a target="_blank" href="https://docs.checkout.com/resources/codes/merchant-category-codes">Checkout.com - Merchant Category Codes</a><br>',
		];
		
		// Payment Instruction.
		$fields[] = [
			'section'  => 'general',
			'meta_key' => '_pronamic_gateway_upi_qr_payment_instruction',
			'title'    => __( 'Payment Instruction', 'knit-pay-lang' ),
			'type'     => 'text',
			'classes'  => [ 'large-text', 'code' ],
			'default'  => __( 'Scan the QR Code with any UPI apps like BHIM, Paytm, Google Pay, PhonePe, or any Banking UPI app to make payment for this order. After successful payment, enter the UPI Reference ID or Transaction Number submit the form. We will manually verify this payment against your 12-digits UPI Reference ID or Transaction Number (eg. 001422121258).', 'knit-pay-lang' ),
			'tooltip'  => __( 'It will be displayed to customers while making payment using destop devices.', 'knit-pay-lang' ),
		];
		
		// Payment Instruction.
		$fields[] = [
			'section'  => 'general',
			'meta_key' => '_pronamic_gateway_upi_qr_mobile_payment_instruction',
			'title'    => __( 'Mobile Payment Instruction', 'knit-pay-lang' ),
			'type'     => 'text',
			'classes'  => [ 'large-text', 'code' ],
			'default'  => __( 'Scan the QR Code with any UPI apps like BHIM, Paytm, Google Pay, PhonePe, or any Banking UPI app to make payment for this order. After successful payment, enter the UPI Reference ID or Transaction Number submit the form. We will manually verify this payment against your 12-digits UPI Reference ID or Transaction Number (eg. 001422121258).', 'knit-pay-lang' ),
			'tooltip'  => __( 'It will be displayed to customers while making payment using mobile devices.', 'knit-pay-lang' ),
		];
		
		// Payment Success Status.
		$fields[] = [
			'section'     => 'general',
			'meta_key'    => '_pronamic_gateway_upi_qr_payment_success_status',
			'title'       => __( 'Payment Success Status', 'knit-pay-lang' ),
			'type'        => 'select',
			'options'     => [
				PaymentStatus::ON_HOLD => PaymentStatus::ON_HOLD,
				PaymentStatus::OPEN    => __( 'Pending', 'knit-pay-lang' ),
				PaymentStatus::SUCCESS => PaymentStatus::SUCCESS,
			],
			'default'     => PaymentStatus::ON_HOLD,
			'description' => 'Knit Pay does not check if payment is received or not. Kindly deliver the product/service only after cross-checking the payment status with your bank.',
		];
		
		// Transaction ID Field.
		$fields[] = [
			'section'  => 'general',
			'meta_key' => '_pronamic_gateway_upi_qr_transaction_id_field',
			'title'    => __( 'Transaction ID Field', 'knit-pay-lang' ),
			'type'     => 'select',
			'options'  => [
				self::HIDE_FIELD          => __( 'Hide Input Field', 'knit-pay-lang' ),
				self::SHOW_FIELD          => __( 'Show Input Field (Not Requied)', 'knit-pay-lang' ),
				self::SHOW_REQUIRED_FIELD => __( 'Show Input Field (Requied)', 'knit-pay-lang' ),
			],
			'default'  => 2,
			'tooltip'  => __( 'If you want to collect UPI Transaction ID from customers, set it from here.', 'knit-pay-lang' ),
		];

		// Hide Mobile QR Code.
		$fields[] = [
			'section'  => 'general',
			'filter'   => FILTER_VALIDATE_BOOLEAN,
			'meta_key' => '_pronamic_gateway_upi_qr_hide_mobile_qr',
			'title'    => __( 'Hide Mobile QR Code', 'knit-pay-lang' ),
			'type'     => 'checkbox',
			'default'  => false,
			'label'    => __( 'Select to Hide QR Code on Mobile.', 'knit-pay-lang' ),
		];

		// Hide Payment Button.
		$fields[] = [
			'section'     => 'general',
			'filter'      => FILTER_VALIDATE_BOOLEAN,
			'meta_key'    => '_pronamic_gateway_upi_qr_hide_pay_button',
			'title'       => __( 'Hide Payment Button', 'knit-pay-lang' ),
			'type'        => 'checkbox',
			'default'     => true,
			'label'       => __( 'Select to Hide Payment Button on Mobile. (Click here to make the payment)', 'knit-pay-lang' ),
			'description' => __( 'Please Note: Some VPA id does not work with Payment Buttons. Uncheck this option only if your VPA works properly with Payment Buttons.', 'knit-pay-lang' ),
		];

		// Return fields.
		return $fields;
	}

	public function get_about_settings_fields( $fields ) {
		$fields[] = [
			'section'     => 'general',
			'type'        => 'custom',
			'description' => '<h1><strong>Please Note:</strong> This module is highly unstable and your customers might face lots of payment failures while using it. Knit Pay strongly suggests that you integrate UPI using some payment gateway service provider instead of this module. Due to a high number of requests from website owners, we have kept this module active. Kindly use it only if you are ready to face potential risks.</h1>',
		];

		// Steps to Integrate.
		$fields[] = [
			'section'  => 'general',
			'type'     => 'custom',
			'callback' => function () {
				$utm_parameter = '?utm_source=knit-pay&utm_medium=ecommerce-module&utm_campaign=module-admin&utm_content=help-signup';

				echo '<p>' . __( '<strong>Steps to Integrate UPI QR</strong>' ) . '</p>' .

				'<ol>
                <li>Signup at any UPI-enabled App. If you will signup using provided signup URLs and use the referral codes, you might also get a bonus after making few payments.
                    <ul>
                        <li>- <a target="_blank" href="' . $this->get_url() . 'open-money' . $utm_parameter . '">Open Money</a></li>
                        <li>- <a target="_blank" href="' . $this->get_url() . 'gpay' . $utm_parameter . '">Google Pay</a> Referral Code: Z05o0</li>
                        <li>- <a target="_blank" href="' . $this->get_url() . 'phonepe' . $utm_parameter . '">PhonePe</a></li>
                        <li>- <a target="_blank" href="' . $this->get_url() . 'amazon-pay' . $utm_parameter . '">Amazon Pay</a> Referral Code: K1ZESF</li>
                        <li>- <a target="_blank" href="' . $this->get_url() . 'bharatpe' . $utm_parameter . '">BharatPe (' . $this->get_url() . 'bharatpe)</a> - Signup using the referral link (on the phone) to get 1500 PayBack points equivalent to ₹375.</li>
                        <li>- <a target="_blank" href="https://play.google.com/store/search?q=upi&c=apps">More UPI Apps</a></li>
                    </ul>
                </li>

                <li>Link your Bank Account and generate a UPI ID/VPA.</li>

                <li>Use this VPA/UPI ID on the configuration page below.
                <br><strong>Kindly use the correct VPA/UPI ID. In case of wrong settings, payments will get credited to the wrong bank account. Knit Pay will not be responsible for any of your lose.</strong></li>

                <li>Save the settings.</li>

                <li>Before going live, make a test payment of ₹1 and check that you are receiving this payment in the correct bank account.</li>

            </ol>';},
		];

		// How does it work.
		$fields[] = [
			'section'  => 'general',
			'type'     => 'custom',
			'callback' => function () {
				echo '<p>' . __( '<strong>How does it work?</strong>' ) . '</p>' .

				'<ol>
                <li>On the payment screen, the customer scans the QR code using any UPI-enabled mobile app and makes the payment.</li>

                <li>The customer enters the transaction ID and submits the payment form.</li>

                <li>Payment remains on hold. Merchant manually checks the payment and mark it as complete on the "Knit Pay" Payments page.</li>

                <li>Automatic tracking is not available in the UPI QR payment method. You can signup at other supported free payment gateways to get an automatic payment tracking feature.
                    <br><a target="_blank" href="https://www.knitpay.org/indian-payment-gateways-supported-in-knit-pay/">Indian Payment Gateways Supported in Knit Pay</a>
                </li>

            </ol>';},
		];

		return $fields;
	}

	public function get_config( $post_id ) {
		$config = new Config();

		$config->vpa                        = $this->get_meta( $post_id, 'upi_qr_vpa' );
		$config->payee_name                 = $this->get_meta( $post_id, 'upi_qr_payee_name' );
		$config->merchant_category_code     = $this->get_meta( $post_id, 'upi_qr_merchant_category_code' );
		$config->payment_instruction        = $this->get_meta( $post_id, 'upi_qr_payment_instruction' );
		$config->mobile_payment_instruction = $this->get_meta( $post_id, 'upi_qr_mobile_payment_instruction' );
		$config->payment_success_status     = $this->get_meta( $post_id, 'upi_qr_payment_success_status' );
		$config->transaction_id_field       = $this->get_meta( $post_id, 'upi_qr_transaction_id_field' );
		$config->hide_mobile_qr             = $this->get_meta( $post_id, 'upi_qr_hide_mobile_qr' );
		$config->hide_pay_button            = $this->get_meta( $post_id, 'upi_qr_hide_pay_button' );

		if ( empty( $config->payment_success_status ) ) {
			$config->payment_success_status = PaymentStatus::ON_HOLD;
		}

		if ( '' === $config->transaction_id_field ) {
			$config->transaction_id_field = self::SHOW_REQUIRED_FIELD;
		}

		return $config;
	}

	/**
	 * Get gateway.
	 *
	 * @param int $post_id Post ID.
	 * @return Gateway
	 */
	public function get_gateway( $config_id ) {
		return new Gateway( $this->get_config( $config_id ) );
	}

	public function ajax_payment_status_check() {
		$payment_id     = isset( $_POST['knit_pay_payment_id'] ) ? sanitize_text_field( $_POST['knit_pay_payment_id'] ) : '';
		$transaction_id = isset( $_POST['knit_pay_transaction_id'] ) ? sanitize_text_field( $_POST['knit_pay_transaction_id'] ) : '';
		$knit_pay_nonce = isset( $_POST['knit_pay_nonce'] ) ? sanitize_text_field( $_POST['knit_pay_nonce'] ) : '';

		$nonce_action = "knit_pay_payment_status_check|{$payment_id}|{$transaction_id}";

		if ( ! wp_verify_nonce( $knit_pay_nonce, $nonce_action ) ) {
			wp_send_json_error( __( 'Nonce Missmatch!', 'knit-pay-lang' ) );
		}

		$payment = get_pronamic_payment( $payment_id );

		if ( null === $payment ) {
			exit;
		}

		$gateway = $payment->get_gateway();
		if ( ! $gateway->supports( 'payment_status_request' ) ) {
			wp_send_json_error( __( 'Gateway does not support automatic payment status check.', 'knit-pay-lang' ) );
		}

		// Update status.
		try {
			$gateway->update_status( $payment );

			// Update payment in data store.
			$payment->save();

			wp_send_json_success( $payment->get_status() );
		} catch ( \Exception $error ) {
			$message = $error->getMessage();

			// Maybe include error code in message.
			$code = $error->getCode();

			if ( $code > 0 ) {
				$message = \sprintf( '%s: %s', $code, $message );
			}

			// Add note.
			$payment->add_note( $message );

			wp_send_json_error( $message );
		}
	}
}
