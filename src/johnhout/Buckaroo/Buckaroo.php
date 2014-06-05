<?php namespace johnhout\Buckaroo;

use johnhout\Buckaroo\SOAP\Services;
use Whoops\Example\Exception;
use Config;

/**
 * Class Buckaroo
 *
 * @package johnhout\Buckaroo (floristenhove\Buckaroo fork)
 *
 * Buckaroo BPE3 API client for Laravel 4
 * Made by: John in 't Hout - U-Lab.nl
 * Tips or suggestions can be mailed to john.hout@u-lab.nl or check github.
 * Thanks to Joost Faasen from johnhout for helping the SOAP examples / client.
 */

class Buckaroo
{
	/**
	 * Returns wether the
	 *
	 * @var bool
	 */
	private static $_success = array();

	static function getSuccess() {
		$class = get_called_class();
		if(isset(self::$_success[$class])) {
			$success  = self::$_success[$class];
        }
		else {
			$success = false;
		}

		return $success;
	}

	static function setSuccess($value) {
		$class = get_called_class();
		self::$_success[$class] = $value;
	}

	/**
	 * Holds the given errors
	 *
	 * @var array
	 */
	private static $_errors = array();

	static function getErrors() {
		$class = get_called_class();
		if(isset(self::$_errors[$class])) {
			$errors = self::$_errors[$class];
        }
		else {
			$errors = array();
		}

		return $errors;
	}

	static function addError($value) {
		$class = get_called_class();
		self::$_errors[$class][] = $value;
	}

	/**
	 * Retrieving transaction data with a given Invoice number.
	 *
	 * @param $invoiceId
	 *
	 * @return array|string
	 */
	public function transactionInfo($invoiceId)
	{
		$this->request = new \johnhout\Buckaroo\Request();

		$InvoiceInfoRequest                  = new SOAP\Body();
		$InvoiceInfoRequest->Invoice         = array();
		$InvoiceInfoRequest->Invoice         = new \stdClass();
		$InvoiceInfoRequest->Invoice->Number = trim($invoiceId);
		$bpeResponse                         = $this->request->sendRequest($InvoiceInfoRequest, 'invoiceinfo');

		if( isset($bpeResponse->Transactions->Transaction) )
		{
			return $bpeResponse->Transactions->Transaction;
		}

		self::addError('Order ' . $invoiceId . ' not found.');
	}

	/**
	 * Add a refund based on an given Invoice number
	 *
	 * @param $dataArray
	 *
	 * @return mixed
	 * @throws \Whoops\Example\Exception
	 */
	public function refund($invoice, $amount)
	{
		$orderBPEdata = Buckaroo::transactionInfo($invoice);

		$transactionInfo = false;
		if( is_array($orderBPEdata) )
		{
			foreach($orderBPEdata as $value)
			{
				if( $value->Status->Success )
				{
					$transactionInfo = $value;
				}
			}
		}
		else
		{
			if( $orderBPEdata->Status->Success )
			{
				$transactionInfo = $orderBPEdata;
			}
		}

		if( !$transactionInfo )
		{
			self::addError('Order has not been payed yet.');
		}
		else
		{
			$this->request = new Request(Config::get('buckaroo::website_key'));

			$RefundInfoRequest                                = new SOAP\Body();
			$RefundInfoRequest->RefundInfo                    = array();
			$RefundInfoRequest->RefundInfo[0]                 = new \stdClass();
			$RefundInfoRequest->RefundInfo[0]->TransactionKey = $transactionInfo->ID;

			$BPEresponse = $this->request->sendRequest($RefundInfoRequest, 'refundinfo');

			if( $amount <= $BPEresponse->RefundInfo->MaximumRefundAmount and $BPEresponse->RefundInfo->IsRefundable )
			{
				$this->TransactionRequest = new \johnhout\Buckaroo\Request(Config::get('buckaroo::website_key'));

				$TransactionRequest                         = new SOAP\Body();
				$TransactionRequest->Currency               = $BPEresponse->RefundInfo->RefundCurrency;
				$TransactionRequest->AmountCredit           = $amount;
				$TransactionRequest->Invoice                = $invoice;
				$TransactionRequest->Description            = 'Retourbetaling ' . $invoice;
				$TransactionRequest->OriginalTransactionKey = $transactionInfo->ID;

				$TransactionRequest->Services          = new Services();
				$TransactionRequest->Services->Service = new SOAP\Service($BPEresponse->RefundInfo->ServiceCode, 'Refund', '');

				if( $BPEresponse->RefundInfo->ServiceCode == 'ideal' )
				{
					$TransactionRequest->Services->Service->RequestParameter = new SOAP\RequestParameter('issuer', $transactionInfo->ID);
				}

				self::setSuccess(true);

				return $this->TransactionRequest->sendRequest($TransactionRequest, 'transaction');

			}
			else
			{
				self::addError('The maximum refund amount is to low. Or the order is not refundable.');
			}
		}

	}

	/**
	 * Check an order if it has been payed with a given Invoice number.
	 *
	 * @param $invoiceId
	 *
	 * @return bool
	 */
	public function checkInvoiceForSuccess($invoiceId)
	{
		if( !$invoiceId )
		{
			self::addError('The maximum refund amount is to low. Or the order is not refundable.');
		}
		else
		{
			$orderBPEdata = Buckaroo::getTransactionInfo($invoiceId);

			$transactionInfo = false;
			if( is_array($orderBPEdata) )
			{
				foreach($orderBPEdata as $value)
				{
					if( $value->Status->Success )
					{
						$transactionInfo = $value;
					}
				}
			}
			else
			{
				if( $orderBPEdata->Status->Success )
				{
					$transactionInfo = $orderBPEdata;
				}
			}

			self::setSuccess(true);

			return (!$transactionInfo) ? false : true;
		}
	}

	/**
	 * Returns a form for submission to Buckaroo.
	 *
	 * @param      $dataArray
	 * @param null $button
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function createForm($dataArray, $button = null)
	{
		if( !$dataArray['brq_amount'] )
		{
			self::addError('Amount has not been set.');
		}
		elseif( !$dataArray['brq_invoicenumber'] )
		{
			self::addError('Amount has not been set.');
		}
		else
		{
			$dataArray['bpe_signature'] = self::createSignature($dataArray);
			$dataArray['bpe_url']       = ((Config::get('buckaroo::test_mode')) ? Config::get('buckaroo::bpe_post_test_url') : Config::get('buckaroo::bpe_post_url'));
			$dataArray['button']        = $button;

			self::setSuccess(true);

			return \View::make('buckaroo::SubmitForm', $dataArray);
		}
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function createSignature($data)
	{

		$hashString = '';
		// Add additional data to array
		$data['brq_websitekey'] = Config::get('buckaroo::website_key');
		$data['brq_currency']   = Config::get('buckaroo::currency');
		$data['brq_culture']    = Config::get('buckaroo::culture');
		$data['brq_return']     = Config::get('buckaroo::return_url');

		ksort($data);

		foreach($data as $arrKey => $arrValue)
		{
			$hashString .= strtolower($arrKey) . '=' . $arrValue;
		}

		$hashString .= Config::get('buckaroo::secret_key');

		return sha1($hashString);
	}

	/**
	 * @param $message
	 */
//	public function addError($message)
//	{
//		array_push(self::$errors, array('message' => $message));
//	}

	/**
	 * @return bool
	 */
	public function success()
	{
		return self::getSuccess();
	}

	/**
	 * @return array
	 */
	public function errors()
	{
		return self::$errors;
	}




}