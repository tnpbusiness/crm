@extends('layouts.main')
@section('page-title')
    {{ __('Customer-Detail') }}
@endsection
@section('page-breadcrumb')
    {{ __('Customer') }},{{ $customer['name'] }}
@endsection
@push('css')
<style>
    .cus-card {
        min-height: 204px;
    }
</style>
@endpush
@push('scripts')
    <script>
        $(document).on('click', '#billing_data', function () {
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
@section('page-action')
    <div>
        @php
            $user_id = !empty($customer->GetUserIdByCustomerId($customer['id'])) ? $customer->GetUserIdByCustomerId($customer['id'])->user_id : null;
        @endphp
        @can('invoice create')
            <a href="{{ route('invoice.create', $customer->id) }}" class="btn btn-sm btn-primary">
                {{ __('Create Invoice') }}
            </a>
        @endcan
        @can('customer create')
            @if (!empty($user_id))
                <a href="{{ route('proposal.create', $customer->id) }}" class="btn btn-sm btn-primary">
                    {{ __('Create Proposal') }}
                </a>
            @endif
        @endcan
        <a href="{{ route('customer.statement', $customer['id']) }}" class="btn btn-sm btn-primary">
            {{ __('Statement') }}
        </a>
        @can('customer edit')
            @if (!empty($user_id))
                <a  class="btn btn-sm btn-primary action-btn px-1" data-url="{{ route('customer.edit', $user_id) }}"
                    data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip" title=""
                    data-title="{{ __('Edit Customer') }}" data-bs-original-title="{{ __('Edit') }}">
                    <i class="ti ti-pencil"></i>
                </a>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card customer-detail-box">
                <div class="card-body cus-card">
                    <h5 class="card-title">{{ __('Customer Info') }}</h5>
                    <p class="card-text mb-0">{{ $customer['name'] }}</p>
                    <p class="card-text mb-0">{{ $customer['email'] }}</p>
                    <p class="card-text mb-0">{{ $customer['contact'] }}</p>
                    @if(!empty($customFields) && count($customer->customField)>0)
                    @foreach($customFields as $field)
                    <p class="card-text mb-0">
                        <strong >{{$field->name}} : </strong>{{!empty($customer->customField)?$customer->customField[$field->id]:'-'}}
                    </p>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card customer-detail-box">
                <div class="card-body cus-card">
                    <h5 class="card-title">{{ __('Billing Info') }}</h5>
                    <p class="card-text mb-0">{{ $customer['billing_name'] }}</p>
                    <p class="card-text mb-0">{{ $customer['billing_phone'] }}</p>
                    <p class="card-text mb-0">{{ $customer['billing_address'] }}</p>
                    <p class="card-text mb-0">
                        {{ $customer['billing_city'] . ', ' . $customer['billing_state'] . ', ' . $customer['billing_country'] }}
                    </p>
                    <p class="card-text mb-0">{{ $customer['billing_zip'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card customer-detail-box">
                <div class="card-body cus-card">
                    <h5 class="card-title">{{ __('Shipping Info') }}</h5>
                    @if (company_setting('invoice_shipping_display') == 'on' || company_setting('proposal_shipping_display') == 'on' )
                        <p class="card-text mb-0">{{ $customer['shipping_name'] }}</p>
                        <p class="card-text mb-0">{{ $customer['shipping_phone'] }}</p>
                        <p class="card-text mb-0">{{ $customer['shipping_address'] }}</p>
                        <p class="card-text mb-0">
                            {{ $customer['shipping_city'] . ', ' . $customer['shipping_state'] . ', ' . $customer['shipping_country'] }}
                        </p>
                        <p class="card-text mb-0">{{ $customer['shipping_zip'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card pb-0">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Company Info') }}</h5>

                    <div class="row">
                        @php
                            $totalInvoiceSum = $customer->customerTotalInvoiceSum($customer['id']);
                            $totalInvoice = $customer->customerTotalInvoice($customer['id']);
                            $averageSale = $totalInvoiceSum != 0 ? $totalInvoiceSum / $totalInvoice : 0;
                        @endphp
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Customer Id') }}</p>
                                <h6 class="report-text mb-3">
                                    {{ Modules\Account\Entities\Customer::customerNumberFormat($customer['customer_id']) }}
                                </h6>
                                <p class="card-text mb-0">{{ __('Total Sum of Invoices') }}</p>
                                <h6 class="report-text mb-0">{{ currency_format_with_sym($totalInvoiceSum) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Date of Creation') }}</p>
                                <h6 class="report-text mb-3">{{ company_date_formate($customer['created_at']) }}</h6>
                                <p class="card-text mb-0">{{ __('Quantity of Invoice') }}</p>
                                <h6 class="report-text mb-0">{{ $totalInvoice }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Balance') }}</p>
                                <h6 class="report-text mb-3">{{ currency_format_with_sym($customer['balance']) }}</h6>
                                <p class="card-text mb-0">{{ __('Average Sales') }}</p>
                                <h6 class="report-text mb-0">{{ currency_format_with_sym($averageSale) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Overdue') }}</p>
                                <h6 class="report-text mb-3">
                                    {{ currency_format_with_sym($customer->customerOverdue($customer['id'])) }}</h6>
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
                <div class="card-body table-border-style table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Proposal') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Proposal') }}</th>
                                    <th>{{ __('Issue Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('proposal edit') || Gate::check('proposal delete') || Gate::check('proposal show'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customer->customerProposal($customer->id) as $proposal)
                                    <tr>
                                        <td class="Id">
                                            @can('proposal show')
                                                <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                    class="btn btn-outline-primary">{{ \App\Models\Proposal::proposalNumberFormat($proposal->proposal_id) }}
                                                </a>
                                            @else
                                                <a
                                                    class="btn btn-outline-primary">{{ \App\Models\Proposal::proposalNumberFormat($proposal->proposal_id) }}
                                                </a>
                                            @endcan
                                        </td>
                                        <td>{{ company_date_formate($proposal->issue_date) }}</td>
                                        <td>{{ currency_format_with_sym($proposal->getTotal()) }}</td>
                                        <td>
                                            @if ($proposal->status == 0)
                                                <span
                                                    class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 1)
                                                <span
                                                    class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 2)
                                                <span
                                                    class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 3)
                                                <span
                                                    class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 4)
                                                <span
                                                    class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('proposal edit') || Gate::check('proposal delete') || Gate::check('proposal show'))
                                            <td class="Action">
                                                <span>
                                                    @if ($proposal->is_convert == 0)
                                                        @can('proposal convert invoice')
                                                            <div class="action-btn bg-success ms-2">
                                                                {!! Form::open([
                                                                    'method' => 'get',
                                                                    'route' => ['proposal.convert', $proposal->id],
                                                                    'id' => 'proposal-form-' . $proposal->id,
                                                                ]) !!}
                                                                <a
                                                                    class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                                    data-bs-toggle="tooltip" title=""
                                                                    data-bs-original-title="{{ __('Convert to Invoice') }}"
                                                                    aria-label="Delete"
                                                                    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="proposal-form-{{ $proposal->id }}">
                                                                    <i class="ti ti-exchange text-white"></i>
                                                                </a>
                                                                {{ Form::close() }}
                                                            </div>
                                                        @endcan
                                                    @else
                                                        @can('invoice show')
                                                            <div class="action-btn bg-success ms-2">
                                                                <a href="{{ route('invoice.show', \Crypt::encrypt($proposal->converted_invoice_id)) }}"
                                                                    class="mx-3 btn btn-sm  align-items-center"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ __('Already convert to Invoice') }}">
                                                                    <i class="ti ti-eye text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                    @endif
                                                    @can('duplicate proposal')
                                                        <div class="action-btn bg-secondary ms-2">
                                                            {!! Form::open([
                                                                'method' => 'get',
                                                                'route' => ['proposal.duplicate', $proposal->id],
                                                                'id' => 'duplicate-form-' . $proposal->id,
                                                            ]) !!}
                                                            <a
                                                                class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                                data-bs-toggle="tooltip" title=""
                                                                data-bs-original-title="{{ __('Duplicate') }}"
                                                                aria-label="Delete"
                                                                data-text="{{ __('You want to confirm duplicate this invoice. Press Yes to continue or Cancel to go back') }}"
                                                                data-confirm-yes="duplicate-form-{{ $proposal->id }}">
                                                                <i class="ti ti-copy text-white text-white"></i>
                                                            </a>
                                                            {{ Form::close() }}
                                                        </div>
                                                    @endcan
                                                    @can('proposal show')
                                                        @if (\Auth::user()->type == 'client')
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('customer.proposal.show', $proposal->id) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                                    class="mx-3 btn btn-sm  align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('proposal edit')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('proposal.edit', \Crypt::encrypt($proposal->id)) }}"
                                                                class="mx-3 btn btn-sm  align-items-center"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan

                                                    @can('proposal delete')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {{ Form::open(['route' => ['proposal.destroy', $proposal->id], 'class' => 'm-0']) }}
                                                            @method('DELETE')
                                                            <a
                                                                class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                                data-bs-toggle="tooltip" title=""
                                                                data-bs-original-title="Delete" aria-label="Delete"
                                                                data-confirm="{{ __('Are You Sure?') }}"
                                                                data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="delete-form-{{ $proposal->id }}"><i
                                                                    class="ti ti-trash text-white text-white"></i></a>
                                                            {{ Form::close() }}
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
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Invoice') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Issue Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Due Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('invoice edit') || Gate::check('invoice delete') || Gate::check('invoice show'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customer->customerInvoice($customer->id) as $invoice)
                                    <tr>
                                        <td class="Id">
                                        @can('invoice show')
                                            <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                class="btn btn-outline-primary">{{ \App\Models\Invoice::invoiceNumberFormat($invoice->invoice_id) }}
                                            </a>
                                        @else
                                            <a
                                                class="btn btn-outline-primary">{{ \App\Models\Invoice::invoiceNumberFormat($invoice->invoice_id) }}
                                            </a>
                                        @endcan
                                        </td>
                                        <td>{{ company_date_formate($invoice->issue_date) }}</td>
                                        <td>
                                            @if ($invoice->due_date < date('Y-m-d'))
                                                <p class="text-danger"> {{ company_date_formate($invoice->due_date) }}</p>
                                            @else
                                                {{ company_date_formate($invoice->due_date) }}
                                            @endif
                                        </td>
                                        <td>{{ currency_format_with_sym($invoice->getDue()) }}</td>
                                        <td>
                                            @if ($invoice->status == 0)
                                                <span
                                                    class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 1)
                                                <span
                                                    class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 2)
                                                <span
                                                    class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 3)
                                                <span
                                                    class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @elseif($invoice->status == 4)
                                                <span
                                                    class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('invoice edit') || Gate::check('invoice delete') || Gate::check('invoice show'))
                                            <td class="Action">
                                                <span>
                                                    @can('duplicate invoice')
                                                        <div class="action-btn bg-secondary ms-2">

                                                            {!! Form::open([
                                                                'method' => 'get',
                                                                'route' => ['invoice.duplicate', $invoice->id],
                                                                'id' => 'invoice-duplicate-form-' . $invoice->id,
                                                            ]) !!}

                                                            <a
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('Duplicate Invoice') }}"
                                                                data-original-title="{{ __('Duplicate') }}"
                                                                data-confirm="{{ __('You want to confirm this action. Press Yes to continue or Cancel to go back') }}"
                                                                data-confirm-yes="document.getElementById('invoice-duplicate-form-{{ $invoice->id }}').submit();">
                                                                <i class="ti ti-copy text-white text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}

                                                        </div>
                                                    @endcan
                                                    @can('invoice show')
                                                        @if (\Auth::user()->type == 'client')
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('customer.invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="action-btn bg-warning ms-2">
                                                                <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                                    <i class="ti ti-eye text-white text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('invoice edit')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('invoice.edit', \Crypt::encrypt($invoice->id)) }}"
                                                                class="mx-3 btn btn-sm  align-items-center"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('invoice delete')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {{ Form::open(['route' => ['invoice.destroy', $invoice->id], 'class' => 'm-0']) }}
                                                            @method('DELETE')
                                                            <a
                                                                class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm"
                                                                data-bs-toggle="tooltip" title=""
                                                                data-bs-original-title="Delete" aria-label="Delete"
                                                                data-confirm="{{ __('Are You Sure?') }}"
                                                                data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="delete-form-{{ $invoice->id }}">
                                                                <i class="ti ti-trash text-white text-white"></i>
                                                            </a>
                                                            {{ Form::close() }}
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
