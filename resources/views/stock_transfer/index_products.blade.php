@extends('layouts.app')
@section('title', __('lang_v1.stock_transfers'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('lang_v1.list_stock_transfer_products')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_stock_transfers')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{action('StockTransferController@create')}}">
                <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="transfare_products">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('lang_v1.batch_number')</th>
                        <th>@lang('product.product_name')</th>
                        <th>@lang('lang_v1.quantity')</th>
                        <th>@lang('lang_v1.location_from')</th>
                        <th>@lang('lang_v1.location_to')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('purchase.additional_notes')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

@include('stock_transfer.partials.update_status_modal')

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop
@section('javascript')
	<script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
@endsection