<?php 

namespace Core\Api;

use Core\Service;

/*
	DOCUMENTATION URL : https://stripe.com/docs
*/

class Stripe
{
	use \Core\Singleton;

	//protected $db;
	//protected $request;
	//protected $session;

	protected $mode;
	protected $api_key; 

	public function __construct() 
	{
		//$this->db = Service::Db();
		//$this->request = Service::Request();
		//$this->session = Service::Session();

		Service::add_autoload_directory(ROOT.DS.'Core'.DS.'Api'.DS);

		$this->mode = _config('stripe.mode', null);

		if ($this->mode == "test") {
			$this->api_key = _config('stripe.api_key_test', null);
			$this->publishable_api_key = _config('stripe.publishable_key_test', null);

		} else if ($this->mode == "live") {
			$this->api_key = _config('stripe.api_key', null);
			$this->publishable_api_key = _config('stripe.publishable_key', null);
		}
	}

	public function set_api_key($api_key)
	{
		$this->api_key = $api_key;
	}

	/* ---------------------------------- FONCTION REQUTE --------------------------------------------- */

	public function add_plan($name, $id, $amount, $interval="month", $currency="eur")
	{
		\Stripe\Stripe::setApiKey($this->api_key);

		$plan = \Stripe\Plan::create(array(
		  "name" => $name, // ex : "Basic Plan"
		  "id" => $id, // ex: "basic-monthly"
		  "interval" => $interval, // ex: month
		  "currency" => $currency, // ex "eur"
		  "amount" => $amount,
		));

		return $plan;
	}

	public function create_constumer($email, $token)
	{
		\Stripe\Stripe::setApiKey($this->api_key);
		
		$customer = \Stripe\Customer::create(array(
		  "email" => $email,
		  "source" => $token,
		));

		return $customer;
	}

	public function charge_constumer($id_customer, $amount, $ref_transaction, $currency="eur")
	{
		\Stripe\Stripe::setApiKey($this->api_key);

		// Charge the Customer instead of the card:
		$charge = \Stripe\Charge::create(array(
		  "amount" => $amount,
		  "currency" => $currency,
		  "customer" => $id_customer,
		  "metadata" => array("ref" => $ref_transaction),
		));

		return $charge;
	}

	public function charge_with_token($token, $amount, $ref_transaction, $currency="eur")
	{
		\Stripe\Stripe::setApiKey($this->api_key);

		$charge = \Stripe\Charge::create(array(
		  "amount" => $amount,
		  "currency" => $currency,
		  //"description" => "Example charge",
		  "source" => $token,
		  "metadata" => array("ref" => $ref_transaction),
		));

		return $charge;
	}

	public function get_event($id_event)
	{
		\Stripe\Stripe::setApiKey($this->api_key);
		return \Stripe\Event::retrieve($id_event);
	}


	/* ------------------------ FORMULAIRE ENREGSITREMENT CARTE ------------------------------ */

	public function card_form ($url, $is_ajax=true, $width=350, $field_style=null)
	{
		$field_style = !$field_style ? 'border: 1px solid #CCC; background-color: #FCFCFC; color: #3b3b3b; padding: 6px 9px;' : $field_style;

		ob_start();
		?>

		<style>
			#payment-form {
			  width: <?= $width ?>px;
			  display:block;
			}

