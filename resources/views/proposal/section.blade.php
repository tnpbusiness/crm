<script>
    var selector = "body";
    if ($(selector + " .repeater").length) {
        var $dragAndDrop = $("body .repeater tbody").sortable({
            handle: '.sort-handler'
        });
        var $repeater = $(selector + ' .repeater').repeater({
            initEmpty: false,
            defaultValues: {
                'status': 1
            },
            show: function() {
                $(this).slideDown();
                var file_uploads = $(this).find('input.multi');
                if (file_uploads.length) {
                    $(this).find('input.multi').MultiFile({
                        max: 3,
                        accept: 'png|jpg|jpeg',
                        max_size: 2048
                    });
                }
                 // for item SearchBox ( this function is  custom Js )
                 JsSearchBox();
            },
            hide: function(deleteElement) {
                if (confirm('Are you sure you want to delete this element?')) {
                    $(this).slideUp(deleteElement);
                    $(this).remove();

                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }
                    $('.subTotal').html(subTotal.toFixed(2));
                    $('.totalAmount').html(subTotal.toFixed(2));
                }
            },
            ready: function(setIndexes) {
                $dragAndDrop.on('drop', setIndexes);
            },
            isFirstItemUndeletable: true
        });
        var value = $(selector + " .repeater").attr('data-value');
        if (typeof value != 'undefined' && value.length != 0) {
            value = JSON.parse(value);
            $repeater.setList(value);
        }
    }
</script>
@if ($acction == 'edit')
    <script>
        $(document).ready(function() {
            $("#customer").trigger('change');
            var value = $(selector + " .repeater").attr('data-value');
            var type = '{{ $type }}';
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
                for (var i = 0; i < value.length; i++) {
                    var tr = $('#sortable-table .id[value="' + value[i].id + '"]').parent();
                    tr.find('.item').val(value[i].product_id);
                    if (type == 'product') {
                        changeItem(tr.find('.item'));
                    }
                }
            }
        });
    </script>
    @if ($type == 'product')
        <script>
            var proposal_id = '{{ $proposal->id }}';
            function changeItem(element) {
                var iteams_id = element.val();

                var url = element.data('url');
                var el = element;
                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': jQuery('#token').val()
                    },
                    data: {
                        'product_id': iteams_id
                    },
                    cache: false,
                    success: function(data) {
                        var item = JSON.parse(data);

                        $.ajax({
                            url: '{{ route('proposal.items') }}',
                            type: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': jQuery('#token').val()
                            },
                            data: {

                                'proposal_id': proposal_id,
                                'product_id': iteams_id,
                            },
                            cache: false,
                            success: function(data) {
                                var proposalItems = JSON.parse(data);
                                if (proposalItems != null) {
                                    var amount = (proposalItems.price * proposalItems.quantity);

                                    $(el.parent().parent().find('.quantity')).val(proposalItems
                                        .quantity);
                                    $(el.parent().parent().find('.price')).val(proposalItems.price);
                                    $(el.parent().parent().find('.discount')).val(proposalItems
                                        .discount);
                                } else {
                                    $(el.parent().parent().find('.quantity')).val(1);
                                    $(el.parent().parent().find('.price')).val(item.product.sale_price);
                                    $(el.parent().parent().find('.discount')).val(0);
                                }

                                var taxes = '';
                                var tax = [];

                                var totalItemTaxRate = 0;
                                if (item.taxes == 0) {
                                    taxes += '-';
                                } else {
                                    for (var i = 0; i < item.taxes.length; i++) {
                                        taxes += '<span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">' +
                                            item.taxes[i].name + ' ' + '(' + item.taxes[i].rate + '%)' +
                                            '</span>';
                                        tax.push(item.taxes[i].id);
                                        totalItemTaxRate += parseFloat(item.taxes[i].rate);
                                    }
                                }

                                $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                                $(el.parent().parent().find('.taxes')).html(taxes);
                                $(el.parent().parent().find('.tax')).val(tax);
                                $(el.parent().parent().find('.unit')).html(item.unit);

                                $(".discount").trigger('change');
                            }
                        });
                    },
                });
            }
        </script>
    @elseif($type == 'project')
        <script>
            $(document).ready(function() {
                $("#tax_project").trigger('change');
                $(".discount").trigger('change');
            });
        </script>
    @endif
