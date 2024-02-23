@extends('layouts.invoicepayheader')
@section('page-title')
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

@section('action-btn')
    @if ($invoice->status != 0)
        <div class="row justify-content-center align-items-center ">
            <div class="col-12 d-flex align-items-center justify-content-between justify-content-md-end">
                @if (!empty($invoicePayment))
                    <div class="all-button-box mx-2">
                        <a href="#" class="btn btn-xs btn-white btn-icon-only width-auto"
                            data-url="{{ route('invoice.credit.note', $invoice->id) }}" data-ajax-popup="true"
                            data-title="{{ __('Add Credit Note') }}">
                            {{ __('Add Credit Note') }}
                        </a>
                    </div>
                @endif
                <div class="all-button-box mr-3">
                    <a href="{{ route('invoice.pdf', \Crypt::encrypt($invoice->id)) }}" target="_blank"
                        class="btn btn-sm btn-primary btn-icon" data-bs-toggle="tooltip" title="{{ __('Print') }}">
                        <span class="btn-inner--icon text-white"><i class="ti ti-printer"></i>{{ __('Print') }}</span>
                    </a>

                    @if ($invoice->getDue() > 0)
                        <a id="paymentModals"  class="btn btn-sm btn-primary">
                            <span class="btn-inner--icon text-white"><i class="ti ti-credit-card"></i></span>
                            <span class="btn-inner--text text-white">{{ __(' Pay Now') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection

@section('content')
    <div class="row ">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h2>{{ __('Invoice') }}</h2>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h3 class="invoice-number">

                                        {{ \App\Models\Invoice::invoiceNumberFormat($invoice->invoice_id, $invoice->created_by,$invoice->workspace) }}
                                    </h3>
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
                                                <strong>{{ __('Issue Date') }} :</strong><br>

                                                {{ company_date_formate($invoice->issue_date, $invoice->created_by,$invoice->workspace) }}<br><br>

                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{ __('Due Date') }} :</strong><br>
                                                {{ company_date_formate($invoice->due_date, $invoice->created_by,$invoice->workspace) }}<br><br>
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
                            @if(company_setting('invoice_shipping_display',$invoice->created_by,$invoice->workspace)=='on')
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
                                        {!! DNS2D::getBarcodeHTML(
                                            route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice->id)),
                                            'QRCODE',
                                            2,
                                            2,
                                        ) !!}
                                    </div>
                                </div>

                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Status') }} :</strong><br>

                                        @if ($invoice->status == 0)
                                            <span
                                                class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 1)
                                            <span
                                                class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 2)
                                            <span
                                                class="badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 3)
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 4)
                                            <span
                                                class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @endif
                                    </small>
                                </div>
                                @if (!empty($customFields) && count($invoice->customField) > 0)
                                    @foreach ($customFields as $field)
                                        <div class="col text-end">
                                            <small>
                                                <strong>{{ $field->name }} :</strong><br>
                                                {{ !empty($invoice->customField) ? $invoice->customField[$field->id] : '-' }}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{ __('Product Summary') }}</div>
                                    <small>{{ __('All items here cannot be deleted.') }}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 ">
                                            <tr>
                                                <th data-width="40" class="text-dark">#</th>
                                                @if ($invoice->invoice_module == 'account')
                                                    <th class="text-dark">{{ __('Product') }}</th>
                                                @elseif($invoice->invoice_module == 'taskly')
                                                    <th class="text-dark">{{ __('Project') }}</th>
                                                @endif

                                                <th class="text-dark">{{ __('Quantity') }}</th>
                                                <th class="text-dark">{{ __('Rate') }}</th>
                                                <th class="text-dark">
                                                        {{ __('Discount') }}
                                                </th>
                                                <th class="text-dark">{{ __('Tax') }}</th>
                                                <th class="text-dark">{{ __('Description') }}</th>
                                                <th class="text-end text-dark" width="12%">{{ __('Price') }}<br>
                                                    <small
                                                        class="text-danger font-weight-bold">{{ __('After discount & tax') }}</small>
                                                </th>
                                            </tr>
                                            @php
                                                $totalQuantity = 0;
                                                $totalRate = 0;
                                                $totalTaxPrice = 0;
                                                $totalDiscount = 0;
                                                $taxesData = [];
                                                $TaxPrice_array = [];
                                            @endphp
                                            @foreach ($iteams as $key => $iteam)
                                                @if (!empty($iteam->tax))
                                                    @php
                                                        $taxes = App\Models\Invoice::tax($iteam->tax);
                                                        $totalQuantity += $iteam->quantity;
                                                        $totalRate += $iteam->price;
                                                        if ($invoice->invoice_module == 'account') {
                                                            $totalDiscount += $iteam->discount;
                                                        } elseif ($invoice->invoice_module == 'taskly') {
                                                            $totalDiscount = $invoice->discount;
                                                        }

                                                        foreach ($taxes as $taxe) {
                                                            $taxDataPrice = App\Models\Invoice::taxRate($taxe->rate, $iteam->price, $iteam->quantity,$iteam->discount);
                                                            if (array_key_exists($taxe->name, $taxesData)) {
                                                                $taxesData[$taxe->name] = $taxesData[$taxe->name] + $taxDataPrice;
                                                            } else {
                                                                $taxesData[$taxe->name] = $taxDataPrice;
                                                            }
                                                        }
                                                    @endphp
                                                @endif
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    @if ($invoice->invoice_module == 'account')
                                                        <td>{{ !empty($iteam->product()) ? $iteam->product()->name : '' }}
                                                        </td>
                                                    @elseif($invoice->invoice_module == 'taskly')
                                                        <td>{{ !empty($iteam->product()) ? $iteam->product()->title : '' }}
                                                        </td>
                                                    @endif
                                                    <td>{{ $iteam->quantity }}</td>
                                                    <td>{{ currency_format_with_sym($iteam->price, $invoice->created_by,$invoice->workspace) }}
                                                    </td>
                                                    <td>
                                                        {{ currency_format_with_sym($iteam->discount, $invoice->created_by,$invoice->workspace) }}
                                                    </td>
                                                    <td>

                                                        @if (!empty($iteam->tax))
                                                            <table>
                                                                @php
                                                                    $totalTaxRate = 0;
                                                                    $data = 0;
                                                                @endphp
                                                                @foreach ($taxes as $tax)
                                                                    @php
                                                                        $taxPrice = App\Models\Invoice::taxRate($tax->rate, $iteam->price, $iteam->quantity,$iteam->discount);
                                                                        $totalTaxPrice += $taxPrice;
                                                                        $data+=$taxPrice;

                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{ $tax->name . ' (' . $tax->rate . '%)' }}
                                                                        </td>
                                                                        <td>{{ currency_format_with_sym($taxPrice, $invoice->created_by,$invoice->workspace) }}
                                                                        </td>
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

                                                    <td style="white-space: break-spaces;">{{ !empty($iteam->description) ? $iteam->description : '-' }}</td>
                                                    @php
                                                        $tr_tex = (array_key_exists($key,$TaxPrice_array) == true) ? $TaxPrice_array[$key] : 0;
                                                    @endphp
                                                    <td class="text-end">
                                                        {{ currency_format_with_sym(($iteam->price*$iteam->quantity) -$iteam->discount + $tr_tex ,$invoice->created_by,$invoice->workspace)}}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tfoot>
                                                <tr>
                                                    <td></td>
                                                    <td><b>{{ __('Total') }}</b></td>
                                                    <td><b>{{ $totalQuantity }}</b></td>
                                                    <td><b>{{ currency_format_with_sym($totalRate, $invoice->created_by,$invoice->workspace) }}</b>
                                                    </td>
                                                    <td><b>{{ currency_format_with_sym($totalDiscount, $invoice->created_by,$invoice->workspace) }}</b></td>
                                                    <td><b>{{ currency_format_with_sym($totalTaxPrice, $invoice->created_by,$invoice->workspace) }}</b></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                    <td class="text-end">
                                                        {{ currency_format_with_sym($invoice->getSubTotal(), $invoice->created_by,$invoice->workspace) }}
                                                    </td>
                                                </tr>
                                                    <tr>
                                                        <td colspan="6"></td>
                                                        <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                        <td class="text-end">
                                                            {{ currency_format_with_sym($invoice->getTotalDiscount(), $invoice->created_by,$invoice->workspace) }}
                                                        </td>
                                                    </tr>
                                                @if (!empty($taxesData))
                                                    @foreach ($taxesData as $taxName => $taxPrice)
                                                        <tr>
                                                            <td colspan="6"></td>
                                                            <td class="text-end"><b>{{ $taxName }}</b></td>
                                                            <td class="text-end">
                                                                {{ currency_format_with_sym($taxPrice, $invoice->created_by,$invoice->workspace) }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                    <td class="blue-text text-end">
                                                        {{ currency_format_with_sym($invoice->getTotal(), $invoice->created_by,$invoice->workspace) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Paid') }}</b></td>
                                                    <td class="text-end">
                                                        {{ currency_format_with_sym($invoice->getTotal() - $invoice->getDue() - $invoice->invoiceTotalCreditNote(), $invoice->created_by,$invoice->workspace) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Credit Note') }}</b></td>
                                                    <td class="text-end">
                                                        {{ currency_format_with_sym($invoice->invoiceTotalCreditNote(), $invoice->created_by,$invoice->workspace) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Due') }}</b></td>
                                                    <td class="text-end">
                                                        {{ currency_format_with_sym($invoice->getDue(), $invoice->created_by,$invoice->workspace) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Receipt Summary') }}</h5>
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table ">
                            <tr>
                                <th class="text-dark">{{ __('Date') }}</th>
                                <th class="text-dark">{{ __('Amount') }}</th>
                                <th class="text-dark">{{ __('Payment Type') }}</th>
                                <th class="text-dark">{{ __('Account') }}</th>
                                <th class="text-dark">{{ __('Reference') }}</th>
                                <th class="text-dark">{{ __('Receipt') }}</th>
                                <th class="text-dark">{{ __('Description') }}</th>
                                <th class="text-dark">{{ __('OrderId') }}</th>
                            </tr>
                            @forelse($invoice->payments as $key =>$payment)
                                <tr>
                                    <td>{{ company_date_formate($payment->date, $invoice->created_by,$invoice->workspace) }}</td>
                                    <td>{{ currency_format_with_sym($payment->amount, $invoice->created_by,$invoice->workspace) }}</td>
                                    <td>{{ $payment->payment_type }}</td>
                                    @if(module_is_active('Account'))
                                        <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '--' }}
                                    @else
                                        <td>--</td>
                                    @endif
                                    <td>{{ !empty($payment->reference) ? $payment->reference : '--' }}</td>
                                    <td>
                                        @if(!empty($payment->add_receipt) && empty($payment->receipt))
                                            <a href="{{ get_file($payment->add_receipt)}}" download="" class="btn btn-sm btn-primary btn-icon rounded-pill" target="_blank"><span class="btn-inner--icon"><i class="ti ti-download"></i></span></a>
                                            <a href="{{ get_file($payment->add_receipt)}}"  class="btn btn-sm btn-secondary btn-icon rounded-pill" target="_blank"><span class="btn-inner--icon"><i class="ti ti-crosshair"></i></span></a>
                                        @elseif (!empty($payment->receipt) && empty($payment->add_receipt)&& $payment->type == 'STRIPE')
                                               <a href="{{$payment->receipt}}" target="_blank"> <i class="ti ti-file"></i></a>
                                        @elseif($payment->payment_type == 'Bank Transfer')
                                            <a href="{{ !empty($payment->receipt) ? (check_file($payment->receipt)) ? get_file($payment->receipt) : '#!' : '#!' }}" target="_blank" >
                                                <i class="ti ti-file"></i>
                                            </a>
                                        @else
                                                --
                                        @endif
                                    </td>
                                    <td style="white-space: break-spaces;">{{ !empty($payment->description) ? $payment->description : '--' }}</td>
                                    <td>{{ !empty($payment->order_id) ? $payment->order_id : '--' }}</td>
                                </tr>
                            @empty
                                @include('layouts.nodatafound')
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if (module_is_active('Account'))
            <div class="col-12">
                <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Credit Note Summary') }}</h5>
                <div class="card">
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table ">
                                <tr>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark" class="">{{ __('Amount') }}</th>
                                    <th class="text-dark" class="">{{ __('Description') }}</th>
                                    @if (Gate::check('edit credit note') || Gate::check('delete credit note'))
                                        <th class="text-dark">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                                @forelse($invoice->creditNote as $key =>$creditNote)
                                    <tr>
                                        <td>{{ company_date_formate($creditNote->date,$invoice->created_by,$invoice->workspace) }}</td>
                                        <td class="">
                                            {{ currency_format_with_sym($creditNote->amount, $invoice->created_by,$invoice->workspace) }}</td>
                                        <td class="">{{ $creditNote->description }}</td>
                                        <td>
                                            @can('edit credit note')
                                                <a data-url="{{ route('invoice.edit.credit.note', [$creditNote->invoice, $creditNote->id]) }}"
                                                    data-ajax-popup="true" data-title="{{ __('Add Credit Note') }}"
                                                    data-toggle="tooltip" data-original-title="{{ __('Credit Note') }}"
                                                    href="#" class="mx-3 btn btn-sm align-items-center"
                                                    data-toggle="tooltip" data-original-title="{{ __('Edit') }}">
                                                    <i class="ti ti-edit text-white"></i>
                                                </a>
                                            @endcan
                                            @can('delete credit note')
                                                <a href="#" class="mx-3 btn btn-sm align-items-center "
                                                    data-toggle="tooltip" data-original-title="{{ __('Delete') }}"
                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                    data-confirm-yes="document.getElementById('delete-form-{{ $creditNote->id }}').submit();">
                                                    <i class="ti ti-trash text-white"></i>
                                                </a>
                                                {!! Form::open([
                                                    'method' => 'DELETE',
                                                    'route' => ['invoice.delete.credit.note', $creditNote->invoice, $creditNote->id],
                                                    'id' => 'delete-form-' . $creditNote->id,
                                                ]) !!}
                                                {!! Form::close() !!}
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    @include('layouts.nodatafound')
                                @endforelse
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
        @if ($invoice->getDue() > 0)
        <div id="paymentModal" class="modal" tabindex="-1" aria-labelledby="exampleModalLongTitle" aria-modal="true"
            role="dialog" data-keyboard="false" data-backdrop="static">

            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">{{ __('Add Payment') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row pb-3 px-2">
                            <section class="">
                                <ul class="nav nav-pills  mb-3" id="pills-tab" role="tablist">
                                    @if (company_setting('bank_transfer_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on' &&
                                        !empty(company_setting('bank_number', $invoice->created_by,$invoice->workspace)) )
                                        <li class="nav-item">
                                            <a class="nav-link" id="pills-home-tab" data-bs-toggle="pill"
                                                data-bs-target="#bank-payment" type="button" role="tab"
                                                aria-controls="pills-home" aria-selected="true">{{ __('Bank trasfer') }}</a>
                                        </li>
                                    @endif
                                    @if (module_is_active('Stripe', $invoice->created_by) &&
                                        (company_setting('stripe_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        (company_setting('stripe_key', $invoice->created_by,$invoice->workspace)) &&
                                        (company_setting('stripe_secret', $invoice->created_by,$invoice->workspace)))
                                        @include('stripe::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Paypal', $invoice->created_by) &&
                                        (company_setting('paypal_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        (company_setting('company_paypal_client_id', $invoice->created_by,$invoice->workspace)) &&
                                        (company_setting('company_paypal_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('paypal::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Paystack', $invoice->created_by) &&
                                        (company_setting('paystack_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_paystack_public_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paystack_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('paystack::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif

                                    @if (module_is_active('Flutterwave', $invoice->created_by) &&
                                        (company_setting('flutterwave_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_flutterwave_public_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_flutterwave_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('flutterwave::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Razorpay', $invoice->created_by) &&
                                        (company_setting('razorpay_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_razorpay_public_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_razorpay_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('razorpay::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Mercado',$invoice->created_by,$invoice->workspace) &&
                                        (company_setting('mercado_payment_is_on', $invoice->created_by,$invoice->workspace)) == 'on' &&
                                        !empty(company_setting('company_mercado_mode', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_mercado_access_token', $invoice->created_by,$invoice->workspace)))
                                        @include('mercado::payment.sidebar',['invoice' => $invoice,'type'=>'invoice'])
                                    @endif
                                    @if (module_is_active('Paytm',$invoice->created_by) &&
                                        (company_setting('paytm_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_paytm_mode', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paytm_merchant_id', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paytm_merchant_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paytm_industry_type', $invoice->created_by,$invoice->workspace)))
                                        @include('paytm::payment.sidebar',['invoice' => $invoice,'type'=>'invoice'])
                                    @endif

                                    @if (module_is_active('Mollie', $invoice->created_by) &&
                                    (company_setting('mollie_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                    !empty(company_setting('company_mollie_api_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_mollie_profile_id', $invoice->created_by,$invoice->workspace))&&
                                    !empty(company_setting('company_mollie_partner_id', $invoice->created_by,$invoice->workspace)))
                                        @include('mollie::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Skrill', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('skrill_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_skrill_email', $invoice->created_by,$invoice->workspace)))
                                        @include('skrill::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Coingate', $invoice->created_by,$invoice->workspace) &&
                                        (company_setting('coingate_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                        !empty(company_setting('company_coingate_mode', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_coingate_auth_token', $invoice->created_by,$invoice->workspace)))
                                        @include('coingate::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Paymentwall', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('paymentwall_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_paymentwall_public_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_paymentwall_private_key', $invoice->created_by,$invoice->workspace)))
                                    @include('paymentwall::payment.sidebar', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('Toyyibpay', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('toyyibpay_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_toyyibpay_secrect_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_toyyibpay_category_code', $invoice->created_by,$invoice->workspace)))
                                    @include('toyyibpay::payment.sidebar', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('SSPay', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('sspay_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_sspay_secrect_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_sspay_category_code', $invoice->created_by,$invoice->workspace)))
                                    @include('sspay::payment.sidebar', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('Payfast', $invoice->created_by,$invoice->workspace) &&
                                        (company_setting('payfast_payment_is_on', $invoice->created_by,$invoice->workspace)) == 'on' &&
                                        !empty(company_setting('company_payfast_merchant_id', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_payfast_merchant_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_payfast_signature', $invoice->created_by,$invoice->workspace)))
                                        @include('payfast::payment.sidebar', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Iyzipay', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('iyzipay_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_iyzipay_mode', $invoice->created_by,$invoice->workspace))&&
                                    !empty(company_setting('company_iyzipay_secret_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_iyzipay_api_key', $invoice->created_by,$invoice->workspace)))
                                    @include('iyzipay::payment.sidebar', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('PayTab', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('paytab_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('paytab_profile_id', $invoice->created_by,$invoice->workspace))&&
                                    !empty(company_setting('paytab_server_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('paytab_region', $invoice->created_by,$invoice->workspace)))
                                    @include('paytab::payment.sidebar', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('YooKassa', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('yookassa_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_yookassa_shop_id', $invoice->created_by,$invoice->workspace))&&
                                    !empty(company_setting('company_yookassa_secret_key', $invoice->created_by,$invoice->workspace)))
                                    @include('yookassa::payment.sidebar', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                </ul>

                                <div class="tab-content" id="pills-tabContent">
                                    @if (company_setting('bank_transfer_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on' &&
                                        !empty(company_setting('bank_number', $invoice->created_by,$invoice->workspace)) )
                                        <div class="tab-pane fade " id="bank-payment" role="tabpanel"
                                            aria-labelledby="bank-payment">
                                            <form method="post" action="{{ route('invoice.pay.with.bank') }}"
                                                class="require-validation" id="payment-form" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="type" value="invoice">
                                                <div class="row mt-2">
                                                    <div class="col-sm-8">
                                                        <div class="form-group">
                                                            <label class="form-label">{{ __('Bank Details :') }}</label>
                                                            <p class="">
                                                                {!!company_setting('bank_number',$invoice->created_by,$invoice->workspace) !!}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <label class="form-label">{{ __('Payment Receipt') }}</label>
                                                            <div class="choose-files">
                                                            <label for="payment_receipt">
                                                                <div class=" bg-primary "> <i class="ti ti-upload px-1"></i></div>
                                                                <input type="file" class="form-control" required="" accept="image/png, image/jpeg, image/jpg, .pdf" name="payment_receipt" id="payment_receipt" data-filename="payment_receipt" onchange="document.getElementById('blah3').src = window.URL.createObjectURL(this.files[0])">
                                                            </label>
                                                            <p class="text-danger error_msg d-none">{{ __('This field is required')}}</p>

                                                            <img class="mt-2" width="70px"  id="blah3">
                                                        </div>
                                                            <div class="invalid-feedback">{{ __('invalid form file') }}</div>
                                                        </div>
                                                    </div>
                                                    <small class="text-danger">{{ __('first, make a payment and take a screenshot or download the receipt and upload it.')}}</small>
                                                </div>
                                                <div class="row">
                                                    <div class="form-group col-md-12">
                                                        <label for="amount">{{ __('Amount') }}</label>
                                                        <div class="input-group">
                                                            <span class="input-group-prepend"><span
                                                                    class="input-group-text">{{ !empty(company_setting('defult_currancy', $invoice->created_by,$invoice->workspace)) ? company_setting('defult_currancy', $invoice->created_by,$invoice->workspace) : '$' }}</span></span>
                                                            <input class="form-control" required="required"
                                                                min="0" name="amount" type="number"
                                                                value="{{ $invoice->getDue() }}" min="0"
                                                                step="0.01" max="{{ $invoice->getDue() }}"
                                                                id="amount">
                                                            <input type="hidden" value="{{ $invoice->id }}"
                                                                name="invoice_id">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="error" style="display: none;">
                                                            <div class='alert-danger alert'>
                                                                {{ __('Please correct the errors and try again.') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <button type="button" class="btn  btn-light"
                                                        data-bs-dismiss="modal">{{ __('Close') }}</button>
                                                    <button class="btn btn-primary"
                                                        type="submit">{{ __('Make Payment') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                    @if (module_is_active('Stripe', $invoice->created_by) &&
                                        (company_setting('stripe_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('stripe_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('stripe_secret', $invoice->created_by,$invoice->workspace)))
                                        @include('stripe::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif

                                    @if (module_is_active('Paypal', $invoice->created_by) &&
                                        (company_setting('paypal_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_paypal_client_id', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paypal_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('paypal::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Paystack', $invoice->created_by) &&
                                        (company_setting('paystack_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_paystack_public_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paystack_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('paystack::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Flutterwave', $invoice->created_by) &&
                                        (company_setting('flutterwave_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_flutterwave_public_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_flutterwave_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('flutterwave::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif

                                    @if (module_is_active('Razorpay', $invoice->created_by) &&
                                        (company_setting('razorpay_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_razorpay_public_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_razorpay_secret_key', $invoice->created_by,$invoice->workspace)))
                                        @include('razorpay::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Mercado',$invoice->created_by,$invoice->workspace) &&
                                        (company_setting('mercado_payment_is_on', $invoice->created_by,$invoice->workspace)) == 'on' &&
                                        !empty(company_setting('company_mercado_mode', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_mercado_access_token', $invoice->created_by,$invoice->workspace)))
                                        @include('mercado::payment.nav_containt_div',['invoice' => $invoice,'type'=>'invoice'])
                                    @endif
                                    @if (module_is_active('Paytm',$invoice->created_by) &&
                                        (company_setting('paytm_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_paytm_mode', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paytm_merchant_id', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paytm_merchant_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_paytm_industry_type', $invoice->created_by,$invoice->workspace)))
                                        @include('paytm::payment.nav_containt_div',['invoice' => $invoice,'type'=>'invoice'])
                                    @endif

                                    @if (module_is_active('Mollie', $invoice->created_by) &&
                                        (company_setting('mollie_payment_is_on', $invoice->created_by,$invoice->workspace)  == 'on') &&
                                        !empty(company_setting('company_mollie_api_key', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_mollie_profile_id', $invoice->created_by,$invoice->workspace))&&
                                        !empty(company_setting('company_mollie_partner_id', $invoice->created_by,$invoice->workspace)))
                                        @include('mollie::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Skrill', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('skrill_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_skrill_email', $invoice->created_by,$invoice->workspace)) )
                                        @include('skrill::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Coingate', $invoice->created_by,$invoice->workspace) &&
                                        (company_setting('coingate_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                        !empty(company_setting('company_coingate_mode', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_coingate_auth_token', $invoice->created_by,$invoice->workspace)))
                                        @include('coingate::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Paymentwall', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('paymentwall_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_paymentwall_public_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_paymentwall_private_key', $invoice->created_by,$invoice->workspace)))
                                    @include('paymentwall::payment.nav_containt_div', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('Toyyibpay', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('toyyibpay_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_toyyibpay_secrect_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_toyyibpay_category_code', $invoice->created_by,$invoice->workspace)))
                                    @include('toyyibpay::payment.nav_containt_div', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('SSPay', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('sspay_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_sspay_secrect_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_sspay_category_code', $invoice->created_by,$invoice->workspace)))
                                    @include('sspay::payment.nav_containt_div', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('Payfast', $invoice->created_by,$invoice->workspace) &&
                                        (company_setting('payfast_payment_is_on', $invoice->created_by,$invoice->workspace)) == 'on' &&
                                        !empty(company_setting('company_payfast_merchant_id', $invoice->created_by,$invoice->workspace)) &&
                                        !empty(company_setting('company_payfast_merchant_key', $invoice->created_by,$invoice->workspace))&&
                                        !empty(company_setting('company_payfast_signature', $invoice->created_by,$invoice->workspace)))
                                        @include('payfast::payment.nav_containt_div', [
                                            'invoice' => $invoice,
                                            'type' => 'invoice',
                                        ])
                                    @endif
                                    @if (module_is_active('Iyzipay', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('iyzipay_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_iyzipay_mode', $invoice->created_by,$invoice->workspace))&&
                                    !empty(company_setting('company_iyzipay_secret_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('company_iyzipay_api_key', $invoice->created_by,$invoice->workspace)))
                                    @include('iyzipay::payment.nav_containt_div', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('PayTab', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('paytab_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('paytab_profile_id', $invoice->created_by,$invoice->workspace))&&
                                    !empty(company_setting('paytab_server_key', $invoice->created_by,$invoice->workspace)) &&
                                    !empty(company_setting('paytab_region', $invoice->created_by,$invoice->workspace)))
                                    @include('paytab::payment.nav_containt_div', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                    @if (module_is_active('YooKassa', $invoice->created_by,$invoice->workspace) &&
                                    (company_setting('yookassa_payment_is_on', $invoice->created_by,$invoice->workspace) == 'on') &&
                                    !empty(company_setting('company_yookassa_shop_id', $invoice->created_by,$invoice->workspace))&&
                                    !empty(company_setting('company_yookassa_secret_key', $invoice->created_by,$invoice->workspace)))
                                    @include('yookassa::payment.nav_containt_div', [
                                        'invoice' => $invoice,
                                        'type' => 'invoice',
                                    ])
                                    @endif
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif


@endsection
@push('scripts')
<script>
    $("#paymentModals").click(function(){
        $("#paymentModal").modal('show');
        $("ul li a").removeClass("active");
        $(".tab-pane").removeClass("active show");
        $("ul li:first a:first").addClass("active");
        $(".tab-pane:first").addClass("active show");
    });
</script>
@endpush
