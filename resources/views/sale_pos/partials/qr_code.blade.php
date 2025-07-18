@if($receipt_details->show_qr_code )
    {{--@php
        $qr_code_text = implode(', ', $receipt_details->qr_code_details);
    @endphp--}}
    {{--<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($qr_code_text, 'QRCODE')}}">--}}
   {{-- <img class="center-block mt-5" style="image-rendering: pixelated;" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_gen, 'QRCODE',3,3)}}">--}}

    <img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_gen, 'QRCODE', 3, 3, [39, 48, 54])}}">

@endif


@if($receipt_details->show_shipping)
    <br><br>
    <?php
        $statuses = [
            ''=>'',
            'ordered' => __('lang_v1.ordered'),
            'packed' => __('lang_v1.packed'),
            'shipped' => __('lang_v1.shipped'),
            'delivered' => __('lang_v1.delivered'),
            'cancelled' => __('restaurant.cancelled')
        ];


        ?>

 <div style="text-align: right">
     <p> عنوان الشحن :
         {{$receipt_details->shipping_address}}
     </p>

         <p> تفاصيل الشحن :
        {{$receipt_details->shipping_details}}
        </p>
    <p>تكلفة الشحن :
        {{$receipt_details->shipping_charges}}
        </p>
    <p> حالة الشحن :
     {{$statuses[$receipt_details->shipping_status]}}
    </p>
     <p>التسليم إلي :
         {{$receipt_details->delivered_to}}</p>
  </div>


@endif