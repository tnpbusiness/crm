@extends('layouts.main')
@section('page-title')
{{ __('Subscription Setting')}}
@endsection
@section('page-breadcrumb')
{{ __('Subscription Setting') }}
@endsection
@section('page-action')
@endsection
@section('content')
<div class="row justify-content-center px-0">
    <div class=" col-12">
        <div class="card">
            {{ Form::open(['url' => 'plans']) }}
                <div class="card-body package-card-inner  d-flex align-items-center">
                    <div class="package-itm theme-avtar border border-secondary">
                        <img src="{{ (!empty(admin_setting('favicon')) && check_file(admin_setting('favicon'))) ? get_file(admin_setting('favicon')) : get_file('uploads/logo/favicon.png')}}{{'?'.time()}}" alt="">
                    </div>
                    <div class="package-itm px-3">
                        {{ Form::label('package_price_monthly', __('Basic Package Price/Month').' ( '.company_setting('defult_currancy_symbol').' )', ['class' => 'form-label']) }}
                        {{ Form::number('package_price_monthly', !empty($plan) ? $plan->package_price_monthly : null, ['class' => 'form-control','required'=>'required','placeholder' => __('Price/month'),'step' => '0.1','min'=>'0']) }}
                    </div>
                    <div class="package-itm px-3">
                        {{ Form::label('package_price_yearly', __('Basic Package Price/Year').' ( '.company_setting('defult_currancy_symbol').' )', ['class' => 'form-label']) }}
                        {{ Form::number('package_price_yearly', !empty($plan) ? $plan->package_price_yearly : null, ['class' => 'form-control','required'=>'required','placeholder' => __('Price/Yearly'),'step' => '0.1','min'=>'0']) }}
                    </div>
                    <div class="package-itm px-3">
                        {{ Form::label('price_per_user_monthly', __('Per User Price/Month').' ( '.company_setting('defult_currancy_symbol').' )', ['class' => 'form-label']) }}
                        {{ Form::number('price_per_user_monthly', !empty($plan) ? $plan->price_per_user_monthly : null, ['class' => 'form-control','required'=>'required','placeholder' => __('Enter Price Per User'),'step' => '0.1','min'=>'0']) }}
                    </div>
                    <div class="package-itm px-3">
                        {{ Form::label('price_per_user_yearly', __('Per User Price/Year').' ( '.company_setting('defult_currancy_symbol').' )', ['class' => 'form-label']) }}
                        {{ Form::number('price_per_user_yearly', !empty($plan) ? $plan->price_per_user_yearly : null, ['class' => 'form-control','required'=>'required','placeholder' => __('Enter Price Per User'),'step' => '0.1','min'=>'0']) }}
                    </div>
                    <div class="package-content flex-grow-1  px-3">
                    </div>
                    <div class="price text-end">
                        {{ Form::submit(__('Save'), ['class' => 'btn  btn-primary']) }}
                    </div>
                </div>
            {{ Form::close() }}
        </div>
    </div>
    <!-- [ sample-page ] start -->
    <div class="event-cards row px-0">
        @if (count($modules))
            @foreach ($modules as $module)
                @php
                    $id = strtolower(preg_replace('/\s+/', '_', $module->getName()));
                    $path =$module->getPath().'/module.json';
                    $json = json_decode(file_get_contents($path), true);
                @endphp
                @if (!isset($json['display']) || $json['display'] == true)
                <div class="col-lg-2 col-md-4 col-sm-6 product-card ">
                    <div class="card {{ ($module->isEnabled()) ? 'enable_module' : 'disable_module'}}">
                        <div class="product-img">
                            <div class="theme-avtar">
                                <img src="{{ get_module_img($module->getName()) }}{{'?'.time()}}"
                                    alt="{{ $module->getName() }}" class="img-user"
                                    style="max-width: 100%">
                                </div>
                            <div class="checkbox-custom">
                                <div class="btn-group card-option">
                                    <button type="button" class="btn" data-ajax-popup="true" data-size="md" data-title="{{ __('Customize Logo And Name') }}"  data-url="{{ route('add-one.detail',$module->getName()) }}" data-bs-toggle="tooltip"  data-bs-original-title="{{ __('Module Setting') }}">
                                        <i class="ti ti-adjustments"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-content">
                            <h4 class="text-capitalize"> {{ Module_Alias_Name($module->getName()) }}</h4>
                            <p class="text-muted text-sm mb-0">
                                {{ isset($json['description']) ? $json['description'] : '' }}
                            </p>
                            <div class="price d-flex justify-content-between">
                                <ins><span class="currency-type">{{ super_currency_format_with_sym(ModulePriceByName($module->getName())['monthly_price']) }}</span> <span class="time-lbl text-muted">{{ __('/Month') }}</span></ins>
                                <ins><span class="currency-type">{{ super_currency_format_with_sym(ModulePriceByName($module->getName())['yearly_price']) }}</span> <span class="time-lbl text-muted">{{ __('/Year') }}</span></ins>
                            </div>
                            <a href="{{ route('software.details',Module_Alias_Name($module->getName())) }}" target="_new" class="btn  btn-outline-secondary w-100 mt-2">{{ __('View Details')}}</a>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        @else
        <div class="col-lg-12 col-md-12">
            <div class="card p-5">
                <div class="d-flex justify-content-center">
                    <div class="ms-3 text-center">
                        <h3>{{ __('Add-on Not Available') }}</h3>
                        <p class="text-muted">{{ __('Click ') }}<a
                                href="{{route('module.index') }}">{{ __('here') }}</a>
                            {{ __('To Acctive Add-on') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <!-- [ sample-page ] end -->

</div>
@endsection
@push('scripts')


@endpush
