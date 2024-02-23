
@extends('layouts.main')
@section('page-title')
    {{ __('Vendor-Detail') }}
@endsection
@section('page-breadcrumb')
    {{ __('Vendor-Detail') }}
@endsection
@push('scripts')
    <script>
        $(document).on('click', '#billing_data', function() {
            $("[name='shipping_name']").val($("[name='billing_name']").val());
            $("[name='shipping_country']").val($("[name='billing_country']").val());
            $("[name='shipping_state']").val($("[name='billing_state']").val());
            $("[name='shipping_city']").val($("[name='billing_city']").val());
            $("[name='shipping_phone']").val($("[name='billing_phone']").val());
            $("[name='shipping_zip']").val($("[name='billing_zip']").val());
            $("[name='shipping_address']").val($("[name='billing_address']").val());
        })
    </script>
@endpush
@push('css')
<style>
    .cus-card {
        min-height: 204px;
    }
</style>
@endpush
@section('page-action')
<div>
    @can('bill create')
        <a href="{{ route('bill.create',$vendor->id) }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus">  </i>{{__('Create Bill')}}
        </a>
    @endcan
        <a href="{{ route('vendor.statement',$vendor['id']) }}" class="btn btn-sm btn-primary">
            {{__('Statement')}}
        </a>
        @can('vendor edit')
                <a  class="btn btn-sm btn-primary action-btn px-1"
                    data-url="{{ route('vendors.edit',$vendor['user_id']) }}" data-ajax-popup="true"  data-size="lg"
                    data-bs-toggle="tooltip" title=""
                    data-title="{{ __('Edit Vendor') }}"
                    data-bs-original-title="{{ __('Edit') }}">
                    <i class="ti ti-pencil text-white"></i>
                </a>
        @endcan
