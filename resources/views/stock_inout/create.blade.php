@extends('layouts.app')
@section('title','إذن إضافة')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <br>
        <h1>إذن إضافة</h1>

    </section>

    <!-- Main content -->
    <section class="content no-print">
        {!! Form::open(['url' => action('StockInoutController@store'), 'method' => 'post', 'id' => 'stock_inout_form' ]) !!}
        <div class="box box-solid">
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                           {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                         </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('ref_no', __('purchase.ref_no').':') !!}
                            {!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                            <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                                {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']); !!}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div> <!--box end-->
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2">
                        <div class="form-group">
                            <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-search"></i>
							</span>
                                {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_srock_adjustment', 'placeholder' => __('stock_adjustment.search_product'), 'disabled']); !!}
                            </div>
                        </div>
                    </div>
                </div>

                        <input type="hidden" id="product_row_index" value="0">
                        <input type="hidden" id="total_amount" name="final_total" value="0">
                        <div class="table-responsive">
                            <table class="table table-bordered "
                                   id="stock_adjustment_product_table">
                                <thead>
                                <tr>
                                    <th class="col-sm-4 text-center">
                                        @lang('sale.product')
                                    </th>
                                   {{-- <th style="width: 90px" >الرقم البحري</th>
                                    <th style="width: 90px">باتش نمبر</th>
                                    <th>تاريخ الإنتاج</th>
                                    <th style="width: 100px">عدد الوحدات الموجودة البالتة</th>
--}}
                                    <th class="text-center" style="width: 150px">
                                      مكان التخزين
                                    </th>
                                    <th  style="width: 90px" class="text-center">
                                        @lang('sale.qty')
                                    </th>
                                    <th class="text-center" style="width: 50px">
                                        <i class="fa fa-times" aria-hidden="true"></i></th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

            </div>
        </div> <!--box end-->
        <div class="box box-solid ">
            <div class="box-body">
                <div class="row hidden">
                    <div class="col-sm-4  ">
                        <div class="form-group">
                            {!! Form::label('total_amount_recovered', __('stock_adjustment.total_amount_recovered') . ':') !!}
                            @show_tooltip(__('tooltip.total_amount_recovered'))
                            {!! Form::text('total_amount_recovered', 0, ['class' => 'form-control input_number', 'placeholder' => __('stock_adjustment.total_amount_recovered')]); !!}
                        </div>
                    </div>
                    <div class="col-sm-4  ">
                        <div class="form-group">
                            {!! Form::label('additional_notes', __('stock_adjustment.reason_for_stock_adjustment') . ':') !!}
                            {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'placeholder' => __('stock_adjustment.reason_for_stock_adjustment'), 'rows' => 3]); !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
                    </div>
                </div>

            </div>
        </div> <!--box end-->
        {!! Form::close() !!}
    </section>
@stop
@section('javascript')
    <script src="{{ asset('js/stock_inout.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        __page_leave_confirmation('#stock_adjustment_form');
    </script>
@endsection
