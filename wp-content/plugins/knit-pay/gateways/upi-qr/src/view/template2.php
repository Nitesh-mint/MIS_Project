<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<title><?php esc_html_e( 'Payment Page', 'knit-pay-lang' ); ?></title>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name='viewport' content='width=device-width, initial-scale=1'>
<meta http-equiv = "refresh" content = "300; url = <?php echo add_query_arg( 'status', 'Expired', $payment->get_return_url() ); ?>" />
<?php
wp_head();
wp_print_styles( 'knit-pay-upi-qr-template-2' );
?>
</head>

<body>
	<?php
	$transaction_id = $payment->get_transaction_id();

	$amount          = $payment->get_total_amount()->format_i18n( '%1$s%2$s' );
	$redirect_url    = $payment->get_return_url();
	$image_path      = KNIT_PAY_PHONE_PE_BUSINESS_IMAGE_URL;
	$hide_pay_button = $this->config->hide_pay_button; // TODO make dynamic

	if ( ! wp_is_mobile() ) {
		$hide_pay_button = true;
	}

	$page = "
    <form id='formCancel' action='" . $redirect_url . "' method='post' style='display:none;'>
<input type='hidden' name='status' value='Falied' >
</form>

  <form id='formSubmit' action='" . $redirect_url . "' method='post' style='display:none;'>
