
function generateQR(user_input) {
	jQuery('.appPayment').css('display', 'none');
	jQuery('#backBtn').attr('onclick', 'qr_back();')
	document.getElementById('qrCodeWrapper').style.display = 'flex';
	jQuery('.qrCodeBody').html('');
	var qrcode = new QRCode(document.querySelector('.qrCodeBody'), {
		text: user_input,
		width: 250, //default 128
		height: 250,
		colorDark: '#000000',
		colorLight: '#ffffff',
		correctLevel: QRCode.CorrectLevel.H
	});
	paymentClicked();
}


function qr_back() {
	document.getElementById('qrCodeWrapper').style.display = 'none';
	jQuery('.appPayment').css('display', 'flex');
	jQuery('#backBtn').attr('onclick', 'cancelOrder();')
	//document.getElementById('continue-first-btn').style.display = 'none';
}

function paymentClicked() {
	//document.getElementById('continue-first-btn').style.display = 'block';
}

function paynow() {
	document.getElementById('payment-first').style.display = 'none';
	document.getElementById('payment-second').style.display = 'block';
	document.getElementById('payment-third').style.display = 'none';
}

function paynow_back() {
	document.getElementById('payment-first').style.display = 'block';
	document.getElementById('payment-second').style.display = 'none';
	document.getElementById('payment-third').style.display = 'none';
	//document.getElementById('continue-first-btn').style.display = 'none';
}

function cancelOrder() {
	document.getElementById('payment-first').style.display = 'none';
	document.getElementById('payment-second').style.display = 'none';
	document.getElementById('payment-third').style.display = 'block';
}

function continueShopping() {
	document.getElementById('formCancel').submit();
}

function knit_pay_check_payment_status() {
	jQuery.post(knit_pay_upi_qr_vars.ajaxurl, {
		'action': 'knit_pay_upi_qr_payment_status_check',
		'knit_pay_transaction_id': document.querySelector('input[name=knit_pay_transaction_id]').value,
		'knit_pay_payment_id': document.querySelector('input[name=knit_pay_payment_id]').value,
		'knit_pay_nonce': document.querySelector('input[name=knit_pay_nonce]').value
	},
		function(msg) {
			console.log(msg);

			if (msg.data == 'Success') {
				Swal.fire('Your Payment Recived Successfully', 'Please Wait!', 'success')

				setTimeout(function() {
					document.getElementById('formSubmit').submit();
				}, 200);
			}
		});
}
setInterval(knit_pay_check_payment_status, 6000);