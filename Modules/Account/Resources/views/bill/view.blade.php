@extends('layouts.main')
@section('page-title')
    {{ __('Bill Detail') }}
@endsection
@section('page-breadcrumb')
    {{ __('Bill Detail') }}
@endsection
@push('scripts')
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
@endpush
@push('css')
<style>
    .bill_status{
        min-width: 94px;
    }
</style>
@endpush
@section('page-action')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary cp_link" data-link="{{route('pay.billpay',\Illuminate\Support\Facades\Crypt::encrypt($bill->id))}}" data-bs-toggle="tooltip" title="{{__('copy')}}"   data-original-title="{{__('Click to copy invoice link')}}">
            <span class="btn-inner--icon text-white"><i class="ti ti-file"></i></span>
        </a>
    </div>
@endsection
@section('content')
        @if($bill->status!=4)
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row timeline-wrapper">
                                <div class="col-md-6 col-lg-4 col-xl-4">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-plus text-primary"></i>
                                    </div>
                                    <h6 class="text-primary my-3">{{__('Create Bill')}}</h6>
                                    <p class="text-muted text-sm mb-3"><i class="ti ti-clock mr-2"></i>{{__('Created on ')}}{{ company_date_formate($bill->bill_date)}}</p>
                                    @can('bill edit')
                                        <a href="{{ route('bill.edit',\Crypt::encrypt($bill->id)) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}"><i class="ti ti-pencil mr-2"></i>{{__('Edit')}}</a>
                                    @endcan
                                </div>
                                <div class="col-md-6 col-lg-4 col-xl-4">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-mail text-warning"></i>
                                    </div>
                                    <h6 class="text-warning my-3">{{__('Send Bill')}}</h6>
                                    <p class="text-muted text-sm mb-3">
                                        @if($bill->status!=0)
                                            <i class="ti ti-clock mr-2"></i>{{__('Sent on')}} {{ company_date_formate($bill->send_date)}}
                                        @else
                                            @can('bill send')
                                                <small>{{__('Status')}} : {{__('Not Sent')}}</small>
                                            @endcan
                                        @endif
                                    </p>
                                    @if($bill->status==0)
                                        @can('bill send')
                                                <a href="{{ route('bill.sent',$bill->id) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-original-title="{{__('Mark Sent')}}"><i class="ti ti-send mr-2"></i>{{__('Send')}}</a>
                                        @endcan
                                    @endif
                                </div>
                                <div class="col-md-6 col-lg-4 col-xl-4">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-info"></i>
                                    </div>
                                    <h6 class="text-info my-3">{{__('Get Paid')}}</h6>
                                    <p class="text-muted text-sm mb-3">{{__('Status')}} : {{__('Awaiting payment')}} </p>
                                    @if($bill->status!=0)
                                        @can('bill payment create')
                                            <a href="#" data-url="{{ route('bill.payment',$bill->id) }}" data-ajax-popup="true" data-title="{{__('Add Payment')}}" class="btn btn-sm btn-info" data-original-title="{{__('Add Payment')}}"><i class="ti ti-report-money mr-2"></i>{{__('Add Payment')}}</a> <br>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @if(\Auth::user()->type=='company')
        @if($bill->status!=0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                    @if(!empty($billPayment))
                        <div class="all-button-box mx-2">
                            <a href="#" data-url="{{ route('bill.debit.note',$bill->id) }}" data-ajax-popup="true" data-title="{{__('Add Debit Note')}}" class="btn btn-sm btn-primary">
                                {{__('Add Debit Note')}}
                            </a>
                        </div>
                    @endif
                    <div class="all-button-box mx-2">
                        <a href="{{ route('bill.resent',$bill->id) }}" class="btn btn-sm btn-primary">
                            {{__('Resend Bill')}}
                        </a>
                    </div>
                    <div class="all-button-box">
                        <a href="{{ route('bill.pdf', Crypt::encrypt($bill->id))}}" target="_blank" class="btn btn-sm btn-primary">
                            {{__('Download')}}
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                <div class="all-button-box mx-2">
                    <a href="#" data-url="{{route('bill.sent',$bill->id)}}" data-ajax-popup="true" data-title="{{__('Send Bill')}}" class="btn btn-sm btn-primary">
                        {{__('Send Mail')}}
                    </a>
                </div>
                <div class="all-button-box mx-2">
                    <a href="{{ route('bill.pdf', Crypt::encrypt($bill->id))}}" target="_blank" class="btn btn-sm btn-primary">
                        {{__('Download')}}
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h2>{{__('Bill')}}</h2>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h3 class="invoice-number float-end">{{ Modules\Account\Entities\Bill::billNumberFormat($bill->bill_id) }}</h3>
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
                                                {{ company_date_formate($bill->bill_date)}}<br><br>
                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{__('Due Date')}} :</strong><br>
                                                {{ company_date_formate($bill->due_date)}}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                @if(!empty($vendor->billing_name))
                                    <div class="col">
                                        <small class="font-style">
                                            <strong>{{__('Billed To')}} :</strong><br>
                                            {{ !empty($vendor->billing_name) ? $vendor->billing_name : '' }}<br>
                                            {{ !empty($vendor->billing_address) ? $vendor->billing_address : '' }}<br>
                                            {{ !empty($vendor->billing_city) ? $vendor->billing_city . ' ,' : '' }}
                                            {{ !empty($vendor->billing_state) ? $vendor->billing_state . ' ,' : '' }}
                                            {{ !empty($vendor->billing_zip) ? $vendor->billing_zip : '' }}<br>
                                            {{ !empty($vendor->billing_country) ? $vendor->billing_country : '' }}<br>
                                            {{ !empty($vendor->billing_phone) ? $vendor->billing_phone : '' }}<br>
                                            <strong>{{__('Tax Number ')}} : </strong>{{!empty($vendor->tax_number)?$vendor->tax_number:''}}
                                        </small>
                                    </div>
                                @endif
                                @if(company_setting('bill_shipping_display')=='on')
                                    <div class="col">
                                        <small>
                                            <strong>{{__('Shipped To')}} :</strong><br>
                                            {{ !empty($vendor->shipping_name) ? $vendor->shipping_name : '' }}<br>
                                            {{ !empty($vendor->shipping_address) ? $vendor->shipping_address : '' }}<br>
                                            {{ !empty($vendor->shipping_city) ? $vendor->shipping_city .' ,': '' }}
                                            {{ !empty($vendor->shipping_state) ? $vendor->shipping_state .' ,': '' }}
                                            {{ !empty($vendor->shipping_zip) ? $vendor->shipping_zip : '' }}<br>
                                            {{ !empty($vendor->shipping_country) ? $vendor->shipping_country : '' }}<br>
                                            {{ !empty($vendor->shipping_phone) ? $vendor->shipping_phone : '' }}<br>
                                            <strong>{{__('Tax Number ')}} : </strong>{{!empty($vendor->tax_number)?$vendor->tax_number:''}}

                                        </small>
                                    </div>
                                @endif
                                    <div class="col">
                                        <div class="float-end mt-3 ">
                                         {!! DNS2D::getBarcodeHTML(route('pay.billpay',\Illuminate\Support\Facades\Crypt::encrypt($bill->id)), "QRCODE",2,2) !!}
                                        </div>
                                    </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{__('Status')}} :</strong><br>
                                        @if ($bill->status == 0)
                                            <span
                                                class="badge fix_badges bg-primary p-2 px-3 rounded bill_status">{{ __(Modules\Account\Entities\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 1)
                                            <span
                                                class="badge fix_badges bg-info p-2 px-3 rounded bill_status">{{ __(Modules\Account\Entities\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 2)
                                            <span
                                                class="badge fix_badges bg-secondary p-2 px-3 rounded bill_status">{{ __(Modules\Account\Entities\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 3)
                                            <span
                                                class="badge fix_badges bg-warning p-2 px-3 rounded bill_status">{{ __(Modules\Account\Entities\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 4)
                                            <span
                                                class="badge fix_badges bg-danger p-2 px-3 rounded bill_status">{{ __(Modules\Account\Entities\Bill::$statues[$bill->status]) }}</span>
                                        @endif
                                    </small>
                                </div>
                                @if(!empty($customFields) && count($bill->customField)>0)
                                    @foreach($customFields as $field)
                                        <div class="col text-md-end">
                                            <small>
                                                <strong>{{$field->name}} :</strong><br>
                                                {{!empty($bill->customField)?$bill->customField[$field->id]:'-'}}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-bold mb-2">{{__('Product Summary')}}</div>
                                    <small class="mb-2">{{__('All items here cannot be deleted.')}}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 table-striped">
                                            <tr>
                                                <th class="text-dark" data-width="40">#</th>
                                                <th class="text-dark">{{__('Product')}}</th>
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
                                            @endphp

                                            @foreach($iteams as $key =>$iteam)
                                                @if(!empty($iteam->tax))
                                                    @php
                                                        $taxes= Modules\Account\Entities\AccountUtility::tax($iteam->tax);
                                                        $totalQuantity+=$iteam->quantity;
                                                        $totalRate+=$iteam->price;
                                                        $totalDiscount+=$iteam->discount;
                                                        foreach($taxes as $taxe){
                                                            $taxDataPrice= Modules\Account\Entities\AccountUtility::taxRate($taxe->rate,$iteam->price,$iteam->quantity,$iteam->discount);
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
                                                    <td>{{!empty($iteam->product())?$iteam->product()->name:''}}</td>
                                                    <td>{{$iteam->quantity}}</td>
                                                    <td>{{ currency_format_with_sym($iteam->price)}}</td>
                                                    <td>{{currency_format_with_sym($iteam->discount)}}</td>

                                                    <td>
                                                        @if(!empty($iteam->tax))
                                                            <table>
                                                                @php
                                                                     $totalTaxRate = 0;
                                                                    $totalTaxPrice=0;
                                                                @endphp
                                                                @foreach($taxes as $tax)
                                                                    @php
                                                                        $taxPrice= Modules\Account\Entities\AccountUtility::taxRate($tax->rate,$iteam->price,$iteam->quantity,$iteam->discount);
                                                                        $totalTaxPrice+=$taxPrice;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{$tax->name .' ('.$tax->rate .'%)'}}</td>
                                                                        <td>{{ currency_format_with_sym($taxPrice)}}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    </td>
                                                    <td style="white-space: break-spaces;">{{!empty($iteam->description)?$iteam->description:'-'}}</td>
                                                    <td class="">{{ currency_format_with_sym(($iteam->price * $iteam->quantity - $iteam->discount) + $totalTaxPrice)}}</td>
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

                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Sub Total')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym($bill->getSubTotal())}}</td>
                                            </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-right"><b>{{__('Discount')}}</b></td>
                                                    <td class="text-right">{{ currency_format_with_sym($bill->getTotalDiscount())}}</td>
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
                                                <td class="blue-text text-right">{{ currency_format_with_sym($bill->getTotal())}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Paid')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym(($bill->getTotal()-$bill->getDue())-($bill->billTotalDebitNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Debit Note')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym(($bill->billTotalDebitNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-right"><b>{{__('Due')}}</b></td>
                                                <td class="text-right">{{ currency_format_with_sym($bill->getDue())}}</td>
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
    </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        <h5 class=" d-inline-block mb-5">{{__('Payment Summary')}}</h5>
                        <div class="table-responsive">
                            <table class="table">
                            <thead>

                                <tr>
                                    <th class="text-dark">{{__('Payment Receipt')}}</th>
                                    <th class="text-dark">{{__('Date')}}</th>
                                    <th class="text-dark">{{__('Amount')}}</th>
                                    <th class="text-dark">{{__('Account')}}</th>
                                    <th class="text-dark">{{__('Reference')}}</th>
                                    <th class="text-dark">{{__('Description')}}</th>
                                    @can('bill payment delete')
                                        <th class="text-dark">{{__('Action')}}</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bill->payments as $key =>$payment)
                                    <tr>
                                        <td>
                                            @if(!empty($payment->add_receipt))
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="{{ get_file($payment->add_receipt)}}" download="" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Download')}}" target="_blank" >
                                                        <i class="ti ti-download text-white" ></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-secondary ms-2">
                                                    <a href="{{ get_file($payment->add_receipt)}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Show')}}" target="_blank" >
                                                        <i class="ti ti-crosshair text-white" ></i>
                                                    </a>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ company_date_formate($payment->date)}}</td>
                                        <td>{{ currency_format_with_sym($payment->amount)}}</td>
                                        <td>{{!empty($payment->bankAccount)?$payment->bankAccount->bank_name.' '.$payment->bankAccount->holder_name:''}}</td>
                                        <td>{{$payment->reference}}</td>
                                        <td style="white-space: break-spaces;">{{$payment->description}}</td>
                                        <td class="text-dark">
                                            @can('bill payment delete')
                                                <div class="action-btn bg-danger ms-2">
                                                    {{Form::open(array('route'=>array('bill.payment.destroy',$bill->id,$payment->id),'class' => 'm-0'))}}
                                                        <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                            data-bs-toggle="tooltip" title="" data-bs-original-title="Delete"
                                                            aria-label="Delete" data-confirm="{{__('Are You Sure?')}}" data-text="{{__('This action can not be undone. Do you want to continue?')}}"  data-confirm-yes="delete-form-{{$payment->id}}">
                                                            <i class="ti ti-trash text-white text-white"></i>
                                                        </a>
                                                    {{Form::close()}}
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    @include('layouts.nodatafound')
                                @endforelse
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        <h5 class="d-inline-block mb-5">{{__('Debit Note Summary')}}</h5>
                        <div class="table-responsive">
                            <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{__('Date')}}</th>
                                    <th class="text-dark">{{__('Amount')}}</th>
                                    <th class="text-dark">{{__('Description')}}</th>
                                    @if(Gate::check('debitnote edit') || Gate::check('debitnote delete'))
                                        <th class="text-dark">{{__('Action')}}</th>
                                    @endif
                                </tr>
                            </thead>
                                @forelse($bill->debitNote as $key =>$debitNote)
                                    <tr>
                                        <td>{{ company_date_formate($debitNote->date)}}</td>
                                        <td>{{ currency_format_with_sym($debitNote->amount)}}</td>
                                        <td>{{$debitNote->description}}</td>
                                        <td>
                                            @can('debitnote edit')
                                                <div class="action-btn bg-info ms-2">
                                                    <a data-url="{{ route('bill.edit.debit.note',[$debitNote->bill,$debitNote->id]) }}" data-ajax-popup="true" data-title="{{__('Add Debit Note')}}" href="#" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('debitnote delete')
                                                    <div class="action-btn bg-danger ms-2">
                                                        {{Form::open(array('route'=>array('bill.delete.debit.note', $debitNote->bill,$debitNote->id),'class' => 'm-0'))}}
                                                        @method('DELETE')
                                                        <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                            data-bs-toggle="tooltip" title="" data-bs-original-title="Delete"
                                                            aria-label="Delete" data-confirm="{{__('Are You Sure?')}}" data-text="{{__('This action can not be undone. Do you want to continue?')}}"  data-confirm-yes="delete-form-{{$debitNote->id}}">
                                                            <i class="ti ti-trash text-white text-white"></i>
                                                        </a>
                                                    {{Form::close()}}
                                                    </div>
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
        </div>
@endsection
