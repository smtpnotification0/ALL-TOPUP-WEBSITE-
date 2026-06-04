@extends('layout.master')
@section('title')
    {{ __('My Orders') }} {{ __('-') }} {{ $settings->site_title }}
@endsection
@section('content')
<section class="orders">
  <div class="mx-auto container mx-auto text-center m-4">
    <div class="pxa-4 md:px-0">
      <div class="bg-white border rounded-lg overflow-hidden mx-auto mr-2">
        <div class="text-left px-3 flex items-center justify-between">
          <div class="flex items-center">
            <svg viewBox="0 0 24 24" class="mr-2" style="width: 24px; height: 24px">
              <path fill="currentColor" d="M11 15H17V17H11V15M9 7H7V9H9V7M11 13H17V11H11V13M11 9H17V7H11V9M9 11H7V13H9V11M21 5V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H19C20.1 3 21 3.9 21 5M19 5H5V19H19V5M9 15H7V17H9V15Z"></path>
            </svg>
            <h2 class="text-lg text-black py-2 font-normal fb"> My Orders</h2>
          </div>
        </div>
          <hr>
          @forelse ($orders as $order)
        <div class="orders-list border-b-2 m-2">
          <div class="sm:flex">
            <div class="w-full sm:w-1/2">
              <p class="px-3 py-1 text-left">
                <span class="font-bold">{{ __('Order ID') }}: </span> {{ $order->id }}
              </p>
              <p class="px-3 py-1 text-left">
                <span class="font-bold">{{ __('Date') }}: </span> {{ custom_date($order) }}
              </p>
              <p class="px-3 py-1 text-left">
                <span class="font-bold">{{ __('Package') }}: </span> 
                    @if (!empty($order->variation->title))
                        {{ $order->variation->title }}
                    @endif
              </p>
            </div>
            <div class="w-full sm:w-1/2">
              
                @if (!empty($order->account_info))         
                    @php
                    $accountInfo = json_decode(json_encode($order->account_info), true);
                    @endphp
                    @if(isset($accountInfo['account_type']))
                     <p class="px-3 py-1 text-left"><span class="font-bold">Account Type: </span> {{ $accountInfo['account_type'] }}</p>
                     <p class="px-3 py-1 text-left"><span class="font-bold">Game Account: </span> {{ $accountInfo['game_account'] }}</p>
                    @endif
                    @if(isset($accountInfo['player_id']))
                     <p class="px-3 py-1 text-left"><span class="font-bold">Player ID: </span> {{ $accountInfo['player_id'] }}</p>
                    @endif
                    @if(isset($accountInfo['subscription_details']))
                     <p class="px-3 py-1 text-left"><span class="font-bold">Subscription Details: </span> {{ $accountInfo['subscription_details'] }}</p>
                    @endif
                @endif
            

              <p class="px-3 py-1 text-left">
                <span class="font-bold">{{ __('Price') }}: </span> {{ price($order->amount) }}
              </p>
              
              {{-- ✅ চূড়ান্ত লজিক: 'cancelled' হলে লাল, অন্য সব ক্ষেত্রে $settings->theme_color --}}
              <p class="px-3 py-1 text-left">
                <span class="font-bold">{{ __('Status') }}: </span>
                @php
                    $status = strtolower($order->status);
                    // যদি স্ট্যাটাস 'cancelled' হয়, তবে রঙ হবে লাল, অন্যথায় থিম কালার
                    $statusColor = ($status === 'cancel') ? 'red' : $settings->theme_color;
                @endphp
                <span style="color: {{ $statusColor }};">
                  <span class="order-status">{{ $status }}</span>
                </span>
              </p>
              
              @if ($order->delivery_message)
                <p class="px-3 py-1 text-left">
                   <i class="fas fa-info-circle" style="color: red;"></i>
                       {{ $order->delivery_message }}
                </p>
              @endif

            </div>
          </div>
        </div>
        @empty <div class="box-form mx-auto w-36 order-not-found">
          <h4 class="fb-normal text-base">No order found !</h4>
          <a href="../?#topup" class="bg-pink-500 border border-pink-500 hover:bg-pink-500 text-white text-xs py-1 px-2 md:px-2 rounded uppercase paglabazar-btn"> Order Now </a>
        </div> 
        @endforelse
        <div class="mt-3">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
</section> 

    <div id="delivery_message" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delivery Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-left" style="padding: 20px;" id="show_message"></div>
                </div>
                <div class="modal-footer">
                    <a href="{{ asset('page/contact-us') }}" type="button"
                        class="btn theme-btn">Contact Us</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="order_note" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-left" style="padding: 20px;" id="show_note"></div>
                </div>
                <div class="modal-footer">
                    <a href="{{ asset('page/contact-us') }}" type="button"
                        class="btn theme-btn">Contact Us</a>
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
<script type="text/javascript">
    $(document).on('click', '#copy', async function() {
        var text = $(this).data("text");
        try {
            await navigator.clipboard.writeText(text);
            toastr.success('Copied!');
        } catch (err) {
            console.error('Failed to copy text:', err);
        }
    });

    $(document).on('click', '.delivery_message', function() {
        if ($(this).data('message') !== "") {
            $("#show_message").text($(this).data('message'));
        } else {
            $("#show_message").text('No message found to show.');
        }
    });

    $(document).on('click', '.order_note', function() {
        if ($(this).data('note') !== "") {
            $("#show_note").html($(this).data('note').replace(/&lt;br&gt;/g, '<br>'));
        } else {
            $("#show_note").text('No note found to show.');
        }
    });

    $(document).ready(function() {
        $('#status').change(function() {
            var selectedstatus = $(this).val();
            window.location.href = '?status=' + selectedstatus;
        });
    });
</script>
@endpush