<?php

class smsfa extends WP_SMS {
	private $wsdl_link = "http://smsfa.net/API/Send.asmx?WSDL";
	public $tariff = "http://smsfa.net/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "09xxxxxxxx";

		ini_set( "soap.wsdl_cache_enabled", "0" );
	}

	public function SendSMS() {

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 *
		 * @param string $this ->from sender number.
		 */
		$this->from = apply_filters( 'wp_sms_from', $this->from );

		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 *
		 * @param array $this ->to receiver number
		 */
		$this->to = apply_filters( 'wp_sms_to', $this->to );

		/**
		 * Modify text message
		 *
		 * @since 3.4
		 *
		 * @param string $this ->msg text message.
		 */
		$this->msg = apply_filters( 'wp_sms_msg', $this->msg );

		// Check gateway credit
		if ( is_wp_error( $this->GetCredit() ) ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $this->GetCredit()->get_error_message(), 'error' );

			return $this->GetCredit();
		}

		$client = new SoapClient( $this->wsdl_link );

		$result = $client->SendSms(
			array(
				'username' => $this->username,
				'password' => $this->password,
				'from'     => $this->from,
				'to'       => $this->to,
				'text'     => $this->msg,
				'flash'    => false,
				'udh'      => ''
			)
		);

		if ( $result ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $result );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */
			do_action( 'wp_sms_send', $result );

			return $result;
		}
		// Log the result
		$this->log( $this->from, $this->msg, $this->to, $result, 'error' );

		return new WP_Error( 'send-sms', $result );
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms' ) );
		}

		if ( ! class_exists( 'SoapClient' ) ) {
			return new WP_Error( 'required-class', __( 'Class SoapClient not found. please enable php_soap in your php.', 'wp-sms' ) );
		}

		try {
			$client = new SoapClient( $this->wsdl_link );
		} catch ( Exception $e ) {
			return new WP_Error( 'account-credit', $e->getMessage() );
		}

		$result = $client->Credit( array( 'username' => $this->username, 'password' => $this->password ) );

		return $result->CreditResult;
	}
}