<?php namespace johnhout\Buckaroo;

/**
 * Class Buckaroo
 *
 * @package johnhout\Buckaroo
 *
 * Buckaroo BPE3 API client for Laravel 4
 * Made by: John in 't Hout - U-Lab.nl
 * Tips or suggestions can be mailed to john.hout@u-lab.nl or check github.
 * Thanks to Joost Faasen from johnhout for helping the SOAP examples / client.
 */

class Buckaroo
{

	public $request;

	/**
	 * Adding a invoice to the system
	 *
	 * @param null $currency
	 * @param null $amount
	 * @param null $invoice
	 * @param null $description
	 * @param null $return_url
	 * @param null $start_recurrent
	 *
	 * @throws \Exception
	 */
	public function addInvoice($currency = NULL, $amount = NULL, $invoice = NULL, $description = NULL, $return_url = NULL, $start_recurrent = NULL) {
		$this->request = new \johnhout\Buckaroo\Request();

		if( $currency === NULL ) {
			$currency = \Config::get('buckaroo::currency');
		}
		if( $amount === NULL ) {
			throw new \Exception('Amount is missing, or cannot be empty');
		}
		if( $invoice === NULL ) {
			throw new \Exception('Invoice is missing, or cannot be empty');
		}
		if( $description === NULL ) {
			$description = 'Payment with the API';
		}
		if( $start_recurrent === NULL ) {
			$start_recurrent = \Config::get('buckaroo::start_recurrent');
		}
		if( $return_url === NULL ) {
			$return_url = \Config::get('buckaroo::start_recurrent');
		}

		// Create the request
		$TransactionRequest                 = new \johnhout\Buckaroo\SOAP\Body();
		$TransactionRequest->Currency       = $currency;
		$TransactionRequest->AmountDebit    = $amount;
		$TransactionRequest->Invoice        = $invoice;
		$TransactionRequest->Description    = $description;
		$TransactionRequest->ReturnURL      = \Config::get('buckaroo::return_url');
		$TransactionRequest->StartRecurrent = $start_recurrent;

		// Specify which service / action we are calling
		$TransactionRequest->Services = new \johnhout\Buckaroo\SOAP\Services();

		$TransactionRequest->Services->Service = new \johnhout\Buckaroo\SOAP\Service('ideal', 'Pay', 1);

		// Add parameters for this service
		$TransactionRequest->Services->Service->RequestParameter = new \johnhout\Buckaroo\SOAP\RequestParameter('issuer', $invoice);

		// Optionally pass the client ip-address for logging
		$TransactionRequest->ClientIP = new \johnhout\Buckaroo\SOAP\IPAddress(\Request::getClientIp());

		// Send the request to Buckaroo, and retrieve the response
		$response = $this->request->sendRequest($TransactionRequest, 'transaction');
	}

