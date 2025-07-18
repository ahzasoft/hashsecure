<!-- business information here -->

@include('sale_pos.receipts.invoice_style')

<div class="invoice-container">

    <div class="row">

        <!-- Logo -->
        @if(!empty($receipt_details->logo))
            <img style="max-height: 120px; width: auto;" src="{{$receipt_details->logo}}"
                 class="img img-responsive center-block">
        @endif

        <!-- Header text -->
        @if(!empty($receipt_details->header_text))
            <div class="col-xs-12">
                {!! $receipt_details->header_text !!}
            </div>
        @endif

        <!-- business information here -->
        <div class="col-xs-12 text-center">
            <h2 class="text-center">
                <!-- Shop & Location Name  -->
                @if(!empty($receipt_details->display_name))
                    {{$receipt_details->display_name}}
                @endif
            </h2>

            <!-- Address -->
            <p>
               {{-- @if(!empty($receipt_details->address))
                    <small class="text-center">
                        {!! $receipt_details->address !!}
                    </small>
                @endif--}}
                @if(!empty($receipt_details->contact))
                    <br/>{!! $receipt_details->contact !!}
                @endif
                {{--@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
                    ,
                @endif
                @if(!empty($receipt_details->website))
                    {{ $receipt_details->website }}
                @endif--}}

                @if(!empty($receipt_details->location_custom_fields))
                    <br>{{ $receipt_details->location_custom_fields }}
                @endif
            </p>
            <p>
                @if(!empty($receipt_details->sub_heading_line1))
                    {{ $receipt_details->sub_heading_line1 }}
                @endif
                @if(!empty($receipt_details->sub_heading_line2))
                    <br>{{ $receipt_details->sub_heading_line2 }}
                @endif
                @if(!empty($receipt_details->sub_heading_line3))
                    <br>{{ $receipt_details->sub_heading_line3 }}
                @endif
                @if(!empty($receipt_details->sub_heading_line4))
                    <br>{{ $receipt_details->sub_heading_line4 }}
                @endif
                @if(!empty($receipt_details->sub_heading_line5))
                    <br>{{ $receipt_details->sub_heading_line5 }}
                @endif
            </p>
            <p>
                @if(!empty($receipt_details->tax_info1))
                    <b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
                @endif

                @if(!empty($receipt_details->tax_info2))
                    <b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
                @endif
            </p>

            <!-- Title of receipt -->
            @if(!empty($receipt_details->invoice_heading))
                <h3 class="text-center">
                    {!! $receipt_details->invoice_heading !!}
                </h3>
            @endif

            <!-- Invoice  number, Date  -->
            <p style="width: 100% !important" class="word-wrap">
			<span class="pull-left text-left word-wrap">
				@if(!empty($receipt_details->invoice_no_prefix))
                    <b>{!! $receipt_details->invoice_no_prefix !!}</b>
                @endif
                {{$receipt_details->invoice_no}}

                @if(!empty($receipt_details->types_of_service))
                    <br/>
                    <span class="pull-left text-left">
						<strong>{!! $receipt_details->types_of_service_label !!}:</strong>
						{{$receipt_details->types_of_service}}
                        <!-- Waiter info -->
                        @if(!empty($receipt_details->types_of_service_custom_fields))
                            @foreach($receipt_details->types_of_service_custom_fields as $key => $value)
                                <br><strong>{{$key}}: </strong> {{$value}}
                            @endforeach
                        @endif
					</span>
                @endif

                <!-- Table information-->
                @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
                    <br/>
                    <span class="pull-left text-left">
						@if(!empty($receipt_details->table_label))
                            <b>{!! $receipt_details->table_label !!}</b>
                        @endif
                        {{$receipt_details->table}}

                        <!-- Waiter info -->
					</span>
                @endif

                <!-- customer info -->

                @if($receipt_details->show_customer==1)

                    @if(!empty( $receipt_details->customer_label))
                        <div class="textbox-info">
                  				<p class="f-right"><strong>
										{{ $receipt_details->customer_label }}
									</strong></p>
								<p class="f-right">
									{!! $receipt_details->customer_name !!}
								</p>
			               </div>
                    @endif
                        @if(!empty($receipt_details->customer_mobile))
                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                                    رقم الجوال :  {{ $receipt_details->customer_mobile }}

                                    </strong></p>
                            </div>
                        @endif

                        @if(!empty($receipt_details->address_line_1))
                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                                     العنوان:  {{ $receipt_details->address_line_1 }}  {{ $receipt_details->address_line_2 }}

                                    </strong></p>
                            </div>
                        @endif
                    @if(!empty($receipt_details->client_id_label))
                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                                    {{ $receipt_details->client_id_label }} {{ $receipt_details->client_id }}

                                    </strong></p>
                            </div>
                    @endif
                    @if(!empty($receipt_details->customer_tax_label))
                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                                          <b>{{ $receipt_details->customer_tax_label }}</b> {{ $receipt_details->customer_tax_number }}

                                    </strong></p>
                            </div>
                    @endif
                    @if(!empty($receipt_details->customer_custom_fields))
                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                        <br/>{!! $receipt_details->customer_custom_fields !!}

                            </strong></p>
                       </div>
                    @endif
                    @if(!empty($receipt_details->sales_person_label))
                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                        <b>{{ $receipt_details->sales_person_label }}</b> {{ $receipt_details->sales_person }}
                                         </strong></p>
                            </div>
                    @endif
                    @if(!empty($receipt_details->commission_agent_label))
                                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                        <strong>{{ $receipt_details->commission_agent_label }}</strong> {{ $receipt_details->commission_agent }}
                                         </strong></p>
                            </div>
                    @endif
                    @if(!empty($receipt_details->customer_rp_label))
                                            <div class="textbox-info">
                  				<p class="f-right"><strong>
                        <strong>{{ $receipt_details->customer_rp_label }}</strong> {{ $receipt_details->customer_total_rp }}
                         </strong></p>
                            </div>
                    @endif
			</span>
                @endif
            @if(!empty($receipt_details->date_label))
                <div class="textbox-info">
                    <p class="f-right"><strong>
				             <b>{{$receipt_details->date_label}}</b> {{$receipt_details->invoice_date}}
                        </strong></p>
                </div>
            @endif

                    @if(!empty($receipt_details->due_date_label))
                <div class="textbox-info">
                    <p class="f-right"><strong>
                        <br><b>{{$receipt_details->due_date_label}}</b> {{$receipt_details->due_date ?? ''}}
                        </strong></p>
                </div>
                    @endif

                    @if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
                        <br>
                        @if(!empty($receipt_details->brand_label))
                            <b>{!! $receipt_details->brand_label !!}</b>
                        @endif
                        {{$receipt_details->repair_brand}}
                    @endif


                    @if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
                        <br>
                        @if(!empty($receipt_details->device_label))
                            <b>{!! $receipt_details->device_label !!}</b>
                        @endif
                        {{$receipt_details->repair_device}}
                    @endif

                    @if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
                        <br>
                        @if(!empty($receipt_details->model_no_label))
                            <b>{!! $receipt_details->model_no_label !!}</b>
                        @endif
                        {{$receipt_details->repair_model_no}}
                    @endif

                    @if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
                        <br>
                        @if(!empty($receipt_details->serial_no_label))
                            <b>{!! $receipt_details->serial_no_label !!}</b>
                        @endif
                        {{$receipt_details->repair_serial_no}}<br>
                    @endif
                    @if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
                        @if(!empty($receipt_details->repair_status_label))
                            <b>{!! $receipt_details->repair_status_label !!}</b>
                        @endif
                        {{$receipt_details->repair_status}}<br>
                    @endif

                    @if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
                        @if(!empty($receipt_details->repair_warranty_label))
                            <b>{!! $receipt_details->repair_warranty_label !!}</b>
                        @endif
                        {{$receipt_details->repair_warranty}}
                        <br>
                    @endif

                    <!-- Waiter info -->
                    @if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
                        <br/>
                        @if(!empty($receipt_details->service_staff_label))
                            <b>{!! $receipt_details->service_staff_label !!}</b>
                        @endif
                        {{$receipt_details->service_staff}}
                    @endif
                    @if(!empty($receipt_details->shipping_custom_field_1_label))
                        <br>
                        <strong>{!!$receipt_details->shipping_custom_field_1_label!!} :</strong> {!!$receipt_details->shipping_custom_field_1_value ?? ''!!}
                    @endif

                    @if(!empty($receipt_details->shipping_custom_field_2_label))
                        <br>
                        <strong>{!!$receipt_details->shipping_custom_field_2_label!!}:</strong> {!!$receipt_details->shipping_custom_field_2_value ?? ''!!}
                    @endif

                    @if(!empty($receipt_details->shipping_custom_field_3_label))
                        <br>
                        <strong>{!!$receipt_details->shipping_custom_field_3_label!!}:</strong> {!!$receipt_details->shipping_custom_field_3_value ?? ''!!}
                    @endif

                    @if(!empty($receipt_details->shipping_custom_field_4_label))
                        <br>
                        <strong>{!!$receipt_details->shipping_custom_field_4_label!!}:</strong> {!!$receipt_details->shipping_custom_field_4_value ?? ''!!}
                    @endif

                    @if(!empty($receipt_details->shipping_custom_field_5_label))
                        <br>
                        <strong>{!!$receipt_details->shipping_custom_field_2_label!!}:</strong> {!!$receipt_details->shipping_custom_field_5_value ?? ''!!}
                    @endif
                    {{-- sale order --}}
                    @if(!empty($receipt_details->sale_orders_invoice_no))
                        <br>
                        <strong>@lang('restaurant.order_no'):</strong> {!!$receipt_details->sale_orders_invoice_no ?? ''!!}
                    @endif

                    @if(!empty($receipt_details->sale_orders_invoice_date))
                        <br>
                        <strong>@lang('lang_v1.order_dates'):</strong> {!!$receipt_details->sale_orders_invoice_date ?? ''!!}
                    @endif
			</span>
            </p>
        </div>
    </div>

    <div class="row">
        @includeIf('sale_pos.receipts.partial.common_repair_invoice')
    </div>

    <div class="row">
        <div class="col-xs-12">
            <br/>
            @php
                $p_width = 40;
            @endphp
            @if(!empty($receipt_details->item_discount_label))
                @php
                    $p_width -= 15;
                @endphp
            @endif

            {{--Table of products--}}
            <table class="table table-responsive table-slim">
                <thead>
                <tr>
                    <th width="{{$p_width}}%">{{$receipt_details->table_product_label}}</th>
                    <th class="text-right" width="15%">{{$receipt_details->table_qty_label}}</th>
                    <th class="text-right" width="15%">{{$receipt_details->table_unit_price_label}}</th>
                    @if(!empty($receipt_details->item_discount_label))
                        <th class="text-right" width="15%">{{$receipt_details->item_discount_label}}</th>
                    @endif
                    <th class="text-right" width="15%">{{$receipt_details->table_subtotal_label}}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($receipt_details->lines as $line)
                    <tr>
                        <td>
                            @if(!empty($line['image']))
                                <img src="{{$line['image']}}" alt="Image" width="50"
                                     style="float: left; margin-right: 8px;">
                            @endif
                            {{$line['name']}} {{$line['product_variation']}} {{$line['variation']}}
                            @if(!empty($line['sub_sku']))
                                , {{$line['sub_sku']}}
                            @endif @if(!empty($line['brand']))
                                , {{$line['brand']}}
                            @endif @if(!empty($line['cat_code']))
                                , {{$line['cat_code']}}
                            @endif
                            @if(!empty($line['product_custom_fields']))
                                , {{$line['product_custom_fields']}}
                            @endif
                            @if(!empty($line['sell_line_note']))
                                <br>
                                <small>
                                    {{$line['sell_line_note']}}
                                </small>
                            @endif
                            @if(!empty($line['lot_number']))
                                <br> {{$line['lot_number_label']}}:  {{$line['lot_number']}}
                            @endif
                            @if(!empty($line['product_expiry']))
                                , {{$line['product_expiry_label']}}:  {{$line['product_expiry']}}
                            @endif

                            @if(!empty($line['warranty_name']))
                                <br><small>{{$line['warranty_name']}} </small>
                            @endif @if(!empty($line['warranty_exp_date']))
                                <small>- {{@format_date($line['warranty_exp_date'])}} </small>
                            @endif
                            @if(!empty($line['warranty_description']))
                                <small> {{$line['warranty_description'] ?? ''}}</small>
                            @endif
                        </td>
                        <td class="text-right">{{$line['quantity']}}{{$line['units']}} </td>
                        <td class="text-right">{{$line['unit_price_inc_tax']}}</td>
                        @if(!empty($receipt_details->item_discount_label))
                            <td class="text-right">
                                {{$line['line_discount'] ?? '0.00'}}
                            </td>
                        @endif
                        <td class="text-right">{{$line['line_total']}}</td>
                    </tr>
                    @if(!empty($line['modifiers']))
                        @foreach($line['modifiers'] as $modifier)
                            <tr>
                                <td colspan="5">
                                    {{$modifier['name']}} {{$modifier['variation']}}
                                    @if(!empty($modifier['sub_sku']))
                                        , {{$modifier['sub_sku']}}
                                    @endif @if(!empty($modifier['cat_code']))
                                        , {{$modifier['cat_code']}}
                                    @endif
                                    @if(!empty($modifier['sell_line_note']))
                                        ({{$modifier['sell_line_note']}})
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    @endif

                    @if(empty($receipt_details->item_discount_label) && $line['line_discount']>0)
                        <tr>
                            <td>
                                @lang('sale.discount'):{{$line['line_discount'] }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>

                @endforelse


                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <hr/>
        </div>


        <div class="col-xs-12 col-md-6">
            <div class="table-responsive">
                <table class="table table-slim">
                    <tbody>
                    @if(!empty($receipt_details->total_quantity_label))
                        <tr class="color-555">
                            <th>
                                {!! $receipt_details->total_quantity_label !!}
                            </th>
                            <td class="text-right">
                                {{$receipt_details->total_quantity}}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <th>
                            {!! $receipt_details->subtotal_label !!}
                        </th>
                        <td class="text-right">
                            {{$receipt_details->subtotal}}
                        </td>
                    </tr>
                    @if(!empty($receipt_details->total_exempt_uf))
                        <tr>
                            <th>
                                @lang('lang_v1.exempt')
                            </th>
                            <td class="text-right">
                                {{$receipt_details->total_exempt}}
                            </td>
                        </tr>
                    @endif
                    <!-- Shipping Charges -->
                    @if(!empty($receipt_details->shipping_charges))
                        <tr>
                            <th>
                                {!! $receipt_details->shipping_charges_label !!}
                            </th>
                            <td class="text-right">
                                {{$receipt_details->shipping_charges}}
                            </td>
                        </tr>
                    @endif

                    @if(!empty($receipt_details->packing_charge))
                        <tr>
                            <th>
                                {!! $receipt_details->packing_charge_label !!}
                            </th>
                            <td class="text-right">
                                {{$receipt_details->packing_charge}}
                            </td>
                        </tr>
                    @endif

                    <!-- Discount -->
                    @if( !empty($receipt_details->discount)  )
                        <tr>
                            <th>
                                {!! $receipt_details->discount_label !!}
                            </th>

                            <td class="text-right">
                                (-) {{$receipt_details->total_discount}}
                            </td>
                        </tr>
                    @endif

                    <!-- Tax -->
                    @if( !empty($receipt_details->tax_label) )
                        <tr>
                            <th>
                                {!! $receipt_details->tax_label !!}
                            </th>
                            <td class="text-right">
                                (+) {{$receipt_details->tax}}
                            </td>
                        </tr>
                    @endif

                    @if( !empty($receipt_details->reward_point_label)  )
                        <tr>
                            <th>
                                {!! $receipt_details->reward_point_label !!}
                            </th>

                            <td class="text-right">
                                (-) {{$receipt_details->reward_point_amount}}
                            </td>
                        </tr>
                    @endif

                    @if( !empty($receipt_details->transaction_add) )
                        <tr>
                            <th>
                                {!! $receipt_details->transaction_add !!}
                            </th>

                            <td class="text-right">
                                {{$receipt_details->transaction_add_value}}
                            </td>
                        </tr>

                        {{--<tr>
                            <th>
                                صافي قيمة المستخلص الحالي
                            </th>

                            <td class="text-right">
                                {{ $receipt_details->transaction_add_total}}
                            </td>
                        </tr>--}}

                    @endif




                    @if( $receipt_details->round_off_amount > 0)
                        <tr>
                            <th>
                                {!! $receipt_details->round_off_label !!}
                            </th>
                            <td class="text-right">
                                {{$receipt_details->round_off}}
                            </td>
                        </tr>
                    @endif

                    <!-- Total -->
                    <tr>
                        <th>
                            {!! $receipt_details->total_label !!}
                        </th>
                        <td class="text-right">
                            {{$receipt_details->transaction_add_total}}
                            @if(!empty($receipt_details->total_in_words))
                                <br>
                                <small>({{$receipt_details->total_in_words}})</small>
                            @endif
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-xs-12 col-md-6">
            <table class="table table-slim">
                @if(!empty($receipt_details->payments))
                    @foreach($receipt_details->payments as $payment)
                        <tr>
                            <td>{{$payment['method']}}</td>
                            <td class="text-right">{{$payment['amount']}}</td>
                            <td class="text-right">{{$payment['date']}}</td>
                        </tr>
                    @endforeach
                @endif

                <!-- Total Paid-->
                @if(!empty($receipt_details->total_paid_label))
                    <tr>
                        <th>
                            {!! $receipt_details->total_paid_label !!}
                        </th>
                        <td class="text-right">
                            {{$receipt_details->total_paid}}
                        </td>
                    </tr>
                @endif

                <!-- Total Due-->
                @if(!empty($receipt_details->total_due_label))
                    <tr>
                        <th>
                            {!! $receipt_details->total_due_label !!}
                        </th>
                        <td class="text-right">
                            {{$receipt_details->total_due}}
                        </td>
                    </tr>
                @endif

                @if(!empty($receipt_details->all_due))
                    <tr>
                        <th>
                            {!! $receipt_details->all_bal_label !!}
                        </th>
                        <td class="text-right">
                            {{$receipt_details->all_due}}
                        </td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="col-xs-12">
            <p>{!! nl2br($receipt_details->additional_notes) !!}</p>
        </div>


    </div>

    @if($receipt_details->show_barcode )
        <div class="@if(!empty($receipt_details->footer_text)) col-xs-4 @else col-xs-12 @endif text-center">
            @if($receipt_details->show_barcode)
                {{-- Barcode --}}
                <img class="center-block"
                     src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
            @endif

        </div>
    @endif

    @include('sale_pos.partials.qr_code')

    <br>
    <div class="row">
        @if(!empty($receipt_details->footer_text))
            <div class="col-xs-12 ">
                {!! $receipt_details->footer_text !!}
            </div>
        @endif

    </div>
</div>