			label {width:100%;}
			.group {width:100%;}
			#payment-form .stripefield {
				<?= $field_style ?>
				width:100%;
			    font-size: 14px;
			    flex: 1;
			  	cursor: text;
			}
			#payment-form .stripefield::-webkit-input-placeholder { color: #CFD7E0; }
			#payment-form .stripefield::-moz-placeholder { color: #CFD7E0; }

			#payment-form .outcome {
			  text-align: center;
			}
			#payment-form .success, #payment-form .error {
			  font-size: 14px;
			  margin-top:5px;
			  margin-top:10px;
			}
			#payment-form .success.visible, #payment-form .error.visible {
			  display: inline;
			}
			#payment-form .error {
			  color: #E4584C;
			}
			#payment-form .success {
			  color: #666EE8;
			}
		</style>

		<form method="POST" action="<?= $url ?>" id="payment-form">

		    <div class="group">
		      <label>
		        <span>Numéro de carte</span>
		        <div id="card-number" class="stripefield"></div>
		      </label>
		    </div>
		    <div class="group" style="float:left; width:55%;">
		      <label>
		        <span>Date d'expiration</span>
		        <div id="card-expiration" class="stripefield"></div>
		      </label>
		    </div>
		    <div class="group" style="float:left; width:40%; margin-left:5%;">
		      	<label>
		        <span>CVC</span>
		        <div id="card-cvc" class="stripefield"></div>
		      </label>
		    </div>
		    <div style="clear:both"></div>
		    <div class="group">
		      <label>
		        <span>Votre code postal</span>
		        <div id="code-postal" class="stripefield"></div>
		      </label>
		    </div>
		    <div class="outcome">
		      <div class="error" id="payment-errors"></div>
		      <div class="success"></div>
		    </div>
		    <input type="submit" style="width:100%;" value="Valider le moyen de paiement" />
		    
		</form>

		<script>

			(function(){

				var style = {
				  base: {
				    color: '#383838',
				    fontSize: '16px',
				    lineHeight: '25px',
				    fontFamily: 'Helvetica Neue',
				    fontSmoothing: 'antialiased',
				    '::placeholder': {
				      color: '#CFD7E0',
				    },
				  },
				  invalid: {
				    color: '#e5424d',
				    ':focus': {
				      color: '#383838',
				    },
				  },
				};

				var error_manager = function (event) {
					var displayError = document.getElementById('payment-errors');
					if (event.error) {
					    displayError.textContent = event.error.message;
					} else {
						displayError.textContent = "";
					}
				}

				<?php if ($this->publishable_api_key) : ?>
				var stripe = Stripe('<?= $this->publishable_api_key ?>');
				<?php else: ?>
				<?php 
					ob_end_clean();
					Service::error('Formulaire carte bancaire : clé api pupliable inexistante'); 
				?>
				<?php endif; ?>

				var elements = stripe.elements({locale:"fr-FR"});

				var card = elements.create('cardNumber', {style});
				card.mount('#card-number');
				card.addEventListener('change', error_manager);

				var card_expiration = elements.create('cardExpiry', {style});
				card_expiration.mount('#card-expiration');
				card_expiration.addEventListener('change', error_manager);

				var card_cvc = elements.create('cardCvc', {style});
				card_cvc.mount('#card-cvc');
				card_cvc.addEventListener('change', error_manager);

				var code_postal = elements.create('postalCode', {style});
				code_postal.mount('#code-postal');
				code_postal.addEventListener('change', error_manager);

				var form = document.getElementById('payment-form');

				form.addEventListener('submit', function(event) {
				  event.preventDefault();
				  
				  $('#loading').fadeTo( 300 , 1);

				  stripe.createToken(card).then(function(result) {
				  	var errorElement = document.getElementById('payment-errors');

				  	$('#loading').fadeTo( 300 , 0, function(){
		                $(this).css('display','none');
		            });
				  	
				    if (result.error) {
				      // Inform the user if there was an error  
				      errorElement.textContent = result.error.message;
				    } else {
				      // Send the token to your server
				      errorElement.textContent = "";
				      stripeTokenHandler(result.token);
				    }
				  });
				});

				function stripeTokenHandler(token) {
				  // Insert the token ID into the form so it gets submitted to the server
				  var form = document.getElementById('payment-form');
				  var hiddenInput = document.createElement('input');
				  hiddenInput.setAttribute('type', 'hidden');
				  hiddenInput.setAttribute('name', 'token');
				  hiddenInput.setAttribute('value', token.id);
				  form.appendChild(hiddenInput);

				  <?php if ($is_ajax) : ?>

				  $form = $('#payment-form');
				  var formData = new FormData($form[0]);
				  var target = $form.closest('[data-ajax-container]');

		            $.ajax({
		                url:  $form.attr('action'),
		                type: $form.attr('method'),
		                dataType: 'html',
		                data: formData,

		                success: function(html){

		                    ajax_load_transition (target, html, 'fade');
		                },
		                error: function(data, state){
		                    ajax_load_transition (target, data.responseText, 'none');    
		                },
		                cache: false,
		                contentType: false, 
		                processData: false,
		                async: true, 
		            });
					
					<?php else : ?>

				  	form.submit();
					
					<?php endif; ?>
				}
			
			})()

		</script>

		<?php
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

}	

?>