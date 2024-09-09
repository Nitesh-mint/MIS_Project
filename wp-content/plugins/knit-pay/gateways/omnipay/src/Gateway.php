<?php
namespace KnitPay\Gateways\Omnipay;

use Pronamic\WordPress\Pay\Core\Gateway as Core_Gateway;
use Exception;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\CreditCard;

/**
 * Title: Omnipay Gateway
 * Copyright: 2020-2024 Knit Pay
 *
 * @author Knit Pay
 * @version 8.72.0.0
 * @since 8.72.0.0
 */
class Gateway extends Core_Gateway {
	private $omnipay_gateway;

	private $transaction_response;

	/**
	 * Initializes an Omnipay gateway
	 *
	 * @param Config $config
	 *            Config.
	 */
	public function init( AbstractGateway $omnipay_gateway ) {

		// Supported features.
		$this->supports = [
			'payment_status_request',
		];

		$this->omnipay_gateway = $omnipay_gateway;
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

		$transaction_data = $this->get_payment_data( $payment );

		// Do a purchase transaction on the gateway
		$transaction = $this->omnipay_gateway->purchase( $transaction_data );
		$response    = $transaction->send();

		if ( $response->isSuccessful() ) {
			// Successful Payment.
			$payment->set_status( PaymentStatus::SUCCESS );
			$payment->set_transaction_id( $response->getTransactionReference() );
		} elseif ( $response->isRedirect() ) {
			if ( 'POST' === $response->getRedirectMethod() ) {
				$this->set_method( self::METHOD_HTML_FORM );
			} else {
				$this->set_method( self::METHOD_HTTP_REDIRECT );
			}
			$this->transaction_response = $response;
			$payment->set_action_url( $response->getRedirectUrl() );
			$payment->set_transaction_id( $response->getTransactionReference() );
		} else {
			$payment->set_transaction_id( $payment->key . '_' . $payment->get_id() );
			if ( ! is_null( $response->getMessage() ) ) {
				 throw new \Exception( $response->getMessage() );
			} elseif ( isset( $response->getData()->message ) ) {
				throw new \Exception( $response->getData()->message );
			} else {
				throw new \Exception( 'Something went wrong.' );
			}
		}
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
		return $this->transaction_response->getRedirectData();
	}

	/**
	 * Get Payment Data.
	 *
	 * @param Payment $payment
	 *            Payment.
	 *
	 * @return array
	 */
	private function get_payment_data( Payment $payment ) {

		$customer         = $payment->get_customer();
		$billing_address  = $payment->get_billing_address();
		$delivery_address = $payment->get_shipping_address();

		$order_amount        = $payment->get_total_amount()->number_format( null, '.', '' );
		$order_currency      = $payment->get_total_amount()->get_currency()->get_alphabetic_code();
		$payment_description = $payment->get_description();
		$transaction_id      = $payment->key . '_' . $payment->get_id();
		
		// @see https://omnipay.thephpleague.com/api/cards/
		$credit_card = [
			'firstName',
			'lastName',
			'number',
			'expiryMonth',
			'expiryYear',
			'startMonth',
			'startYear',
			'cvv',
			'issueNumber',
			'type',
			'billingAddress1' => $billing_address->get_line_1(),
			'billingAddress2' => $billing_address->get_line_2(),
			'billingCity'     => $billing_address->get_city(),
			'billingPostcode' => $billing_address->get_postal_code(),
			'billingState'    => $billing_address->get_region(),
			'billingCountry'  => $billing_address->get_country_name(),
			'billingPhone'    => $billing_address->get_phone(),
			'company'         => $billing_address->get_company_name(),
			'email'           => $customer->get_email(),
		];

		if ( ! is_null( $customer->get_name() ) ) {
			$credit_card['firstName'] = $customer->get_name()->get_first_name();
			$credit_card['lastName']  = $customer->get_name()->get_last_name();
		}

		if ( ! empty( $delivery_address ) ) {
			$credit_card['shippingAddress1'] = $delivery_address->get_line_1();
			$credit_card['shippingAddress2'] = $delivery_address->get_line_2();
			$credit_card['shippingCity']     = $delivery_address->get_city();
			$credit_card['shippingState']    = $delivery_address->get_region();
			$credit_card['shippingPostcode'] = $delivery_address->get_postal_code();
			$credit_card['shippingCountry']  = $delivery_address->get_country();
			$credit_card['shippingPhone']    = $delivery_address->get_phone();
		}
		
		$card = new CreditCard( $credit_card );
		
		// @see https://omnipay.thephpleague.com/api/authorizing/
		return [
			'card'                 => $card,
			'amount'               => $order_amount,
			'currency'             => $order_currency,
			'description'          => $payment_description,
			'transactionId'        => $transaction_id,
			'transactionReference' => $transaction_id,
			'clientIp'             => $customer->get_ip_address(),
			'returnUrl'            => $payment->get_return_url(),
			'cancelUrl'            => $payment->get_return_url(),
			'notifyUrl'            => $payment->get_return_url(),
			'email'                => $customer->get_email(),
		];
	}

	/**
	 * Update status of the specified payment.
	 *
	 * @param Payment $payment
	 *            Payment.
	 */
	public function update_status( Payment $payment ) {
		if ( PaymentStatus::SUCCESS === $payment->get_status() || PaymentStatus::EXPIRED === $payment->get_status() ) {
			return;
		}
		
		$transaction_data = $this->get_payment_data( $payment );
		
		// Do a purchase transaction on the gateway
		$transaction = $this->omnipay_gateway->completePurchase( $transaction_data );
		$response    = $transaction->send();
		
		$payment->add_note( '<strong>Response Data:</strong><br><pre>' . print_r( $response->getData(), true ) . '</pre><br>' );
		if ( $response->isSuccessful() ) {
			$payment->set_transaction_id( $response->getTransactionReference() );
			$payment->set_status( PaymentStatus::SUCCESS );
		}
	}
}
