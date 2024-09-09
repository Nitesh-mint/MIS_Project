<?php
namespace KnitPay\Gateways\UpiQR;

use KnitPay\Gateways\Gateway as Core_Gateway;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Pronamic\WordPress\Pay\Core\PaymentMethod;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;

/**
 * Title: UPI QR Gateway
 * Copyright: 2020-2024 Knit Pay
 *
 * @author Knit Pay
 * @version 1.0.0
 * @since 4.1.0
 */
class Gateway extends Core_Gateway {

	/**
	 * Constructs and initializes an UPI QR gateway
	 *
	 * @param Config $config
	 *            Config.
	 */
	public function __construct( Config $config ) {
		parent::__construct( $config );
		
		$this->config = $config;

		$this->set_method( self::METHOD_HTML_FORM );

		$this->payment_page_title = 'Payment Page';
		
		if ( wp_is_mobile() ) {
			$this->payment_page_description = $config->mobile_payment_instruction;
		} else {
			$this->payment_page_description = $config->payment_instruction;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		$this->register_payment_methods();
	}

	private function register_payment_methods() {
		$this->register_payment_method( new PaymentMethod( PaymentMethods::UPI ) );
	}

	/**
	 * Start.
	 *
	 * @see Core_Gateway::start()
	 *
	 * @param Payment $payment
	 *            Payment.
	 */
	public function start( Payment $payment ) {
		$payment_currency = $payment->get_total_amount()
			->get_currency()
			->get_alphabetic_code();
		if ( isset( $payment_currency ) && 'INR' !== $payment_currency ) {
			$currency_error = 'UPI only accepts payments in Indian Rupees. If you are a store owner, kindly activate INR currency for ' . $payment->get_source() . ' plugin.';
			throw new \Exception( $currency_error );
		}

		if ( $this->config->hide_mobile_qr && $this->config->hide_pay_button ) {
			$mobile_error = "QR code and Payment Button can't be hidden at the same time. Kindly show at least one of them from the configuration page.";
			throw new \Exception( $mobile_error );
		}

		$payment->set_transaction_id( $payment->key . '_' . $payment->get_id() );

		$payment->set_action_url( $payment->get_pay_redirect_url() );
	}

	/**
	 * Output form.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 * @throws \Exception When payment action URL is empty.
	 */
	public function output_form( // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Parameter is used in include.
		Payment $payment
		) {
		$hide_pay_button = $this->config->hide_pay_button;

		if ( ! wp_is_mobile() ) {
			$hide_pay_button = true;
		}

		\wp_enqueue_script( 'jquery' );
		\wp_enqueue_script( 'knit-pay-easy-qrcode' );

		$data    = $this->get_output_fields( $payment );
		$pay_uri = add_query_arg( $data, 'upi://pay' );
		
		$form_inner = '';

		$html = wp_head() . '<hr>';
		
		// Show Pay Button after delay.
		$html .= '<script type="text/javascript">
                    // Get time after 30 seconds
                    var countDownDate = new Date().getTime() + 30000;
    		    
                    // Update the count down every 1 second
                    var x = setInterval(function() {
    		    
                          // Get today\'s date and time
                          var now = new Date().getTime();
    		    
                          // Find the distance between now and the count down date
                          var distance = countDownDate - now;
    		    
                          // Time calculations for seconds
                          var seconds = Math.ceil((distance % (1000 * 60)) / 1000);
    		    
                          // Output the result in an element with id="timmer"
                          document.getElementById("timmer").innerHTML = seconds + " sec";
    		    
                          // If the count down is over, write some text
                          if (distance < 0) {
                            clearInterval(x);
                            document.getElementById("transaction-details").removeAttribute("style");
                            document.getElementById("delay-info").remove();
                          }
                    }, 1000);
                </script>';
		
		// Show QR Code.
		if ( ! ( wp_is_mobile() && $this->config->hide_mobile_qr ) ) {
			$html .= '<div><strong>Scan the QR Code</strong></div><div class="qrcode"></div>';
			$html .= "<script type='text/javascript'>
                        jQuery(document).ready(function() {
                            new QRCode(document.querySelector('.qrcode'), {
                            		text: '$pay_uri',
                            		width: 250, //default 128
                            		height: 250,
                            		colorDark: '#000000',
                            		colorLight: '#ffffff',
                            		correctLevel: QRCode.CorrectLevel.H
                            	});
                            
                        });
                      </script>";
		}
		
		if ( ! ( $hide_pay_button || $this->config->hide_mobile_qr ) ) {
			$html .= '<p>or</p>';
		}

		if ( ! $hide_pay_button ) {
			$html .= '<a class="pronamic-pay-btn" href="' . $pay_uri . '" style="font-size: 15px;">Click here to make the payment</a>';
		}

		$html .= '<hr>';

		$form_inner .= '<span id="transaction-details" style="display: none;"><br><br>';

		// Transaction ID Filed.
		if ( Integration::HIDE_FIELD !== $this->config->transaction_id_field ) {
			$form_inner .= '<label for="transaction_id">Transaction ID:</label>
                <input type="text" id="transaction_id" name="transaction_id" ';
			if ( Integration::SHOW_REQUIRED_FIELD === $this->config->transaction_id_field ) {
				$form_inner .= 'required';
			}
			$form_inner .= ' ><br><br>';
		}

		// Submit Button.
		$form_inner .= sprintf(
			'<input class="pronamic-pay-btn" type="submit" name="pay-status" value="%s" />',
			__( 'Submit', 'knit-pay-lang' )
		);
		$form_inner .= '&nbsp;&nbsp;</span>';
		$form_inner .= "<div id='delay-info'>The \"Submit\" button will be visible after <span id='timmer'>30 sec</span>.<br><br></div>";
		
		// Cancel Button.
		$form_inner .= sprintf(
			'<input id = "cancel-button" class="pronamic-pay-btn" type="submit" name="pay-status" formnovalidate value="%s" />',
			__( 'Cancel', 'knit-pay-lang' )
		);

		$html .= sprintf(
			'<form id="pronamic_ideal_form" name="pronamic_ideal_form" method="post" action="%s">%s</form>',
			esc_attr( $payment->get_return_url() ),
			$form_inner
		);
		$html .= wp_footer();

		echo $html;
	}

	/**
	 * Get output inputs.
	 *
	 * @see Core_Gateway::get_output_fields()
	 *
	 * @param Payment $payment
	 *            Payment.
	 *
	 * @return array
	 */
	public function get_output_fields( Payment $payment ) {
		$vpa        = $this->config->vpa;
		$payee_name = rawurlencode( $this->config->payee_name );
		if ( empty( $payee_name ) ) {
			$payee_name = get_bloginfo();
		}
		if ( empty( $payee_name ) ) {
			throw new \Exception( 'The Payee Name is blank. Kindly set it from the UPI QR Configuration page.' );
		}
		if ( empty( $vpa ) ) {
			throw new \Exception( 'UPI ID is blank. Kindly set it from the UPI QR Configuration page.' );
		}

		// @see https://developers.google.com/pay/india/api/web/create-payment-method
		$data['pa'] = $vpa;
		$data['pn'] = $payee_name;
		if ( ! empty( $this->config->merchant_category_code ) ) {
			$data['mc'] = $this->config->merchant_category_code;
		}
		$data['tr'] = $payment->get_transaction_id();
		// $data['url'] = ''; // Invoice/order details URL
		$data['am'] = $payment->get_total_amount()->number_format( null, '.', '' );
		$data['cu'] = $payment->get_total_amount()->get_currency()->get_alphabetic_code();

		// $data['tid'] = $payment->get_transaction_id();
		$data['tn'] = rawurlencode( substr( trim( $payment->get_description() ), 0, 75 ) );

		return $data;
	}

	/**
	 * Update status of the specified payment.
	 *
	 * @param Payment $payment
	 *            Payment.
	 */
	public function update_status( Payment $payment ) {
		$transaction_id = filter_input( INPUT_POST, 'transaction_id', FILTER_SANITIZE_STRING );
		$pay_status     = filter_input( INPUT_POST, 'pay-status', FILTER_SANITIZE_STRING );

		if ( $pay_status === 'Cancel' ) {
			$payment->add_note( 'Payment Cancelled.' );
			$payment->set_status( PaymentStatus::CANCELLED );
			return;
		}

		if ( empty( $transaction_id ) && Integration::SHOW_REQUIRED_FIELD === $this->config->transaction_id_field ) {
			$payment->add_note( 'Transaction ID not provided.' );
			$payment->set_status( PaymentStatus::FAILURE );
			return;
		}

		$payment->set_transaction_id( $transaction_id );

		$payment->set_status( $this->config->payment_success_status );
	}

	/**
	 * Redirect via HTML.
	 *
	 * @param Payment $payment The payment to redirect for.
	 * @return void
	 */
	public function redirect_via_html( Payment $payment ) {
		if ( PaymentStatus::OPEN !== $payment->get_status() ) {
			wp_safe_redirect( $payment->get_return_redirect_url() );
			exit;
		}

		if ( ! $this->supports( 'payment_status_request' ) ) {
			parent::redirect_via_html( $payment );
		}

		\wp_register_style(
			'knit-pay-upi-qr-template-2',
			KNITPAY_URL . '/gateways/upi-qr/src/css/template2.css',
			[],
			KNITPAY_VERSION
		);

		\wp_enqueue_script( 'knit-pay-easy-qrcode' );
		\wp_enqueue_script( 'knit-pay-upi-qr-template-2' );

		define( 'KNIT_PAY_PHONE_PE_BUSINESS_IMAGE_URL', plugins_url( 'images/', __FILE__ ) );

		if ( headers_sent() ) {
			parent::redirect_via_html( $payment );
		} else {
			Core_Util::no_cache();

			include 'view/template2.php';
		}

		exit;
	}

	public function get_intent_url_parameters( $payment ) {
		if ( isset( $this->intent_url_parameters ) ) {
			return $this->intent_url_parameters;
		}
		$this->intent_url_parameters = $this->get_output_fields( $payment );
		return $this->intent_url_parameters;
	}

	public function enqueue_scripts() {
		\wp_register_script(
			'knit-pay-easy-qrcode',
			KNITPAY_URL . '/gateways/upi-qr/src/js/easy.qrcode.min.js',
			[],
			'4.6.0',
			true
		);

		\wp_register_script(
			'knit-pay-upi-qr-template-2',
			KNITPAY_URL . '/gateways/upi-qr/src/js/template2.js',
			[ 'jquery' ],
			KNITPAY_VERSION,
			true
		);

		wp_localize_script(
			'knit-pay-upi-qr-template-2',
			'knit_pay_upi_qr_vars',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}
}
