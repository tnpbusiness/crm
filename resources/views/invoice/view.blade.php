@extends('layouts.main')
@section('page-title')
    {{ __('Invoice Detail') }}
@endsection
@section('page-breadcrumb')
    {{ __('Invoice Detail') }}
@endsection
@push('css')
    <style>
        #card-element {
            border: 1px solid #a3afbb !important;
            border-radius: 10px !important;
            padding: 10px !important;
        }
    </style>
@endpush
@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script type="text/javascript">
        @if($invoice->getDue() > 0  && !empty($company_payment_setting) &&  $company_payment_setting['is_stripe_enabled'] == 'on' && !empty($company_payment_setting['stripe_key']) && !empty($company_payment_setting['stripe_secret']))
        var stripe = Stripe('{{ $company_payment_setting['stripe_key'] }}');
        var elements = stripe.elements();

        // Custom styling can be passed to options when creating an Element.
        var style = {
            base: {
                // Add your base input styles here. For example:
                fontSize: '14px',
                color: '#32325d',
            },
        };

        // Create an instance of the card Element.
        var card = elements.create('card', {style: style});

        // Add an instance of the card Element into the `card-element` <div>.
        card.mount('#card-element');

        // Create a token or display an error when the form is submitted.
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            stripe.createToken(card).then(function (result) {
                if (result.error) {
                    $("#card-errors").html(result.error.message);
                    toastrs('Error', result.error.message, 'error');
                } else {
                    // Send the token to your server.
                    stripeTokenHandler(result.token);
                }
            });
        });

        function stripeTokenHandler(token) {
            // Insert the token ID into the form so it gets submitted to the server
            var form = document.getElementById('payment-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'stripeToken');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);

            // Submit the form
            form.submit();
        }

        @endif

        @if(isset($company_payment_setting['paystack_public_key']))
        $(document).on("click", "#pay_with_paystack", function () {
            $('#paystack-payment-form').ajaxForm(function (res) {
                var amount = res.total_price;
                if (res.flag == 1) {
                    var paystack_callback = "{{ url('/invoice/paystack') }}";

                    var handler = PaystackPop.setup({
                        key: '{{ $company_payment_setting['paystack_public_key']  }}',
                        email: res.email,
                        amount: res.total_price * 100,
                        currency: res.currency,
                        ref: 'pay_ref_id' + Math.floor((Math.random() * 1000000000) +
                            1
                        ), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
                        metadata: {
                            custom_fields: [{
                                display_name: "Email",
                                variable_name: "email",
                                value: res.email,
                            }]
                        },

                        callback: function (response) {

                            window.location.href = paystack_callback + '/' + response.reference + '/' + '{{encrypt($invoice->id)}}' + '?amount=' + amount;
                        },
                        onClose: function () {
                            alert('window closed');
                        }
                    });
                    handler.openIframe();
                } else if (res.flag == 2) {
                    toastrs('Error', res.msg, 'msg');
                } else {
                    toastrs('Error', res.message, 'msg');
                }

            }).submit();
        });
        @endif

        @if(isset($company_payment_setting['flutterwave_public_key']))
        //    Flaterwave Payment
        $(document).on("click", "#pay_with_flaterwave", function () {
            $('#flaterwave-payment-form').ajaxForm(function (res) {

                if (res.flag == 1) {
                    var amount = res.total_price;
                    var API_publicKey = '{{ $company_payment_setting['flutterwave_public_key']  }}';
                    var nowTim = "{{ date('d-m-Y-h-i-a') }}";
                    var flutter_callback = "{{ url('/invoice/flaterwave') }}";
                    var x = getpaidSetup({
                        PBFPubKey: API_publicKey,
                        customer_email: '{{Auth::user()->email}}',
                        amount: res.total_price,
                        currency: '{{App\Models\Utility::getValByName('site_currency')}}',
                        txref: nowTim + '__' + Math.floor((Math.random() * 1000000000)) + 'fluttpay_online-' + '{{ date('Y-m-d') }}' + '?amount=' + amount,
                        meta: [{
                            metaname: "payment_id",
                            metavalue: "id"
                        }],
                        onclose: function () {
                        },
                        callback: function (response) {
                            var txref = response.tx.txRef;
                            if (
                                response.tx.chargeResponseCode == "00" ||
                                response.tx.chargeResponseCode == "0"
                            ) {
                                window.location.href = flutter_callback + '/' + txref + '/' + '{{\Illuminate\Support\Facades\Crypt::encrypt($invoice->id)}}';
                            } else {
                                // redirect to a failure page.
                            }
                            x.close(); // use this to close the modal immediately after payment.
                        }
                    });
                } else if (res.flag == 2) {
                    toastrs('Error', res.msg, 'msg');
                } else {
                    toastrs('Error', data.message, 'msg');
                }

            }).submit();
        });
        @endif

        @if(isset($company_payment_setting['razorpay_public_key']))
        // Razorpay Payment
        $(document).on("click", "#pay_with_razorpay", function () {
            $('#razorpay-payment-form').ajaxForm(function (res) {
                if (res.flag == 1) {
                    var amount = res.total_price;
                    var razorPay_callback = '{{url('/invoice/razorpay')}}';
                    var totalAmount = res.total_price * 100;
                    var coupon_id = res.coupon;
                    var options = {
                        "key": "{{ $company_payment_setting['razorpay_public_key']  }}", // your Razorpay Key Id
                        "amount": totalAmount,
                        "name": 'Plan',
                        "currency": '{{App\Models\Utility::getValByName('site_currency')}}',
                        "description": "",
                        "handler": function (response) {
                            window.location.href = razorPay_callback + '/' + response.razorpay_payment_id + '/' + '{{\Illuminate\Support\Facades\Crypt::encrypt($invoice->id)}}' + '?amount=' + amount;
                        },
                        "theme": {
                            "color": "#528FF0"
                        }
                    };
                    var rzp1 = new Razorpay(options);
                    rzp1.open();
                } else if (res.flag == 2) {
                    toastrs('Error', res.msg, 'msg');
                } else {
                    toastrs('Error', data.message, 'msg');
                }

            }).submit();
        });
        @endif


        $('.cp_link').on('click', function () {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            toastrs('success', '{{__('Link Copy on Clipboard')}}', 'success')
        });
    </script>
    <script>
        $(document).on('click', '#shipping', function () {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                url: url,
                type: 'get',
                data: {
                    'is_display': is_display,
                },
                success: function (data) {
                }
            });
        })
    </script>