<input type='hidden' name='status' value='Success' >
<input type='hidden' name='knit_pay_transaction_id' value='" . $transaction_id . "' >
<input type='hidden' name='knit_pay_payment_id' value='" . $payment->get_id() . "' >";

	$nonce_action = 'knit_pay_payment_status_check|' . $payment->get_id() . "|$transaction_id";
	$page        .= wp_nonce_field( $nonce_action, 'knit_pay_nonce' );

	$page .= '</form>';

	$page .= "<div class='paymentWrapper'>
    <div class='paymentWrapperCard'>
      <div class='paymentContainer' id='payment-first'>
        <div class='topPaymentWrapper'>
          <img src='{$image_path}back_icon.svg' alt='back button' id='backBtn' onclick='cancelOrder()'>
          <h1>Choose a payment option</h1>
          <p>Payable Now <span style='font-weight:800;'>" . $amount . "</span></p>
          <p class='orderid'>Transation Number : " . $transaction_id . "</p>
        </div>
        <div class='paymentMethodWrapper'>
          <div class='methodHeading'>Payment Options</div>";
	if ( ! $hide_pay_button ) {

		$page .= "<a href='" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'gpay://upi/pay' ) . "'  class='methodsWrapper appPayment'>
            <div class='leftSide'>
              <div>
                <img src='{$image_path}gpay_icon.svg' alt='gpay'>
              </div>
              <div>
                <p style='margin-bottom:0.3rem;font-weight:bold;'>Google Pay</p>
                <span>Pay with Google Pay UPI</span>
              </div>
            </div>
            <div class='rightSide'>
              <img src='{$image_path}right_icon.svg' alt='button'>
            </div>
          </a>
          <a href='" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'phonepe://pay' ) . "' class='methodsWrapper appPayment'>
            <div class='leftSide'>
              <div>
                <img src='{$image_path}phonepe.svg' alt='phonepe'>
              </div>
              <div>
                <p style='margin-bottom:0.3rem;font-weight:bold;'>PhonePe</p>
                <span>Pay with PhonePe UPI</span>
              </div>
            </div>
            <div class='rightSide'>
              <img src='{$image_path}right_icon.svg' alt='button'>
            </div>
          </a>
          <a href='" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'paytmmp://pay' ) . "'  class='methodsWrapper appPayment'>
            <div class='leftSide'>
              <div>
                <img src='{$image_path}paytm_icon.svg' alt='paytm'>
              </div>
              <div>
                <p style='margin-bottom:0.3rem;font-weight:bold;'>Paytm</p>
                <span>Pay with Paytm UPI</span>
              </div>
            </div>
            <div class='rightSide'>
              <img src='{$image_path}right_icon.svg' alt='button'>
            </div>
          </a>
		      <a href='" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'bhim://pay' ) . "'  class='methodsWrapper appPayment'>
            <div class='leftSide'>
              <div>
                <img src='{$image_path}bhim_icon.svg' alt='paytm'>
              </div>
              <div>
                <p style='margin-bottom:0.3rem;font-weight:bold;'>BHIM UPI</p>
                <span>Pay with BHIM UPI</span>
              </div>
            </div>
            <div class='rightSide'>
              <img src='{$image_path}right_icon.svg' alt='button'>
            </div>
          </a>
          <a href='" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'whatsapp://pay' ) . "'  class='methodsWrapper appPayment'>
            <div class='leftSide'>
              <div>
                <img src='{$image_path}whatspp_pay.svg' alt='paytm'>
              </div>
              <div>
                <p style='margin-bottom:0.3rem;font-weight:bold;'>Whatsapp Pay</p>
                <span>Pay with Whatsapp Pay UPI</span>
              </div>
            </div>
            <div class='rightSide'>
              <img src='{$image_path}right_icon.svg' alt='button'>
            </div>
          </a>
          <a href='" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'upi://pay' ) . "'  class='methodsWrapper appPayment'>
            <div class='leftSide'>
              <div>
                <img src='{$image_path}upi_icon.svg' alt='UPI'>
              </div>
              <div>
                <p style='margin-bottom:0.3rem;font-weight:bold;'>UPI</p>
                <span>Pay with any UPI App</span>
              </div>
            </div>
            <div class='rightSide'>
              <img src='{$image_path}right_icon.svg' alt='button'>
            </div>
          </a>";
	}
	if ( ! wp_is_mobile() || ! $this->config->hide_mobile_qr ) {
		$page .= "<a href='javascript:void(0)' onclick='generateQR(\"" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'upi://pay' ) . "\");' class='methodsWrapper'>
            <div class='leftSide'>
              <div>
                <img src='{$image_path}qr_icon.svg' alt='qr_icon'>
              </div>
              <div>
                <p style='margin-bottom:0.3rem;font-weight:bold;'>Show QRCode</p>
                <span>Pay with Any UPI App</span>
              </div>
            </div>
            <div class='rightSide'>
              <img src='{$image_path}right_icon.svg' alt='button'>
            </div>
          </a>
          <div class='qrCodeWrapper' id='qrCodeWrapper' style='display: none;'>
            <a href='" . add_query_arg( $this->get_intent_url_parameters( $payment ), 'upi://pay' ) . "'><div class='qrCodeBody'></div></a>
            <!-- <div class='btnWrapper'>
              <button class='paymentContinueBtn' onclick='qr_back();'>Back</button>
            </div> -->
          </div>";
	}
		$page .= "</div>
       
        <!-- For now don't allow to enter UTR. It will be used latter.
        <div class='btnWrapper' id='continue-first-btn' style='display:none;'>
          <button class='paymentContinueBtn' onclick='paynow();'>Continue</button>
        </div> -->
      </div>
      <div class='paymentContainer' id='payment-second' style='display:none'>
        <div class='topPaymentWrapper'>
          <img src='{$image_path}back_icon.svg' alt='back button' onclick='paynow_back()'>
          <h1>Transaction Details</h1>
          <p style='padding: 0 1rem;margin-top:1.5rem;'>Please enter transaction details to validate payment!</p>
        </div>
        <div class='paymentMethodWrapper'>
          <div class='inputWrapper' style='margin-top:2rem'>
            <label for='customerUTRNumber'>UTR Number (12 Digits)</label>
            <input type='number' id='customerUTRNumber' name='customerUTRNumber' autocomplete='off' oninput='validateUTRNumber(this);' onkeydown='if(this.value.length==12 && event.keyCode!=8) return false;'/>
          </div>
          <div class='btnWrapper utrContinueBtn' style='display:none;'>
            <button class='cardPaymentButton' onclick='orderPlaced();'>Continue</button>
          </div>
        </div>
      </div>
      <div class='paymentContainer' id='payment-third' style='display:none;'>
          <div class='topPaymentWrapper'>
              <h1>Order Cancelled!</h1>
              <p style='padding: 0 1rem;margin-top:1.5rem;'>You have cancelled the order! If cancelled by mistake try again.</p>
            </div>
          <div class='successIconWrapper'>
            <img src='{$image_path}unchecked.svg' alt='Success Icon'>
            <p style='margin-top:2rem;margin-bottom:0rem;'>Your order has been cancelled!</p>
          </div>
          <div class='btnWrapper' style='margin: 2rem 2rem 0rem 2rem;'>
            <button onclick='continueShopping();'>Continue Shopping</button>
          </div>
          <div class='btnWrapper' style='margin: 2rem 2rem 0rem 2rem;'>
            <button onclick='paynow_back();'>Retry</button>
          </div>
      </div>
      <div class='paymentFooter'>
        <div class='innerWrapper'>
          <p>Powered By</p>
          <div style='display: flex;gap: 14px;margin:10px;'>
            <img src='{$image_path}upi.svg' alt='UPI Icon'>
          </div>
        </div>
      </div>
    </div>
  </div>";
	wp_footer();
	if ( ! ( wp_is_mobile() ) ) {
		$page .= '<script>';
		$page .= 'generateQR("' . add_query_arg( $this->get_intent_url_parameters( $payment ), 'upi://pay' ) . '");';
		$page .= "jQuery('#backBtn').attr('onclick', 'cancelOrder();');";
		$page .= '</script>';
	}
	echo $page;
	?>
</body>

</html>

<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