</div>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card pb-0 customer-detail-box">
                <div class="card-body cus-card">
                    <h5 class="card-title">{{__('Vendor Info')}}</h5>
                    <p class="card-text mb-0">{{$vendor->name}}</p>
                    <p class="card-text mb-0">{{$vendor->email}}</p>
                    <p class="card-text mb-0">{{$vendor->contact}}</p>
                    @if(!empty($customFields) && count($vendor->customField)>0)
                        @foreach($customFields as $field)
                        <p class="card-text mb-0">
                            <strong >{{$field->name}} : </strong>{{!empty($vendor->customField)?$vendor->customField[$field->id]:'-'}}
                        </p>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card pb-0 customer-detail-box">
                <div class="card-body cus-card">
                    <h3 class="card-title">{{__('Billing Info')}}</h3>
                    <p class="card-text mb-0">{{$vendor->billing_name}}</p>
                    <p class="card-text mb-0">{{$vendor->billing_address}}</p>
                    <p class="card-text mb-0">{{$vendor->billing_city.' ,'. $vendor->billing_state .' ,'.$vendor->billing_zip}}</p>
                    <p class="card-text mb-0">{{$vendor->billing_country}}</p>
                    <p class="card-text mb-0">{{$vendor->billing_phone}}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card pb-0 customer-detail-box">
                <div class="card-body cus-card">
                    <h3 class="card-title">{{__('Shipping Info')}}</h3>
                    @if(company_setting('bill_shipping_display')=='on')
                    <p class="card-text mb-0">{{$vendor->shipping_name}}</p>
                    <p class="card-text mb-0">{{$vendor->shipping_address}}</p>
                    <p class="card-text mb-0">{{$vendor->shipping_city.' ,'. $vendor->shipping_state .' ,'.$vendor->shipping_zip}}</p>
                    <p class="card-text mb-0">{{$vendor->shipping_country}}</p>
                    <p class="card-text mb-0">{{$vendor->shipping_phone}}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card pb-0">
                <div class="card-body">
                    <h3 class="card-title">{{__('Company Info')}}</h3>
                    <div class="row">
                        @php
                            $totalBillSum=$vendor->vendorTotalBillSum($vendor['id']);
                            $totalBill=$vendor->vendorTotalBill($vendor['id']);
                            $averageSale=($totalBillSum!=0)?$totalBillSum/$totalBill:0;
                        @endphp
                        <div class="col-md-3 col-sm-6">
                            <div class="p-2">
                                <p class="card-text mb-0">{{__('Vendor Id')}}</p>
                                <h6 class="report-text mb-3">{{ Modules\Account\Entities\Vender::vendorNumberFormat($vendor->vendor_id)}}</h6>
                                <p class="card-text mb-0">{{__('Total Sum of Bills')}}</p>
                                <h6 class="report-text mb-0">{{ currency_format_with_sym($totalBillSum)}}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-2">
                                <p class="card-text mb-0">{{__('Date of Creation')}}</p>
                                <h6 class="report-text mb-3">{{ company_date_formate($vendor->created_at)}}</h6>
                                <p class="card-text mb-0">{{__('Quantity of Bills')}}</p>
                                <h6 class="report-text mb-0">{{$totalBill}}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-2">
                                <p class="card-text mb-0">{{__('Balance')}}</p>
                                <h6 class="report-text mb-3">{{ currency_format_with_sym($vendor->balance)}}</h6>
                                <p class="card-text mb-0">{{__('Average Sales')}}</p>
                                <h6 class="report-text mb-0">{{ currency_format_with_sym($averageSale)}}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-2">
                                <p class="card-text mb-0">{{__('Overdue')}}</p>
                                <h6 class="report-text mb-3">{{ currency_format_with_sym($vendor->vendorOverdue($vendor->id))}}</h6>
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
                    <h5 class="d-inline-block mb-5">{{__('Bills')}}</h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{__('Bill')}}</th>
                                    <th>{{__('Bill Date')}}</th>
                                    <th>{{__('Due Date')}}</th>
                                    <th>{{__('Due Amount')}}</th>
                                    <th>{{__('Status')}}</th>
                                    @if(Gate::check('bill edit') || Gate::check('bill delete') || Gate::check('bill show'))
                                        <th width="10%"> {{__('Action')}}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($vendor->vendorBill($vendor->id) as $bill)
                                <tr class="font-style">
                                    <td class="Id">
                                        @can('bill show')
                                            <a href="{{ route('bill.show',\Crypt::encrypt($bill->id)) }}" class="btn btn-outline-primary">{{ Modules\Account\Entities\Bill::billNumberFormat($bill->bill_id) }}
                                            </a>
                                        @else
                                            <a  class="btn btn-outline-primary">{{ Modules\Account\Entities\Bill::billNumberFormat($bill->bill_id) }}</a>
                                        @endcan
                                    </td>
                                    <td>{{ company_date_formate($bill->bill_date) }}</td>
                                    <td>
                                        @if(($bill->due_date < date('Y-m-d')))
                                            <p class="text-danger"> {{  company_date_formate($bill->due_date) }}</p>
                                        @else
                                            {{  company_date_formate($bill->due_date) }}
                                        @endif
                                    </td>
                                    <td>{{ currency_format_with_sym($bill->getDue())  }}</td>
                                    <td>
                                        @if($bill->status == 0)
                                            <span class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 1)
                                            <span class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 2)
                                            <span class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 3)
                                            <span class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 4)
                                            <span class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                        @endif
                                    </td>
                                    @if(Gate::check('bill edit') || Gate::check('bill delete') || Gate::check('bill show'))
                                        <td class="Action">
                                            <span>
                                            @can(' bill duplicate')
                                                    <div class="action-btn bg-secondary ms-2">

                                                        {!! Form::open(['method' => 'get', 'route' => ['bill.duplicate', $bill->id],'id'=>'bill-duplicate-form-'.$bill->id]) !!}
                                                            <a  class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip"  title="{{ __('Duplicate Bill') }}" data-original-title="{{__('Duplicate')}}" data-confirm="{{__('You want to confirm this action. Press Yes to continue or Cancel to go back')}}" data-confirm-yes="document.getElementById('bill-duplicate-form-{{$bill->id}}').submit();">
                                                                <i class="ti ti-copy text-white text-white"></i>
                                                            </a>
                                                        {!! Form::close() !!}

                                                    </div>
                                                @endcan
                                                @can('bill show')
                                                    <div class="action-btn bg-warning ms-2">
                                                        <a href="{{ route('bill.show',\Crypt::encrypt($bill->id)) }}" class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('Show')}}" data-original-title="{{__('Detail')}}">
                                                            <i class="ti ti-eye text-white text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('bill edit')
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('bill.edit',\Crypt::encrypt($bill->id)) }}" class="mx-3 btn btn-sm align-items-center" data-toggle="popover" title="Edit" data-original-title="{{__('Edit')}}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('bill delete')
                                                <div class="action-btn bg-danger ms-2">
                                                    {{Form::open(array('route'=>array('bill.destroy', $bill->id),'class' => 'm-0'))}}
                                                     @method('DELETE')
                                                        <a
                                                            class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                            data-toggle="popover" title="Delete" data-bs-original-title="Delete"
                                                            aria-label="Delete" data-confirm="{{__('Are You Sure?')}}" data-text="{{__('This action can not be undone. Do you want to continue?')}}"  data-confirm-yes="delete-form-{{$bill->id}}"><i
                                                                class="ti ti-trash text-white text-white"></i></a>
                                                    {{Form::close()}}
                                                </div>
                                            @endcan
                                            </span>
                                        </td>
                                    @endif
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
@endsection