	/**
	 * Retrieving invoice information with a given Invoice number.
	 *
	 * @param $invoiceId
	 *
	 * @return array|string
	 */
	public function getInvoiceInfo($invoiceId) {

		$this->request = new Request(\Config::get('buckaroo::website_key'));

		$InvoiceInfoRequest          = new \johnhout\Buckaroo\SOAP\Body();
		$InvoiceInfoRequest->Invoice = array();


		$InvoiceInfoRequest->Invoice         = new \stdClass();
		$InvoiceInfoRequest->Invoice->Number = trim($invoiceId);
		$bpeResponse                         = $this->request->sendRequest($InvoiceInfoRequest, 'invoiceinfo');

		$paymentStatus   = NULL;
		$paymentDatetime = NULL;
		$currency        = NULL;
		$TypeDescription = NULL;
		$status_msg      = NULL;
		$test            = 0;

		if( isset($bpeResponse->Transactions) ) {

			if( !isset($bpeResponse->Transactions->Transaction->ID) ) {
				foreach($bpeResponse->Transactions->Transaction as $transaction) {
					if( $transaction->Status->Code == 190 ) {
						$paymentStatus   = 190;
						$paymentDatetime = strtotime($transaction->Status->Datetime);
						$currency        = $transaction->Currency;
						exit;
					}
					else {
						if( $paymentDatetime == NULL ) {
							$paymentStatus   = $transaction->Status->Code;
							$TypeDescription = $transaction->TypeDescription;
							$status_msg      = $transaction->Status->Message;
							$paymentDatetime = strtotime($transaction->Status->Datetime);
							$test            = (isset($transaction->Test) ? $transaction->Test : 0);

						}
						elseif( $paymentDatetime < strtotime($transaction->Status->Datetime) ) {
							$paymentStatus   = $transaction->Status->Code;
							$paymentDatetime = strtotime($transaction->Status->Datetime);
							$TypeDescription = $transaction->TypeDescription;
							$status_msg      = $transaction->Status->Message;
							$test            = (isset($transaction->Test) ? $transaction->Test : 0);
						}

					}
				}
				$attempts = count($bpeResponse->Transactions->Transaction);

				return array(
					'attempts'         => $attempts,
					'datetime'         => $paymentDatetime,
					'type_description' => $TypeDescription,
					'status'           => $paymentStatus,
					'status_msg'       => $status_msg,
					'currency'         => $currency,
					'test'             => $test

				);
			}
			else {
				return array(
					'id'               => $bpeResponse->Transactions->Transaction->ID,
					'attempts'         => 1,
					'type_description' => $bpeResponse->Transactions->Transaction->TypeDescription,
					'datetime'         => $bpeResponse->Transactions->Transaction->Status->Datetime,
					'status'           => $bpeResponse->Transactions->Transaction->Status->Code,
					'status_msg'       => $bpeResponse->Transactions->Transaction->Status->Message,
					'currency'         => $bpeResponse->Transactions->Transaction->Currency,
					'test'             => (isset($bpeResponse->Transactions->Transaction->Test) ? $bpeResponse->Transactions->Transaction->Test : 0),
				);


			}
		}

		return 'Order ' . $invoiceId . ' not found.';
	}

	/**
	 * Check an invoice if it has been payed.
	 *
	 * @param $invoiceId
	 *
	 * @return bool
	 */
	public function checkInvoiceForSuccess($invoiceId) {
		$this->request = new \johnhout\Buckaroo\Request();

		$InvoiceInfoRequest          = new \johnhout\Buckaroo\SOAP\Body();
		$InvoiceInfoRequest->Invoice = array();

		$InvoiceInfoRequest->Invoice         = new \stdClass();
		$InvoiceInfoRequest->Invoice->Number = trim($invoiceId);
		$bpeResponse                         = $this->request->sendRequest($InvoiceInfoRequest, 'invoiceinfo');


		if( isset($bpeResponse->Transactions) ) {
			if( !isset($bpeResponse->Transactions->Transaction->ID) ) {
				foreach($bpeResponse->Transactions->Transaction as $transaction) {
					if( $transaction->Status->Code == 190 ) {
						return true;
					}
				}
			}
			else {
				if( $bpeResponse->Transactions->Transaction->Status->Code == 190 ) {
					return true;
				}
			}

			return false;
		}

		return false;
	}

	/**
	 * Returns a form for submission to Buckaroo.
	 *
	 * @param $dataArray
	 * @param null $button
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function createForm($dataArray, $button = NULL) {

		if( !$dataArray['brq_amount'] ) {
			throw new \Exception('Amount has not been set.');
		}
		if( !$dataArray['brq_invoicenumber'] ) {
			throw new \Exception('Invoice number has not been set.');
		}

		$dataArray['bpe_signature'] = self::createSignature($dataArray);
		$dataArray['bpe_url']       = ((\Config::get('buckaroo::test_mode')) ? \Config::get('buckaroo::bpe_post_test_url') : \Config::get('buckaroo::bpe_post_url'));
		$dataArray['button']        = $button;

		return \View::make('buckaroo::SubmitForm', $dataArray);
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function createSignature($data) {

		$hashString = '';
		// Add additional data to array
		$data['brq_websitekey'] = \Config::get('buckaroo::website_key');
		$data['brq_currency']   = \Config::get('buckaroo::currency');
		$data['brq_culture']    = \Config::get('buckaroo::culture');
		$data['brq_return']     = \Config::get('buckaroo::return_url');

		ksort($data);

		foreach($data as $arrKey => $arrValue) {
			$hashString .= strtolower($arrKey) . '=' . $arrValue;
		}

		$hashString .= \Config::get('buckaroo::secret_key');

		return sha1($hashString);
	}


}