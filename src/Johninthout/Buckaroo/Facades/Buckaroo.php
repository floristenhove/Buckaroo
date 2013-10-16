<?php namespace johnhout\Buckaroo\Facades;

/**
 * Class Buckaroo
 *
 * @package johnhout\Buckaroo
 *
 * Buckaroo BPE3 API client for Laravel 4
 * Made by: John in 't Hout - U-Lab.nl
 * Tips or suggestions can be mailed to john.hout@u-lab.nl or check github.
 * Thanks to Joost Faasen from LinkORB for helping the SOAP examples / client.
 */


use Illuminate\Support\Facades\Facade;

class Buckaroo extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'buckaroo'; }

}