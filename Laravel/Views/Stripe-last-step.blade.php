
<script src="https://js.stripe.com/v3/"></script>
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Source+Code+Pro" rel="stylesheet">

{!! Form::open(['route' => 'publico::finalize-buy', 'method'=>'post', 'id'=>'form','class'=>'form']) !!}

    <div class="form__container form__container_receipt container">
        <div class="form__step form__step_1 form__step_hidden form__step_verified" id="form__step_1">
            <div class="form__title form-title"><span class="form-title__step">1</span>
              <input type="hidden" id="step-1_url" value="{{$step_back}}/step-1#step_main">
              <a class="form-title__text" id="step-1" href="#">Your Personal Information</a></div>
        </div>
        <div class="form__step form__step_2 form__step_hidden form__step_verified" id="form__step_2">
            <div class="form__title form-title"><span class="form-title__step">2</span>
              <input type="hidden" id="step-2_url" value="{{$step_back}}/step-2#step_main">
              <a class="form-title__text"  id="step-2"  href="#">Your Address Information</a></div>
        </div>
        <div class="form__step form__step_3 form__step_current" id="step_main">
            <div class="form__title form-title"><span class="form-title__step">3</span><span class="form-title__text">Confirm and Pay</span></div>
            @include('flash::message-public')
            <div class="form__content">   
                <div class="reciept">
                    <h2 class="reciept__title">Payment Receipt Order</h2>
                    <div class="reciept__section">
                        <div class="reciept__column">
                            <p>{{$full_data_cliente->first_name or ''}} {{$full_data_cliente->last_name or ''}}</p>
                            <p>{{$full_data_cliente->email or ''}}</p>
                        </div>
                        <div class="reciept__column">
                            {{-- <p>1500 11th Street</p>
                            <p>Sacramento CA 95814</p> --}}
                            <p>{{$full_data_cliente->user_address->address or ''}}</p>
                             <p>{{$full_data_cliente->user_address->city or ''}} {{$full_data_cliente->user_address->state or ''}} {{$full_data_cliente->user_address->zip_code or ''}}</p>
                                                   
                        </div>
                    </div>
                    <div class="reciept__section">
                        <div class="reciept__column">
                            <p>Accidental Death & Dismemberment (AD&D)</p>
                            <p>Beneficiary: </p>
                            @if (isset($full_data_cliente->have_user_beneficiary ) && $full_data_cliente->have_user_beneficiary == "1") 
                                <p>{{$full_data_cliente->user_beneficiary->first_name or ''}}</p>
                                <p>{{$full_data_cliente->user_beneficiary->last_name or ''}}</p>
                                <p>{{$full_data_cliente->user_beneficiary->email or ''}}</p>                                 
                            @else
                                <p>{{$full_data_cliente->first_name or ''}}</p>
                                <p>{{$full_data_cliente->last_name or ''}}</p>
                                <p>{{$full_data_cliente->email or ''}}</p>
                            @endif                         
                        </div>
                        <div class="reciept__column">
                            <p class="reciept__total-price">Total <span>${{$productSelected->price or ''}}</span></p>
                        </div> 
                    </div>
                    <div class="credit-card">
                        <h2 class="credit-card__title">Credit Card Info</h2>
                        <div class="form__item">
                            <div class="input-box">
                                <label class="input-box__label" for="first-name">Card Holder's Name</label>
                                <input class="input-box__item only-letters" name="card-holders-name" id="card-holders-name" type="text"  value="{{$full_data_cliente->first_name or ''}} {{$full_data_cliente->last_name or ''}}" required="">
                            </div>
                        </div>
                        <div class="form__item">
                            {{-- <div id="credit-card-input">Credit Card Place Holder</div> --}}
                            <div id="input-card-sale" class="input-box"></div>
                        </div>
                    </div>
                </div>

                <div class="error" role="alert"></div> <span class="message"></span>

                <div class="form__controls">
                    <input type="hidden" id="form__control_back_disable" value="{{$step_back}}/step-2#step_main">
                    <a class="form__control form__control_back" id="form__control_back" href="#" ><span>Back</span></a>
                    <button class="form__control form__control_submit" id="form__control_submit" type="submit"><span>Confirm & Pay</span><img src="{{ url('/') }}/assets/modules/publico/images/loading.svg" alt="Loading"></button>
                </div>
            </div>
        </div>
    </div>
{!! Form::close() !!}

<script>  
    'use strict';
    var stripe = Stripe('{{ env('STRIPE_PUB_KEY') }}');
    function stripeTokenHandler(token) {
        // Insert the token ID into the form so it gets submitted to the server
        var form = document.getElementById('form');
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);
        form.submit();
    }
  
    function registerElements(elements, exampleName) {
      var formClass = '.' + exampleName;
      var example = document.querySelector(formClass);
  
      var form = document.getElementById('form');
      // var resetButton = example.querySelector('a.reset');
      var error = form.querySelector('.error');
      var errorMessage = error.querySelector('.message');
  
     
      // Listen on the form's 'submit' handler...
        form.addEventListener('submit', function(e) {
        e.preventDefault();
        document.getElementById("form__control_submit").disabled = true;
        document.getElementById("form__control_back").href = "#";
  
        document.getElementById("step-1").href = "#";
        document.getElementById("step-2").href = "#";
        // Gather additional customer data we may have collected in our form.
          var name_holders = document.getElementById("card-holders-name").value;
          var additionalData = {
            name: name_holders,
          };
      
        form.classList.add('form_loading');      
        stripe.createToken(elements[0],additionalData).then(function(result) {
          if (result.token) {
            stripeTokenHandler(result.token);         
          } else {
            // Otherwise, un-disable inputs.
            form.classList.remove('form_loading'); 
            document.getElementById("form__control_submit").disabled = false;          
            document.getElementById("form__control_back").href = document.getElementById("form__control_back_disable").value;
            document.getElementById("step-1").href = document.getElementById("step-1_url").value;
            document.getElementById("step-2").href = document.getElementById("step-2_url").value;
  
          }
        });        
      });
    }
           
(function() {
    'use strict';

    var elements = stripe.elements({
      fonts: [
        {
          cssSrc: 'https://fonts.googleapis.com/css?family=Roboto',
        },
      ],
      // Stripe's examples are localized to specific languages, but if
      // you wish to have Elements automatically detect your user's locale,
      // use `locale: 'auto'` instead.
      locale: window.__exampleLocale
    });

    var card = elements.create('card', {
      iconStyle: 'solid',
      style: {
        base: {
          iconColor: '#c4f0ff',
          color: '#00489a',
          fontWeight: 500,
          fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif',
          fontSize: '16px',
          fontSmoothing: 'antialiased',

          ':-webkit-autofill': {
            color: '#fce883',
          },
          '::placeholder': {
            color: '#87BBFD',
          },
        },
        invalid: {
          iconColor: 'red',
          color: 'red',
        },
      },
    });
    card.mount('#input-card-sale');        
    registerElements([card], 'example1');
  })();

  document.getElementById("form__control_back").href = document.getElementById("form__control_back_disable").value;
  document.getElementById("step-1").href = document.getElementById("step-1_url").value;
  document.getElementById("step-2").href = document.getElementById("step-2_url").value;

</script>