@endpush
@section('page-action')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary  cp_link" data-link="{{route('pay.invoice',\Illuminate\Support\Facades\Crypt::encrypt($invoice->id))}}" data-bs-toggle="tooltip" title="{{__('Copy')}}" data-original-title="{{__('Click to copy invoice link')}}">
            <span class="btn-inner--icon text-white"><i class="ti ti-file"></i></span>
        </a>
    </div>
@endsection
@section('content')
        @if($invoice->status!=4)
            <div class="row">
                <div class="card ">
                    <div class="card-body">
                        <div class="row timeline-wrapper">
                            <div class="col-md-6 col-lg-4 col-xl-4">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-plus text-primary"></i>
                                </div>
                                <h6 class="text-primary my-3">{{__('Create Invoice')}}</h6>
                                <p class="text-muted text-sm mb-3"><i class="ti ti-clock mr-2"></i>{{__('Created on ')}}{{ company_date_formate($invoice->issue_date)}}</p>
                                @can('invoice edit')
                                    <a href="{{ route('invoice.edit',\Crypt::encrypt($invoice->id)) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}"><i class="ti ti-edit mr-2"></i>{{__('Edit')}}</a>
                                @endcan
                            </div>
                            <div class="col-md-6 col-lg-4 col-xl-4">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-mail text-warning"></i>
                                </div>
                                <h6 class="text-warning my-3">{{__('Send Invoice')}}</h6>
                                <p class="text-muted text-sm mb-3">
                                    @if($invoice->status!=0)
                                        <i class="ti ti-clock mr-2"></i>{{__('Sent on')}} {{ company_date_formate($invoice->send_date)}}
                                    @else
                                        <small>{{__('Status')}} : {{__('Not Sent')}}</small>
                                    @endif
                                </p>

                                @if($invoice->status==0)
                                    @can('invoice send')
                                        <a href="{{ route('invoice.sent',$invoice->id) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-original-title="{{__('Mark Sent')}}"><i class="ti ti-send mr-2"></i>{{__('Send')}}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-6 col-lg-4 col-xl-4">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-report-money text-info"></i>
                                </div>
                                <h6 class="text-info my-3">{{__('Get Paid')}}</h6>
                                <p class="text-muted text-sm mb-3">{{__('Status')}} : {{__('Awaiting payment')}} </p>
                                @if($invoice->status!=0)
                                    @can('invoice payment create')
                                        <a href="#" data-url="{{ route('invoice.payment',$invoice->id) }}" data-ajax-popup="true" data-title="{{__('Add Payment')}}" class="btn btn-sm btn-info" data-original-title="{{__('Add Payment')}}"><i class="ti ti-report-money mr-2"></i>{{__('Add Payment')}}</a> <br>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @if($invoice->status!=0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                    @can('creditnote create')
                    @if(module_is_active('Account'))
                    {{-- @if(!empty($invoicePayment)) --}}
                                <div class="all-button-box mx-2">
                                    <a href="#" class="btn btn-sm btn-primary" data-url="{{ route('invoice.credit.note',$invoice->id) }}" data-ajax-popup="true" data-title="{{__('Add Credit Note')}}">
                                        {{__('Add Credit Note')}}
                                    </a>
                                </div>
                            @endif
                    {{-- @endif --}}
                    @endcan
                    @if(\Auth::user()->type =='company')
                        @if($invoice->status!=4)
                            <div class="all-button-box mx-2">
                                <a href="{{ route('invoice.payment.reminder',$invoice->id)}}" class="btn btn-sm btn-primary">{{__('Receipt Reminder')}}</a>
                            </div>
                        @endif
                        <div class="all-button-box mx-2">
                            <a href="{{ route('invoice.resent',$invoice->id)}}" class="btn btn-sm btn-primary">{{__('Resend Invoice')}}</a>
                        </div>
                    @endif
                    <div class="all-button-box mx-2">
                        <a href="{{ route('invoice.pdf', Crypt::encrypt($invoice->id))}}" target="_blank" class="btn btn-sm btn-primary">{{__('Download')}}</a>
                    </div>
                </div>
            </div>
    @else
        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                <div class="all-button-box mx-2">
                    <a href="{{ route('invoice.pdf', Crypt::encrypt($invoice->id))}}" target="_blank" class="btn btn-xs btn-primary btn-icon-only width-auto">
                        {{__('Download')}}
                    </a>
                </div>
                {{-- @if($invoice->getDue() > 0 && !empty($company_payment_setting) && ($company_payment_setting['is_stripe_enabled'] == 'on' || $company_payment_setting['is_paypal_enabled'] == 'on' || $company_payment_setting['is_paystack_enabled'] == 'on' || $company_payment_setting['is_flutterwave_enabled'] == 'on' || $company_payment_setting['is_razorpay_enabled'] == 'on' || $company_payment_setting['is_mercado_enabled'] == 'on' || $company_payment_setting['is_paytm_enabled'] == 'on' ||
                    $company_payment_setting['is_mollie_enabled']  == 'on' || $company_payment_setting['is_paypal_enabled'] == 'on' || $company_payment_setting['is_skrill_enabled'] == 'on' || $company_payment_setting['is_coingate_enabled'] == 'on' || $company_payment_setting['is_paymentwall_enabled'] == 'on'))
                    <div class="all-button-box">
                        <a href="#" class="btn btn-xs btn-primary btn-icon-only width-auto" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            {{__('Pay Now')}}
                        </a>
                    </div>
                @endif --}}
            </div>
        </div>
    @endif

    <div class="row">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h2>{{__('Invoice')}}</h2>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h3 class="invoice-number">{{ \App\Models\Invoice::invoiceNumberFormat($invoice->invoice_id) }}</h3>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="me-4">
                                            <small>
                                                <strong>{{__('Issue Date')}} :</strong><br>
                                                {{ company_date_formate($invoice->issue_date)}}<br><br>
                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{__('Due Date')}} :</strong><br>
                                                {{ company_date_formate($invoice->due_date)}}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @if (!empty($customer->billing_name) && !empty($customer->billing_address) && !empty($customer->billing_zip))
                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{__('Billed To')}} :</strong><br>
                                        {{ !empty($customer->billing_name) ? $customer->billing_name : '' }}<br>
                                        {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                        {{ !empty($customer->billing_city) ? $customer->billing_city . ' ,' : '' }}
                                        {{ !empty($customer->billing_state) ? $customer->billing_state . ' ,' : '' }}
                                        {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                        {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                        {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}<br>
                                        <strong>{{__('Tax Number ')}} : </strong>{{!empty($customer->tax_number)?$customer->tax_number:''}}

                                    </small>
                                </div>
                            @endif
                            @if(company_setting('invoice_shipping_display')=='on')
                                @if (!empty($customer->shipping_name) && !empty($customer->shipping_address) && !empty($customer->shipping_zip))
                                    <div class="col ">
                                        <small>
                                            <strong>{{__('Shipped To')}} :</strong><br>
                                            {{ !empty($customer->shipping_name) ? $customer->shipping_name : '' }}<br>
                                            {{ !empty($customer->shipping_address) ? $customer->shipping_address : '' }}<br>
                                            {{ !empty($customer->shipping_city) ? $customer->shipping_city .' ,': '' }}
                                            {{ !empty($customer->shipping_state) ? $customer->shipping_state .' ,': '' }}
                                            {{ !empty($customer->shipping_zip) ? $customer->shipping_zip : '' }}<br>
                                            {{ !empty($customer->shipping_country) ? $customer->shipping_country : '' }}<br>
                                            {{ !empty($customer->shipping_phone) ? $customer->shipping_phone : '' }}<br>
                                            <strong>{{__('Tax Number ')}} : </strong>{{!empty($customer->tax_number)?$customer->tax_number:''}}

                                        </small>
                                    </div>
                                @endif
                            @endif

                                <div class="col">
                                    <div class="float-end mt-3">
                                        {!! DNS2D::getBarcodeHTML(route('pay.invoice',\Illuminate\Support\Facades\Crypt::encrypt($invoice->id)), "QRCODE",2,2) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{__('Status')}} :</strong><br>
                                        @if($invoice->status == 0)
                                            <span class="badge fix_badge rounded p-1 px-3 bg-primary">{{ __( \App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 1)
                                            <span class="badge fix_badge rounded p-1 px-3 bg-info">{{ __( \App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 2)
                                            <span class="badge fix_badge rounded p-1 px-3 bg-secondary">{{ __( \App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 3)
                                            <span class="badge fix_badge rounded p-1 px-3 bg-warning">{{ __( \App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 4)
                                            <span class="badge fix_badge rounded p-1 px-3 bg-danger">{{ __( \App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @endif
                                    </small>
                                </div>

                                @if(!empty($customFields) && count($invoice->customField)>0)
                                    @foreach($customFields as $field)
                                        <div class="col text-md-end">
                                            <small>
                                                <strong>{{$field->name}} :</strong><br>
                                                {{!empty($invoice->customField)?$invoice->customField[$field->id]:'-'}}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{__('Product Summary')}}</div>
                                    <small>{{__('All items here cannot be deleted.')}}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 table-striped">
                                            <tr>
                                                <th data-width="40" class="text-dark">#</th>
                                                @if($invoice->invoice_module == "account")
                                                    <th class="text-dark">{{__('Product')}}</th>
                                                @elseif($invoice->invoice_module == "taskly")
                                                  <th class="text-dark">{{__('Project')}}</th>
                                                @endif

                                                <th class="text-dark">{{__('Quantity')}}</th>
                                                <th class="text-dark">{{__('Rate')}}</th>
                                                <th class="text-dark">{{__('Discount')}}</th>
                                                <th class="text-dark">{{__('Tax')}}</th>
                                                <th class="text-dark">{{__('Description')}}</th>
                                                <th class="text-right text-dark" width="12%">{{__('Price')}}<br>
                                                    <small class="text-danger font-weight-bold">{{__('After discount & tax')}}</small>
                                                </th>
                                            </tr>
                                            @php
                                                $totalQuantity=0;
                                                $totalRate=0;
                                                $totalTaxPrice=0;
                                                $totalDiscount=0;
                                                $taxesData=[];
                                                $TaxPrice_array = [];
                                            @endphp
                                            @foreach($iteams as $key =>$iteam)
                                                @if(!empty($iteam->tax))
                                                    @php
                                                        $taxes=  \App\Models\Invoice::tax($iteam->tax);
                                                        $totalQuantity+=$iteam->quantity;
                                                        $totalRate+=$iteam->price;
                                                        $totalDiscount+=$iteam->discount;
                                                        foreach($taxes as $taxe){
                                                            $taxDataPrice= \App\Models\Invoice::taxRate($taxe->rate,$iteam->price,$iteam->quantity,$iteam->discount);
                                                            if (array_key_exists($taxe->name,$taxesData))
                                                            {
                                                                $taxesData[$taxe->name] = $taxesData[$taxe->name]+$taxDataPrice;
                                                            }
                                                            else
                                                            {
                                                                $taxesData[$taxe->name] = $taxDataPrice;
                                                            }
                                                        }
                                                    @endphp
                                                @endif
                                                <tr>
                                                    <td>{{$key+1}}</td>
                                                    @if($invoice->invoice_module == "account")
                                                        <td>{{!empty($iteam->product())?$iteam->product()->name:''}}</td>
                                                    @elseif($invoice->invoice_module == "taskly")
                                                        <td>{{!empty($iteam->product())?$iteam->product()->title:''}}</td>
                                                    @endif
                                                    <td>{{$iteam->quantity}}</td>
                                                    <td>{{ currency_format_with_sym($iteam->price)}}</td>
                                                    <td>
                                                            {{ currency_format_with_sym($iteam->discount)}}
                                                    </td>
                                                    <td>

                                                        @if(!empty($iteam->tax))
                                                            <table>
                                                                @php
                                                                     $totalTaxRate = 0;
                                                                     $data = 0;
                                                                @endphp
                                                                @foreach($taxes as $tax)
                                                                    @php
                                                                        $taxPrice= \App\Models\Invoice::taxRate($tax->rate,$iteam->price,$iteam->quantity,$iteam->discount);
                                                                        $totalTaxPrice+=$taxPrice;
                                                                        $data+=$taxPrice;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{$tax->name .' ('.$tax->rate .'%)'}}</td>
                                                                        <td>{{ currency_format_with_sym($taxPrice)}}</td>
                                                                    </tr>
                                                                @endforeach
                                                                @php
                                                                    array_push($TaxPrice_array,$data);
                                                                @endphp
                                                            </table>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <td style="white-space: break-spaces;">{{!empty($iteam->description)?$iteam->description:'-'}}</td>
                                                    @php
                                                        $tr_tex = (array_key_exists($key,$TaxPrice_array) == true) ? $TaxPrice_array[$key] : 0;
                                                    @endphp
                                                    <td class="">{{ currency_format_with_sym(($iteam->price*$iteam->quantity) -$iteam->discount + $tr_tex )}}</td>
                                                </tr>
                                            @endforeach
                                            <tfoot>
                                            <tr>
                                                <td></td>
                                                <td><b>{{__('Total')}}</b></td>
                                                <td><b>{{$totalQuantity}}</b></td>
                                                <td><b>{{ currency_format_with_sym($totalRate)}}</b></td>
                                                <td><b>{{ currency_format_with_sym($totalDiscount)}}</b></td>
                                                <td><b>{{ currency_format_with_sym($totalTaxPrice)}}</b></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Sub Total')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym($invoice->getSubTotal())}}</td>
                                            </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-right"><b>{{__('Discount')}}</b></td>
                                                    <td class="text-right">{{ currency_format_with_sym($invoice->getTotalDiscount())}}</td>
                                                </tr>
                                            @if(!empty($taxesData))
                                                @foreach($taxesData as $taxName => $taxPrice)
                                                    <tr>
                                                        <td colspan="6"></td>
                                                        <td class="text-right"><b>{{$taxName}}</b></td>
                                                        <td class="text-right">{{  currency_format_with_sym($taxPrice) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="blue-text text-right"><b>{{__('Total')}}</b></td>
                                                <td class="blue-text text-right">{{ currency_format_with_sym($invoice->getTotal())}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Paid')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym(($invoice->getTotal()-$invoice->getDue())-($invoice->invoiceTotalCreditNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Credit Note')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym(($invoice->invoiceTotalCreditNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Due')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym($invoice->getDue())}}</td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- </div> -->
        </div>
        <!-- <div class="col-12"> -->
            <h5 class="h4 d-inline-block font-weight-400 mb-2">{{__('Receipt Summary')}}</h5>
                <div class="card">
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table ">
                                <tr>
                                    <th class="text-dark">{{__('Date')}}</th>
                                    <th class="text-dark">{{__('Amount')}}</th>
                                    <th class="text-dark">{{__('Payment Type')}}</th>
                                    <th class="text-dark">{{__('Account')}}</th>
                                    <th class="text-dark">{{__('Reference')}}</th>
                                    <th class="text-dark">{{__('Description')}}</th>
                                    <th class="text-dark">{{__('Receipt')}}</th>
                                    <th class="text-dark">{{__('OrderId')}}</th>
                                    @can('invoice payment delete')
                                        <th class="text-dark">{{__('Action')}}</th>
                                    @endcan
                                </tr>
                                @if(!empty($retainer->payments) || !empty($bank_transfer_payments))
                                    @foreach($bank_transfer_payments as $bank_transfer_payment)
                                        <tr>
                                            <td>{{ company_datetime_formate($bank_transfer_payment->created_at) }}</td>
                                            <td class="text-right">{{currency_format_with_sym($bank_transfer_payment->price)}}</td>
                                            <td>{{('Bank transfer')}}</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>@if(!empty($bank_transfer_payment->attachment))<a href="{{get_file($bank_transfer_payment->attachment)}}" target="_blank"> <i class="ti ti-file"></i></a>@else -- @endif</td>
                                            <td>{{ $bank_transfer_payment->order_id}}</td>
                                            <td>
                                                <div class="action-btn bg-primary ms-2">
                                                    <a  class="mx-3 btn btn-sm  align-items-center"
                                                        data-url="{{ route('invoice.bank.request.edit', $bank_transfer_payment->id) }}"
                                                        data-ajax-popup="true" data-size="md" data-bs-toggle="tooltip" title=""
                                                        data-title="{{ __('Request Action') }}"
                                                        data-bs-original-title="{{ __('Action') }}">
                                                        <i class="ti ti-caret-right text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    {{Form::open(array('route'=>array('bank-transfer-request.destroy', $bank_transfer_payment->id),'class' => 'm-0'))}}
                                                    @method('DELETE')
                                                        <a
                                                            class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                            data-bs-toggle="tooltip" title="" data-bs-original-title="Delete"
                                                            aria-label="Delete" data-confirm="{{__('Are You Sure?')}}" data-text="{{__('This action can not be undone. Do you want to continue?')}}"  data-confirm-yes="delete-form-{{$bank_transfer_payment->id}}"><i
                                                                class="ti ti-trash text-white text-white"></i></a>
                                                    {{Form::close()}}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach($invoice->payments as $key =>$payment)
                                        <tr>
                                            <td>{{ company_date_formate($payment->date)}}</td>
                                            <td>{{ currency_format_with_sym($payment->amount)}}</td>
                                            <td>{{$payment->payment_type}}</td>
                                            @if(module_is_active('Account'))
                                                <td>{{!empty($payment->bankAccount)?$payment->bankAccount->bank_name.' '.$payment->bankAccount->holder_name:'--'}}</td>
                                            @else
                                            <td>--</td>
                                            @endif

                                            <td>{{!empty($payment->reference)?$payment->reference:'--'}}</td>
                                            <td style="white-space: break-spaces;">{{!empty($payment->description)?$payment->description:'--'}}</td>
                                            <td>
                                                @if(!empty($payment->add_receipt) && empty($payment->receipt))
                                                    <a href="{{ get_file($payment->add_receipt)}}" download="" class="btn btn-sm btn-primary btn-icon rounded-pill" target="_blank"><span class="btn-inner--icon"><i class="ti ti-download"></i></span></a>
                                                    <a href="{{ get_file($payment->add_receipt)}}"  class="btn btn-sm btn-secondary btn-icon rounded-pill" target="_blank"><span class="btn-inner--icon"><i class="ti ti-crosshair"></i></span></a>
                                                @elseif (!empty($payment->receipt) && empty($payment->add_receipt) && $payment->type == 'STRIPE')
                                                    <a href="{{$payment->receipt}}" target="_blank">
                                                        <i class="ti ti-file"></i>
                                                    </a>
                                                @elseif($payment->payment_type == 'Bank Transfer')
                                                    <a href="{{ !empty($payment->receipt) ? (check_file($payment->receipt)) ? get_file($payment->receipt) : '#!' : '#!' }}" target="_blank" >
                                                        <i class="ti ti-file"></i>
                                                    </a>
                                                @else
                                                        --
                                                @endif
                                            </td>
                                            <td>{{!empty($payment->order_id)?$payment->order_id:'--'}}</td>
                                            @can('invoice payment delete')
                                                <td>
                                                    <div class="action-btn bg-danger ms-2">
                                                        {{Form::open(array('route'=>array('invoice.payment.destroy',$invoice->id,$payment->id),'class' => 'm-0'))}}
                                                            <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                                data-bs-toggle="tooltip" title="" data-bs-original-title="Delete"
                                                                aria-label="Delete" data-confirm="{{__('Are You Sure?')}}" data-text="{{__('This action can not be undone. Do you want to continue?')}}"  data-confirm-yes="delete-form-{{$payment->id}}">
                                                                <i class="ti ti-trash text-white text-white"></i>
                                                            </a>
                                                        {{Form::close()}}
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                @else
                                    @include('layouts.nodatafound')
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
        <!-- </div> -->
        @if(module_is_active('Account'))
            @include('account::invoice.invoice_section')
        @endif

@endsection