@endif
<script>
      $(document).on('click', '[data-repeater-create]', function() {
        $('.item :selected').each(function() {
            var id = $(this).val();
            if(id != '')
            {
                $(".item option[value=" + id + "]").addClass("d-none");
            }
        });
    })

    $(".tax_get").click(function() {
        myFunction();

    });
    $(".get_tax").change(function() {
        myFunction();
    });

    function myFunction() {
        var tax_id = $('.get_tax').val();


        if (tax_id != "") {
            $.ajax({
                url: '{{ route('get_tax') }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'tax_id': tax_id,
                },
                cache: false,
                success: function(data) {
                    var obj = jQuery.parseJSON(data);


                    var taxes = '';
                    var tax = [];
                    $.each(obj, function() {

                        taxes += '<span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">' +
                            this.name + ' ' + '(' + this.rate + '%)' +
                            '</span>';
                        tax.push(this.id);

                    });

                    $('.taxes').html(taxes);
                },
            });
        } else {
            $('.taxes').html("");
        }
    }
</script>

@if ($type == 'product')
    <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Product & Services') }}</h5>
    <div class="card repeater" @if ($acction == 'edit') data-value='{!! json_encode($proposal->items) !!}' @endif>
        <div class="item-section py-4">
            <div class="row justify-content-between align-items-center">
                <div class="col-md-12 d-flex align-items-center justify-content-md-end px-5">
                    <div class="all-button-box ">
                        <a href="#" data-repeater-create="" class="btn btn-primary mr-2" data-toggle="modal"
                            data-target="#add-bank">
                            <i class="ti ti-plus"></i> {{ __('Add item') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body mt-3">
            <div class="table-responsive">
                <table class="table mb-0" data-repeater-list="items" id="sortable-table">
                    <thead>
                        <tr>
                            <th>{{ __('Items') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Price') }} </th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Tax') }} (%)</th>
                            <th class="text-end">{{ __('Amount') }} <br>
                                <small class="text-danger font-weight-bold">{{ __('After discount & tax') }}</small>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="ui-sortable" data-repeater-item>
                        <tr>
                            {{ Form::hidden('id', null, ['class' => 'form-control id']) }}
                            <td width="25%" class="form-group pt-0">
                                {{ Form::select('item', $product_services, null, ['class' => 'form-control item js-searchBox', 'data-url' => route('proposal.product'), 'required' => 'required', 'placeholder' => 'Select Item']) }}
                                @if (empty($product_services_count))
                                    <div class=" text-xs">{{ __('Please create Product first.') }}<a
                                            href="{{ route('product-service.index') }}"><b>{{ __('Add Product') }}</b></a>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="form-group price-input input-group search-form">
                                    {{ Form::text('quantity', '', ['class' => 'form-control quantity', 'required' => 'required', 'placeholder' => __('Qty'), 'required' => 'required']) }}
                                    <span class="unit input-group-text bg-transparent"></span>
                                </div>
                            </td>
                            <td>
                                <div class="form-group price-input input-group search-form">
                                    {{ Form::text('price', '', ['class' => 'form-control price', 'required' => 'required', 'placeholder' => __('Price'), 'required' => 'required']) }}
                                    <span
                                        class="input-group-text bg-transparent">{{ company_setting('defult_currancy_symbol') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="form-group price-input input-group search-form">
                                    {{ Form::text('discount', '', ['class' => 'form-control discount', 'required' => 'required', 'placeholder' => __('Discount')]) }}
                                    <span
                                        class="input-group-text bg-transparent">{{ company_setting('defult_currancy_symbol') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="taxes "></div>
                                        {{ Form::hidden('tax', null, ['class' => 'form-control tax']) }}
                                        {{ Form::hidden('itemTaxPrice', '', ['class' => 'form-control itemTaxPrice']) }}
                                        {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate']) }}
                                    </div>
                                </div>
                            </td>
                            <td class="text-end amount">
                                0.00
                            </td>
                            <td>
                                <a href="#" class="bs-pass-para repeater-action-btn" data-repeater-delete>
                                    <div class="repeater-action-btn action-btn bg-danger ms-2">
                                        <i class="ti ti-trash text-white text-white"></i>
                                    </div>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="form-group">
                                    {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => '1', 'placeholder' => __('Description')]) }}
                                </div>
                            </td>
                            <td colspan="5"></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-none">
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td></td>
                            <td><strong>{{ __('Sub Total') }}
                                    ({{ company_setting('defult_currancy_symbol') }})</strong></td>
                            <td class="text-end subTotal">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td></td>
                            <td><strong>{{ __('Discount') }}
                                    ({{ company_setting('defult_currancy_symbol') }})</strong></td>
                            <td class="text-end totalDiscount">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td></td>
                            <td><strong>{{ __('Tax') }} ({{ company_setting('defult_currancy_symbol') }})</strong>
                            </td>
                            <td class="text-end totalTax">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td class="blue-text border-none"><strong>{{ __('Total Amount') }}
                                    ({{ company_setting('defult_currancy_symbol') }})</strong></td>
                            <td class="text-end totalAmount blue-text border-none">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@elseif ($type == 'project')
    <h5 class="h4 d-inline-block font-weight-400 mb-4 pro_name">{{ __('Project') }}</h5>
    {{ Form::hidden('itemTaxRate', null, ['class' => 'form-control itemTaxRate']) }}
    <div class="card repeater" @if ($acction == 'edit') data-value='{!! json_encode($proposal->items) !!}' @endif>
        <div class="item-section py-4">
            <div class="row justify-content-between align-items-center">
                <div class="col-md-12 d-flex align-items-center justify-content-md-end px-5 ">
                    <a href="#" data-repeater-create="" class="btn btn-primary mr-2 tax_get  " data-toggle="modal"
                        data-target="#add-bank">
                        <i class="ti ti-plus"></i> {{ __('Add item') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body table-border-style mt-2">
            <div class="table-responsive">
                <table class="table  mb-0 table-custom-style" data-repeater-list="items" id="sortable-table">
                    <thead>
                        <tr>
                            <th>{{ __('Items') }}</th>
                            <th>{{ __('Price') }} </th>
                            <th>{{ __('Discount') }}</th>
                            <th width="200px">{{ __('Tax') }} (%)</th>
                            <th class="text-end" width="200px">{{ __('Amount') }} <br><small
                                    class="text-danger font-weight-bold">{{ __('After tax & discount') }}</small></th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody class="ui-sortable" data-repeater-item>
                        <tr>
                            <td width="25%" class="form-group pt-0">
                                {{ Form::hidden('id', null, ['class' => 'form-control id']) }}
                                {{ Form::select('item', $tasks, null, ['class' => 'form-control item js-searchBox', 'required' => 'required']) }}
                            </td>
                            <td>
                                <div class="form-group price-input input-group search-form">
                                    {{ Form::text('price', '', ['class' => 'form-control price', 'required' => 'required', 'placeholder' => __('Price'), 'required' => 'required']) }}
                                    <span
                                        class="input-group-text bg-transparent">{{ company_setting('defult_currancy_symbol') }}</span>
                                </div>
                            </td>
                            {{ Form::hidden('quantity',1, ['class' => 'form-control quantity', 'required' => 'required', 'placeholder' => __('Qty'), 'required' => 'required']) }}
                            <td>
                                <div class="form-group price-input input-group search-form">
                                    {{ Form::text('discount', '', ['class' => 'form-control discount', 'placeholder' => __('Discount')]) }}
                                    <span
                                        class="input-group-text bg-transparent">{{ company_setting('defult_currancy_symbol') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="taxes"></div>
                                        {{ Form::hidden('tax', null, ['class' => 'form-control tax']) }}
                                        {{ Form::hidden('itemTaxPrice', '', ['class' => 'form-control itemTaxPrice']) }}
                                        {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate']) }}
                                    </div>
                                </div>
                            </td>
                            <td class="text-end amount">0.00</td>
                            <td>
                                <a href="#" class="bs-pass-para repeater-action-btn" data-repeater-delete>
                                    <div class="repeater-action-btn action-btn bg-danger ms-2">
                                        <i class="ti ti-trash text-white text-white"></i>
                                    </div>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="form-group">
                                    {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => '2', 'placeholder' => __('Description')]) }}
                                </div>
                            </td>
                            <td colspan="5"></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td></td>
                            <td><strong>{{ __('Sub Total') }}
                                    ({{ company_setting('defult_currancy_symbol') }})</strong>
                            </td>
                            <td class="text-end subTotal">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td></td>
                            <td><strong>{{ __('Discount') }}
                                    ({{ company_setting('defult_currancy_symbol') }})</strong>
                            </td>
                            <td class="text-end totalDiscount">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td></td>
                            <td><strong>{{ __('Tax') }} ({{ company_setting('defult_currancy_symbol') }})</strong>
                            </td>
                            <td class="text-end totalTax">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td class="blue-text"><strong>{{ __('Total Amount') }}
                                    ({{ company_setting('defult_currancy_symbol') }})</strong></td>
                            <td class="text-end totalAmount blue-text"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endif
