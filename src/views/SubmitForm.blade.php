<form id="bpe" method="POST" action="{{ $bpe_url }}" enctype="application/x-www-form-urlencoded">

	<input type="hidden" name="brq_return" value="{{ \Config::get('buckaroo::return_url') }}" />
	<input type="hidden" name="brq_websitekey" value="{{ \Config::get('buckaroo::website_key') }}" />
	<input type="hidden" name="brq_amount" value="{{ $brq_amount }}" />
	<input type="hidden" name="brq_currency" value="{{ \Config::get('buckaroo::currency') }}" />
	<input type="hidden" name="brq_culture" value="{{ \Config::get('buckaroo::culture') }}" />
	<input type="hidden" name="brq_invoicenumber" value="{{ $brq_invoicenumber }}" />

@if (isset($brq_service_ideal_issuer) && $brq_payment_method == 'ideal')
	<input type="hidden" name="brq_payment_method" value="ideal" />
	<input type="hidden" name="brq_service_ideal_issuer" value="{{ $brq_service_ideal_issuer }}" />

@elseif (isset($brq_service_paypal_buyeremail) && $brq_payment_method == 'paypal')
	<input type="hidden" name="brq_payment_method" value="paypal" />
	<input type="hidden" name="brq_service_paypal_buyeremail" value="{{ $brq_service_paypal_buyeremail }}" />

@endif

	<input type="hidden" name="brq_signature" value="{{ $bpe_signature }}" />

@if ( !is_null($button))
	{{ $button }}
@else
	<button class="submitBPE" type="submit">Make payment</button>
@endif


</form>

