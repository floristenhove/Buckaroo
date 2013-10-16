<?php

namespace johnhout\Buckaroo\SOAP;

class RefundInfo
{
	public $TransactionKey;
	
	public function __construct($Transactionkey) {
		$this->TransactionKey = $Transactionkey;
	}
}

