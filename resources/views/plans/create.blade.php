{{ Form::open(['url' => 'plans', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    <div class="form-group mb-1">
        {{ Form::label('name', __('Name'), ['class' => 'col-form-label']) }}
        {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Permission Name')]) }}
        @error('name')
            <span class="invalid-name" role="alert">
                <strong class="text-danger">{{ $message }}</strong>
            </span>
        @enderror
    </div>
    <div class="form-group mb-1">
        {{ Form::label('price', __('Price'), ['class' => 'col-form-label']) }}
        {{ Form::text('price', null, ['class' => 'form-control', 'placeholder' => __('Enter Price')]) }}
        @error('price')
            <span class="invalid-price" role="alert">
                <strong class="text-danger">{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group mb-1">
        {{ Form::label('duration', __('Duration'), ['class' => 'col-form-label']) }}
        {!! Form::select('duration', $duretion, null, [
            'class' => 'form-control multi-select',
            'required' => 'required',
        ]) !!}
        @error('duration')
            <span class="invalid-duration" role="alert">
                <strong class="text-danger">{{ $message }}</strong>
            </span>
        @enderror
    </div>
    @foreach (getPlanField() as $plan)
        @php
            $string = $string = str_replace('_', ' ', $plan);
        @endphp
        <div class="form-group mb-1">
            {{ Form::label($plan, __(ucwords($string)), ['class' => 'col-form-label']) }}
            {{ Form::text('plan_files[' . $plan . ']', null, ['class' => 'form-control', 'placeholder' => __('Enter ' . ucwords($string))]) }}
            @error($plan)
                <span class="invalid-duration" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
    @endforeach
</div>

<div class="modal-footer">
    <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {{ Form::submit(__('Create'), ['class' => 'btn  btn-primary']) }}
</div>
{{ Form::close() }}
