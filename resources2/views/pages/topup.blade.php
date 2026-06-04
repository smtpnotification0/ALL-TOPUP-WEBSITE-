@extends('layout.master')
@section('title')
{{ $product->title }} {{ __('-') }} {{ $settings->site_title }}
@endsection
@section('content')
<div>
    <div class="p-2 container m-auto checkout_page">
        <div class="bg-white border rounded-md" style="background-image: url('{{ asset('assets/template/images/ff_top_up_background.webp') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
            <div class="flex justify-between items-center" style="position: relative; z-index: 1; background: linear-gradient(135deg, rgba(0, 0, 0, .3), rgba(0, 0, 0, .2) 50%, rgba(0, 0, 0, .4)); border-radius: 10px;">
                <div class="flex items-center">
                    <div>
                        <img class="rounded-3xl p-2 w-24 h-24" src="{{ asset('uploads') }}/{{ $product->image }}" alt="{{ $product->title }}">
                    </div>
                    <div class="flex items-center">
                        <div>
                            <h2 class="text-lg capitalize" style="color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ $product->title }}</h2>
                            <div class="text-sm text-left" style="color: rgba(255, 255, 255, 0.8);">
                                <span>{{ productType($product->type) }} </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mr-4">
                    <div class="px-2 md:px-4 py-1 md:py-2 rounded-full flex items-center gap-1 md:gap-2" style="margin-right: 5px; background: linear-gradient(135deg,rgb(75, 201, 94),rgb(38, 161, 73)); box-shadow: 0 4px 6px rgba(0,0,0,0.3); white-space: nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <span class="text-xs md:text-sm" style="color: white; font-weight: 600; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); white-space: nowrap;">AI Delivery</span>
                    </div>
                </div>
            </div>
        </div>
        <div>
            {{-- Added form ID for JS submission --}}
            <form method="POST" action="{{ route('topup.buynow') }}" class="md:flex gap-2" id="topup_form">
                @csrf
                <input type="hidden" name="variation_id" id="variation_id" value="">
                <input type="hidden" name="variation_price" id="variation_price" value="">
                @if ($settings->wallet)
                <input type="hidden" name="payment_method" id="payment_method" value="wallet">
                @else
                <input type="hidden" name="payment_method" id="payment_method" value="payment_gateway">
                @endif

                <section class="w-full md:w-2/3 mt-2">
                    <div class="bg-white border rounded-md">
                        {{-- START: Updated Header with Gift Coins Placeholder --}}
                        <div class="text-left px-3 flex items-center justify-start">
                            <div class="_order_header_step_circle mr-2">1</div>
                            <h2 class="text-lg text-black py-2 font-normal fb flex items-center w-full justify-between"> 
                                <span>Select Recharge</span> 
                                <span id="selected_gift_coins_display" class="ml-4">
                                    </span>
                            </h2>
                        </div>
                        <hr>
                        {{-- END: Updated Header with Gift Coins Placeholder --}}
                        <div class="p-1 md:p-4 inline-grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-2 package-item-outer w-full">
                            @foreach ($product->variations as $variation)
                            <button type="button" class="sm-device-package mb-0 w-full drop-shadow-2xl list-group-item flex content-between p-2 active:order-0 variation_list @if ($variation->stock < 1 || ($product->isVoucher() && count($variation->vouchers) < 1)) stockout @endif" style="font-size: 11px; position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center; height: 50px;"
                                id="{{ $variation->id }}" data-price="{{ $variation->price }}" data-gift-coins="{{ $variation->gift_coins ?? 0 }}">
                                <div class="w-full flex flex-wrap">
                                    <div class="flex items-center">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa h-2 mr-2 w-4 text-gray-300 fa-circle fa-w-16 fa-2x">
                                            <path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path>
                                        </svg>
                                        <span class="text-xs font-primary">{{ $variation->title }}</span>
                                    </div>
                                    @if ($variation->stock < 1 || ($product->isVoucher() && count($variation->vouchers) < 1))
                                    <h6 class="bg-red-500 ml-2 rounded-full text-white px-2" style="font-size: 8px; padding-top: 3px; padding-bottom: 0px; max-width: 70px;"> STOCK OUT </h6>
                                    @endif
                                    </div>
                                <div class="font-bold fb-normal" style="color: var( --theme-color); min-width: 46px; float: right; text-align: right;">{{ price($variation->price) }} </div>
                            </button>
                            @endforeach
                        </div>
                        @if($product->has_tutorial)
                        <div class="ml-4 mt-2 md:mt-0">
                            <div>
                                <p class="_body2 mb-3">
                                    <a href=" {{$product->tutorial_link}}" target="_blank" class="text-left text-lg flex items-start info-text blink_me" style="color: rgb(0, 0, 238);">
                                        <span class="text-lg flex" style="font-family: initial;">
                                            {{$product->tutorial_text}}
                                        </span>
                                        <svg stroke="currentColor" fill="none" stroke-width="0" viewBox="0 0 24 24" height="22" width="22" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                </p>
                            </div>
                        </div>
                        @endif
                        </div>
                </section>
                <div class="w-full md:w-1/3 mt-2">
                    @if ($product->isTopup() || $product->isInGame() || $product->isSubscription())
                    <section>
                        <div class="border bg-white rounded-md">
                            <div class="text-left px-3 flex items-center">
                                <div class="_order_header_step_circle mr-2">2</div>
                                <h2 class="text-lg text-black py-2 font-bold fb-normal"> {{ __('Account Info') }} </h2>
                            </div>
                            <hr>
                            @if ($product->isTopup())
                            <div class="p-3">
                                <div class="relative">
                                    {{-- Updated label to show text directly and removed animation IDs/data --}}
                                    <label class="label-title" 
                                           id="player_id_label"
                                           >
                                           {{ $product->input }} 
                                    </label>
                                    <input name="account_info[player_id]" id="player_id" type="text" placeholder="{{ $product->input }}" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900 player_id checkId" required>
                                    @if ($product->uid_checker == 2)
                                    <button type="button" id="check_player_name_btn" class="mt-2 w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2.5 md:py-3 px-4 md:px-5 rounded-md transition-colors duration-200 flex items-center justify-center text-sm md:text-base" style="min-height: 44px;">
                                        <span id="check_player_name_text">Check Player Name</span>
                                    </button>
                                    @endif
                                    @if ($settings->enable_uid_checker)
                                    @if ($product->uid_checker == 1)
                                    <div class="gamename1">
                                        <span id="gamename1">Click to check player name</span>
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if ($product->isSubscription())
                            <div class="p-3">
                                <div class="relative">
                                    <label class="label-title">{{ $product->input }}</label><input name="account_info[subscription_details]" id="player_id" type="text" placeholder="{{ $product->input }}" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900 player_id checkId" required>
                                </div>
                            </div>
                            @endif
                            @if ($product->isInGame())
                            <div class="p-3">
                                <div class="p-2 pb-0">
                                    <label>{{ __('Account Type') }}</label>
                                    <select name="account_info[account_type]" id="game_account_type" class="form-select relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md text-sm px-3.5 py-2.5 shadow-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 pe-11">
                                        <option value="Gmail">Gmail</option>
                                        <option value="Facebook">Facebook</option>
                                    </select>
                                </div>
                                <div class="p-2 pb-0"><label>Enter Email/Number</label><input name="account_info[game_account]" id="game_account" type="text" placeholder="Enter Email/Number" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" required></div>
                                <div class="p-2 pb-0"><label>Password</label><input name="account_info[game_password]" id="game_password" type="text" placeholder="Enter Password" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" required></div>
                                <div class="p-2 pb-0"><label>Account Back Up If Have</label><input name="account_info[game_backup]" id="game_backup" type="text" placeholder="Enter Back Up Code" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900"></div>
                                @if (!empty($settings->backup_code_video_link))
                                <div class="w-full text-left ml-1">
                                    <a href="{{ $settings->backup_code_video_link }}" target="_blanck" class="text-left text-lg flex items-start info-text" style="color: rgb(0, 0, 238);">কিভাবে ফেসবুক অ্যাকাউন্ট এর ব্যাকআপ কোড বের করবেন?</a>
                                </div>
                                @endif
                            </div>
                            @endif
                            </div>
                    </section>
                    @endif
                    @if ($product->isVoucher())
                    <section>
                        <div class="flex justify-between align-middle px-3 bg-white rounded-md border quantity-container">
                            <div class="my-auto font-primary"> {{ __('Quantity') }} </div>
                            <div>
                                <label for="{{ __('Quantity') }}" class="sr-only"> {{ __('Quantity') }} </label>
                                <div class="flex items-center border-2 my-2 border-gray-200 rounded-full px-2 quantity-options">
                                    <div class="cursor-pointer w-6 h-6 flex items-center justify-center" id="decrease">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"></path>
                                        </svg>
                                    </div>
                                    <input value="1" min="1" autocomplete="off" type="number" class="h-10 w-16 border-transparent text-center bg-white [&amp;::-webkit-inner-spin-button]:appearance-none" id="quantity" name="quantity">
                                    <div class="cursor-pointer w-6 h-6 flex items-center justify-center" id="increase">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    @endif
                    <section>
                        <div class="bg-white border mt-2" style="border-radius: 0.375rem; border-width: 1px;">
                            <div class="text-left px-3 flex items-center">
                                <div class="_order_header_step_circle mr-2">3</div>
                                <h2 class="text-lg text-black py-2 font-bold fb-normal"> Payment Methods </h2>
                            </div>
                            <hr>
                            <div class="flex justify-center py-3 px-2">
                            @if ($settings->wallet)
                                <div class="w-full pm_list" id="wallet">
                                    <div class="m-1">
                                        <label for="wallet_radio" class="mb-0 w-full list-group-item pt-2 cursor-pointer" style="display: block; font-size: 11px; position: relative; overflow: hidden;">
                                            <span class="absolute left-0 check_selected element-check-label" style="color: rgb(255, 255, 255);">L </span>
                                            <img src="{{ asset('assets/template/images/walletpay.jpg') }}" alt="wallet" class="p-2" style="height: 6rem;">
                                            <input id="wallet_radio" name="send" type="radio" class="absolute" value="1" style="visibility: hidden;">
                                            <div class="bg-gray-300 text-left p-1">
                                                <p class="text-xs p-0 capitalize fb-normal"> {{ __('Wallet Pay') }}
                                                @auth
                                                {{ __('(')}}{{ $settings->currency_symbol }}<span
                                                    id='wallet_balance'>{{ amount(Auth::user()->balance) }}</span>
                                                {{ __(')') }}
                                                @endauth</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            @endif

                                <div class="text-center w-full pm_list" id="payment_gateway">
                                    <div class="m-1">
                                        <label for="sslcom" class="mb-0 w-full list-group-item pt-2 cursor-pointer" style="display: block; font-size: 11px; position: relative; overflow: hidden;">
                                            <span class="absolute check_selected left-0" style="color: rgb(255, 255, 255);">L </span>
                                            <img src="{{ asset('assets/template/images/bd_payments.png') }}" alt="SSL" class="p-2" style="height: 6rem;">
                                            <input id="sslcom" name="send" type="radio" class="absolute" value="2" style="visibility: hidden;">
                                            <div class="bg-gray-300 text-left">
                                                <p class="text-xs p-1 capitalize fb-normal"> Instant Pay</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row pb-5">
                                <div class="col-md-12 text-left px-3">
                                    <div>
                                        <div class="fb-normal text-xs flex items-center" style="color: gray;">
                                            <svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="info-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa h-4 w-4 mr-1 fa-info-circle fa-w-16 fa-2x">
                                                <path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 448c-110.532 0-200-89.431-200-200 0-110.495 89.472-200 200-200 110.491 0 200 89.471 200 200 0 110.53-89.431 200-200 200zm0-338c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"></path>
                                            </svg>
                                            আপনার অ্যাকাউন্ট ব্যালেন্স
                                            <div style="min-width: 100px;">
                                                <span class="flex items-center">
                                                    <p class="pl-2 text-pink-500 font-bold fb cost_alert_bl">
                                                         {{ $settings->currency_symbol }}
                                                    @auth
                                                         {{ amount(Auth::user()->balance) }}
                                                         @else
                                                         0
                                                         @endauth
                                                    </p>
                                                    <div class="border ml-2 p-1 rounded cursor-pointer">
                                                        <svg viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                                            <path fill="currentColor" d="M2 12C2 16.97 6.03 21 11 21C13.39 21 15.68 20.06 17.4 18.4L15.9 16.9C14.63 18.25 12.86 19 11 19C4.76 19 1.64 11.46 6.05 7.05C10.46 2.64 18 5.77 18 12H15L19 16H19.1L23 12H20C20 7.03 15.97 3 11 3C6.03 3 2 7.03 2 12Z"></path>
                                                        </svg>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="fb-normal text-xs flex items-center mb-3" style="color: gray;">
                                            <svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="info-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa h-4 w-4 mr-1 fa-info-circle fa-w-16 fa-2x">
                                                <path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 448c-110.532 0-200-89.431-200-200 0-110.495 89.472-200 200-200 110.491 0 200 89.471 200 200 0 110.53-89.431 200-200 200zm0-338c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"></path>
                                            </svg>
                                            প্রোডাক্ট কিনতে আপনার প্রয়োজন <span class="text-pink-500 font-bold fb" style="padding: 0px 4px; font-size: 14px;"> {{ $settings->currency_symbol }}<span id="total_cost">0</span></span> ।
                                        </p>
                                         @auth
                                         <div>
                                             <a href="add-funds" class="align-middle bg-pink-500 hover:bg-pink-400 text-center px-4 py-2 text-white text-sm font-semibold rounded inline-block shadow-lg w-full gosizi-btn" id="add_fund" style="margin-bottom: 10px;display:none;">
                                                 ADD FUND
                                             </a>
                                            {{-- বাটনটি আপডেট করা হয়েছে --}}
<div id="buy-now-wrapper">
    <button onclick="processOrder(this)" 
            class="align-middle bg-pink-500 hover:bg-pink-400 text-center px-4 md:px-6 py-3.5 md:py-4 text-white text-base md:text-lg font-semibold rounded inline-block shadow-lg w-full gosizi-btn buy_now_btn flex items-center justify-center gap-2" 
            id="buy_now" 
            type="button" 
            style="min-height: 52px;">
        
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span>Buy Now</span>
    </button>
</div>

{{-- জাভাস্ক্রিপ্ট কোড --}}
<script>
function processOrder(btn) {
    // বাটনটিকে হাইড করে দেওয়া হচ্ছে
    btn.style.display = 'none';

    // এখানে আপনার অর্ডারের বাকি কাজ বা Modal ওপেন করার কোড থাকবে
    console.log("Order processing...");

    // ৩ সেকেন্ড পর বাটনটি আবার দেখা যাবে (যাতে ইউজার পরে আবার প্রয়োজন হলে পায়)
    setTimeout(function() {
        btn.style.display = 'flex';
    }, 3000); // ৩০০০ মিলি-সেকেন্ড = ৩ সেকেন্ড
}
</script>
                                         @else
                                         <div>
                                            <a href="{{ route('login') }}">
                                            <button class="align-middle bg-pink-500 hover:bg-pink-400 text-center px-4 py-2 text-white text-sm font-semibold rounded inline-block shadow-lg w-full gosizi-btn checkout_login" type="button"> LOG IN </button>
                                            </a>
                                         </div>
                                        @endauth
                                    </div>
                                </div>
                                </div>
                        </div>
                    </section>
                </div>
            </form>
        </div>
        <div class="mt-2 bg-white border rounded-md">
            <h1 class="font-bold p-2"> Rules &amp; Conditions </h1>
            <hr>
            <div class="p-2">
                {!! $product->content !!}
            </div>
        </div>
    </div>
</div>

{{-- UPDATED CONFIRMATION MODAL STRUCTURE --}}
<div id="confirmation_modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 9999; justify-content: center; align-items: center;">
    <div class="modal-content" style="background-color: white; border-radius: 8px; max-width: 400px; width: 90%; margin: 20px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
        {{-- Normal Order Details View --}}
        <div id="modal_order_details">
        <h2 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 15px;">Confirm Order</h2>
        
        <div style="font-size: 1rem; margin-bottom: 15px; border-top: 1px solid #eee; padding-top: 10px;">
            {{-- Selected Variation --}}
            <p style="margin-bottom: 5px; display: flex; justify-content: space-between;">
                <span>Selected Variation:</span> 
                <strong style="color: #495057;" id="modal_variation_title">N/A</strong> 
            </p>
            {{-- Account Info --}}
            <p style="margin-bottom: 10px; display: flex; justify-content: space-between; border-bottom: 1px dotted #ccc; padding-bottom: 5px;">
                <span>Account Info:</span> 
                {{-- font size set to 0.9rem to handle longer IDs/Details --}}
                <strong style="color: #E91E63; font-size: 0.9rem; text-align: right;" id="modal_account_info">N/A</strong> 
            </p>
            
            <p style="margin-bottom: 5px; display: flex; justify-content: space-between;">
                <span>Product Price:</span> 
                <strong style="color: #6c757d;" id="modal_product_price">{{ $settings->currency_symbol }}0</strong> 
            </p>
            <p style="margin-bottom: 5px; display: flex; justify-content: space-between;">
                <span>You Get Coins:</span> 
                <strong style="color: #38c172;" id="modal_gift_coins">0 Coins</strong>
            </p>
        </div>
        
        <p style="font-size: 1.1rem; font-weight: bold; margin-bottom: 15px; border-top: 1px solid #eee; padding-top: 10px; display: flex; justify-content: space-between;">
            <span>Total Payable:</span> 
            <span style="text-align: right;">
                <strong style="color: #E91E63;" id="modal_total_cost">{{ $settings->currency_symbol }}0</strong>
                <small style="font-size: 0.7rem; color: #6c757d; font-weight: normal; margin-left: 1px;">+fee</small>
            </span>
        </p>
        
        {{-- This message will be updated by JS --}}
        <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 20px;" id="modal_payment_info">পেমেন্ট গেটওয়ের মাধ্যমে নিরাপদে লেনদেন সম্পন্ন হবে।</p>
        
        <div style="display: flex; justify-content: space-between; gap: 10px;">
            {{-- Main action button. Text will be 'Pay Now' or 'Confirm' via JS --}}
            <button id="modal_confirm_button" style="flex: 1; padding: 10px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; background-color: #E91E63; color: white; transition: background-color 0.3s;">Pay Now</button>
            {{-- Cancel button --}}
            <button id="modal_cancel" style="flex: 1; padding: 10px; border: 1px solid #dc3545; border-radius: 4px; font-weight: bold; cursor: pointer; background-color: white; color: #dc3545; transition: background-color 0.3s;">Cancel</button>
            </div>
        </div>
        
        {{-- Loading/Redirecting View --}}
        <div id="modal_loading_state" style="display: none; text-align: center; padding: 20px;">
            <div style="margin-bottom: 20px;">
                <div id="modal_loading_spinner" class="payment-loading-spinner" style="width: 60px; height: 60px; border: 4px solid #f3f3f3; border-top: 4px solid #E91E63; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
            </div>
            <h3 id="modal_loading_title" style="font-size: 1.2rem; font-weight: bold; margin-bottom: 10px; color: #E91E63;">Redirecting to Payment System</h3>
            <p id="modal_loading_message" style="color: #6c757d; font-size: 0.9rem;">Please wait while we redirect you to the secure payment gateway...</p>
        </div>
    </div>
</div>
@endsection
@push('js')
@if ($settings->enable_uid_checker)
@if ($product->uid_checker == 1)
<script>
    $(document).ready(function () {
        $('.gamename1').on('click', function () {
            var id = $('#player_id').val().trim();

            if (id) {
                var url = "{{ route('uidcheck') }}";

                $.post(url, {
                    id: id,
                    _token: "{{ csrf_token() }}"
                })
                .done(function (response) {
                    const nickname = response.nickname ?? 'No nickname found';
                    $('.gamename1').html('<span> ' + nickname + ' </span>');
                })
                .fail(function () {
                    $('.gamename1').html('<span>Error occurred while checking Player ID.</span>');
                });

            } else {
                $('.gamename1').html('<span>Please enter a valid Player ID.</span>');
            }
        });
    });
</script>
@endif
@endif
@if ($product->uid_checker == 2)
<script>
    // Check Player Name functionality
    $(document).ready(function() {
        const $checkBtn = $('#check_player_name_btn');
        const $checkBtnText = $('#check_player_name_text');
        const $playerIdInput = $('#player_id');
        const checkUrl = "{{ route('player.name.check') }}";
        
        // Check player name function
        function checkPlayerName() {
            const uid = $playerIdInput.val().trim();
            
            if (!uid) {
                alert('Please enter a UID first.');
                return;
            }
            
            $checkBtn.prop('disabled', true);
            $checkBtn.addClass('opacity-50');
            $checkBtnText.text('Checking...');
            
            $.ajax({
                url: checkUrl,
                method: 'POST',
                data: {
                    uid: uid,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success && response.name) {
                        $checkBtnText.text(response.name);
                    } else {
                        $checkBtnText.text('Player Not Found');
                    }
                    $checkBtn.prop('disabled', false);
                    $checkBtn.removeClass('opacity-50');
                },
                error: function(xhr) {
                    $checkBtnText.text('Error Checking');
                    $checkBtn.prop('disabled', false);
                    $checkBtn.removeClass('opacity-50');
                    
                    console.error('Error checking player name:', xhr.responseJSON || xhr.statusText);
                }
            });
        }
        
        // Button click handler - only check on click
        $checkBtn.on('click', function() {
            checkPlayerName();
        });
    });
</script>
@endif
<script>
    const themeColor = "{{ $settings->theme_color }}";
    const currencySymbol = "{{ $settings->currency_symbol }}";

    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse-glow {
            0% {
                box-shadow: 0 0 0 0 rgba(233, 30, 99, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(233, 30, 99, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(233, 30, 99, 0);
            }
        }
        
        @keyframes button-shimmer {
            0% {
                background-position: -200px 0;
            }
            100% {
                background-position: calc(200px + 100%) 0;
            }
        }
        
        .buy-now-animated {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .buy-now-animated:not(:disabled) {
            animation: pulse-glow 2s infinite;
        }
        
        .buy-now-animated:not(:disabled):hover {
            transform: translateY(-2px);
            animation: pulse-glow 1s infinite, button-shimmer 2s infinite linear;
            background-image: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            background-size: 200px 100%;
        }
        
        .buy-now-animated:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .buy-now-loading {
            position: relative;
            color: transparent;
        }
        
        .buy-now-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Modal Animation Styles */
        #confirmation_modal {
            opacity: 0;
            transition: opacity 0.25s ease-out;
        }
        
        #confirmation_modal.modal-show {
            opacity: 1;
        }
        
        #confirmation_modal .modal-content {
            transform: scale(0.85) translateY(-30px);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease-out;
        }
        
        #confirmation_modal.modal-show .modal-content {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        
        /* Smooth close animation */
        #confirmation_modal.modal-hide {
            opacity: 0;
            transition: opacity 0.2s ease-in;
        }
        
        #confirmation_modal.modal-hide .modal-content {
            transform: scale(0.9) translateY(15px);
            opacity: 0;
            transition: transform 0.2s ease-in, opacity 0.2s ease-in;
        }
        
        /* Smooth transition between order details and loading state */
        #confirmation_modal .modal-content {
            position: relative;
            min-height: 300px;
            transition: min-height 0.3s ease-out;
        }
        
        #modal_order_details {
            opacity: 1;
            transform: translateX(0);
            transition: opacity 0.35s ease-out, transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            width: 100%;
        }
        
        #modal_order_details.modal-fade-out {
            opacity: 0;
            transform: translateX(-30px);
            pointer-events: none;
        }
        
        #modal_loading_state {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transform: translateX(30px);
            transition: opacity 0.35s ease-in 0.2s, transform 0.35s cubic-bezier(0.4, 0, 0.2, 1) 0.2s;
            pointer-events: none;
            padding: 20px;
        }
        
        #modal_loading_state.modal-fade-in {
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
        }
    `;
    document.head.appendChild(style);

    $(document).ready(function() {
        const $playerId = $('#player_id');
        const $playerIdError = $("#player_id_error");
        const $gameAccount = $('#game_account');
        const $gameAccountError = $("#game_account_error");
        const $gamePassword = $('#game_password');
        const $gamePasswordError = $("#game_password_error");
        const $addMoneyInstruction = $('#add_money_instruction');
        const $wallet = $('#wallet');
        const $walletBalance = $('#wallet_balance');
        const $variationId = $('#variation_id');
        const $variationPrice = $('#variation_price');
        const $totalCost = $('#total_cost');
        const $paymentMethod = $('#payment_method');
        const $quantityInput = $('#quantity');
        const $buyNow = $("#buy_now");
        const $addFund = $("#add_fund");
        const $topupForm = $("#topup_form");
        
        // Modal elements
        const $confirmationModal = $("#confirmation_modal");
        const $modalTotalCost = $("#modal_total_cost");
        const $modalConfirmButton = $("#modal_confirm_button"); 
        const $modalCancel = $("#modal_cancel");
        const $modalProductPrice = $("#modal_product_price"); 
        const $modalGiftCoins = $("#modal_gift_coins");       
        const $modalPaymentInfo = $("#modal_payment_info");   
        const $modalVariationTitle = $("#modal_variation_title");
        const $modalAccountInfo = $("#modal_account_info");
        const $modalOrderDetails = $("#modal_order_details");
        const $modalLoadingState = $("#modal_loading_state");
        const $modalLoadingTitle = $("#modal_loading_title");
        const $modalLoadingMessage = $("#modal_loading_message");
        const $modalLoadingSpinner = $("#modal_loading_spinner");
        

        let selectedGiftCoins = 0; 

        // Function to format price - hides .00 if decimals are zero
        function formatPrice(price) {
            const num = parseFloat(price);
            if (isNaN(num)) return '0';
            // Format to 2 decimals, then remove .00 if present
            const formatted = num.toFixed(2);
            return formatted.replace(/\.00$/, '');
        }

        // Add animation class to Buy Now button
        $buyNow.addClass('buy-now-animated');

        // Function to show modal with smooth animation
        function showModal() {
            // Remove any hide classes
            $confirmationModal.removeClass('modal-hide');
            
            // Show modal first
            $confirmationModal.css('display', 'flex');
            
            // Force reflow to ensure display is applied
            $confirmationModal[0].offsetHeight;
            
            // Add show class to trigger animation
            setTimeout(function() {
                $confirmationModal.addClass('modal-show');
            }, 10);
        }
        
        // Function to hide modal with smooth animation
        function hideModal() {
            // Remove show class and add hide class for exit animation
            $confirmationModal.removeClass('modal-show').addClass('modal-hide');
            
            // Hide modal after animation completes (200ms for close animation)
            setTimeout(function() {
                $confirmationModal.hide().removeClass('modal-hide');
            }, 200);
        }
        
        // Function to smoothly transition from order details to loading state
        function transitionToLoadingState() {
            // Remove any existing transition classes
            $modalOrderDetails.removeClass('modal-fade-out');
            $modalLoadingState.removeClass('modal-fade-in');
            
            // Ensure loading state is visible but transparent (for absolute positioning)
            $modalLoadingState.css('display', 'flex');
            
            // Force reflow
            $modalLoadingState[0].offsetHeight;
            
            // Start fading out order details
            $modalOrderDetails.addClass('modal-fade-out');
            
            // After a short delay, start fading in loading state
            setTimeout(function() {
                $modalLoadingState.addClass('modal-fade-in');
            }, 200); // Start loading fade-in to match CSS delay
        }
        
        // Function to reset modal state (close and reset to default)
        function resetModalState() {
            // Check if modal is currently visible
            const isVisible = $confirmationModal.is(':visible') || $confirmationModal.hasClass('modal-show');
            
            if (isVisible) {
                // Hide modal with animation if it's visible
                hideModal();
            } else {
                // Just ensure it's completely hidden if already hidden
                $confirmationModal.hide().removeClass('modal-show modal-hide');
            }
            
            // Reset modal content: show order details, hide loading
            $modalOrderDetails.show().removeClass('modal-fade-out');
            $modalLoadingState.hide().removeClass('modal-fade-in');
            
            // Reset button states
            $modalConfirmButton.prop('disabled', false);
            $modalCancel.prop('disabled', false);
            
            // Reset loading spinner and title colors to default (pink for payment gateway)
            $modalLoadingSpinner.css('border-top-color', '#E91E63');
            $modalLoadingTitle.css('color', '#E91E63');
        }

        // Reset modal state on page load
        resetModalState();

        // Reset modal state when page is visible (handles browser back/forward navigation)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                resetModalState();
            }
        });

        // Reset modal state on pageshow event (handles browser back/forward navigation)
        window.addEventListener('pageshow', function(event) {
            // event.persisted is true when page is loaded from cache (back/forward navigation)
            if (event.persisted) {
                resetModalState();
            }
        });

        // START: Custom Modal Logic
        $buyNow.on('click', function(e) {
            e.preventDefault(); 
            
            // If the button is disabled (e.g., stockout or insufficient wallet funds), do nothing
            if ($(this).prop('disabled')) {
                return;
            }

            if ($('#variation_id').val() === "") {
                alert("অনুগ্রহ করে রিচার্জের জন্য একটি অপশন নির্বাচন করুন।");
                return;
            }

            // Get base values
            const finalCost = $totalCost.text();
            const unitPrice = parseFloat($variationPrice.val()) || 0;
            const paymentMethod = $paymentMethod.val();
            const quantity = parseInt($quantityInput.val()) || 1;
            
            // Calculate dependent values
            const totalProductPriceValue = unitPrice * quantity;
            const totalProductPrice = formatPrice(totalProductPriceValue);
            const totalGiftCoins = selectedGiftCoins * quantity;
            
            // Retrieve Variation Title and Account Info for the modal
            const selectedVariationTitle = $('.selected_variation').find('.text-xs').text().trim() || 'N/A';
            let accountInfo = 'N/A';
            
            @if ($product->isTopup())
                // Use a short, meaningful text if player ID is missing
                accountInfo = $('#player_id').val().trim() || 'Player ID Missing'; 
            @elseif ($product->isSubscription())
                accountInfo = $('[name="account_info[subscription_details]"]').val().trim() || 'Subscription Details Missing';
            @elseif ($product->isInGame())
                const accountType = $('#game_account_type').val().trim() || 'N/A';
                const accountDetails = $('#game_account').val().trim() || 'N/A';
                if(accountDetails !== 'N/A') {
                    // Combine account type and details for display
                    accountInfo = `${accountType}: ${accountDetails}`;
                } else {
                    accountInfo = 'Account Details Missing';
                }
            @else
                // For Voucher/Other types where Account Info is not explicitly required
                accountInfo = 'Not Required'; 
            @endif
            
            // Update modal content
            $modalVariationTitle.text(selectedVariationTitle); 
            $modalAccountInfo.text(accountInfo); 
            $modalProductPrice.text(`${currencySymbol}${totalProductPrice}`);
            $modalGiftCoins.text(`${totalGiftCoins} Coins`);
            $modalTotalCost.text(`${currencySymbol}${formatPrice(finalCost)}`);

            // Update button text and payment message based on method
            if (paymentMethod === 'wallet') {
                $modalConfirmButton.text('Confirm');
                // Use a distinct color for Wallet Confirm
                $modalConfirmButton.css('background-color', themeColor); 
                $modalPaymentInfo.text('আপনার অ্যাকাউন্ট ব্যালেন্স থেকে পেমেন্ট করা হবে।');
            } else { // payment_gateway
                $modalConfirmButton.text('Pay Now');
                // Use a distinct color for Instant Pay
                $modalConfirmButton.css('background-color', '#E91E63'); 
                $modalPaymentInfo.text('পেমেন্ট গেটওয়ের মাধ্যমে নিরাপদে লেনদেন সম্পন্ন হবে।');
            }

            // Show the custom modal with smooth animation
            showModal();

        });
        
        $modalConfirmButton.on('click', function() {
            const paymentMethod = $paymentMethod.val();
            
            // For wallet payment, show loading state in modal
            if (paymentMethod === 'wallet') {
                // Disable buttons to prevent double clicks
                $modalConfirmButton.prop('disabled', true);
                $modalCancel.prop('disabled', true);
                
                // Update loading message and spinner color for wallet payment
                $modalLoadingTitle.text('Purchasing Please Wait');
                $modalLoadingTitle.css('color', themeColor || '#38c172');
                $modalLoadingMessage.text('Your order is being processed. Please wait...');
                $modalLoadingSpinner.css('border-top-color', themeColor || '#38c172');
                
                // Smoothly transition from order details to loading state
                transitionToLoadingState();
                
                // Prepare form data
                const formData = new FormData($topupForm[0]);
                
                // Submit form via AJAX
                $.ajax({
                    url: $topupForm.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success && response.redirect_url) {
                            // Redirect to success page after a brief delay
            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1000);
                        } else {
                            // Error in response
                            alert(response.message || 'Order processing failed. Please try again.');
                            // Reset modal state
                            $modalOrderDetails.show().removeClass('modal-fade-out');
                            $modalLoadingState.hide().removeClass('modal-fade-in');
                            $modalConfirmButton.prop('disabled', false);
                            $modalCancel.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        console.error('Order processing error:', xhr);
                        
                        // Try to parse error response
                        let errorMessage = 'Order processing failed. Please try again.';
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.message) {
                                errorMessage = errorResponse.message;
                            }
                        } catch (e) {
                            // Use default error message
                        }
                        
                        alert(errorMessage);
                        
                        // Reset modal state
                        $modalOrderDetails.show();
                        $modalLoadingState.hide();
                        $modalConfirmButton.prop('disabled', false);
                        $modalCancel.prop('disabled', false);
                    }
                });
            } else {
                // For payment gateway, show loading state in modal and redirect
                // Disable buttons to prevent double clicks
                $modalConfirmButton.prop('disabled', true);
                $modalCancel.prop('disabled', true);
                
                // Update loading message and spinner color for payment gateway
                $modalLoadingTitle.text('Redirecting to Payment System');
                $modalLoadingTitle.css('color', '#E91E63');
                $modalLoadingMessage.text('Please wait while we redirect you to the secure payment gateway...');
                $modalLoadingSpinner.css('border-top-color', '#E91E63');
                
                // Smoothly transition from order details to loading state
                transitionToLoadingState();
                
                // Prepare form data
                const formData = new FormData($topupForm[0]);
                
                // Submit form via AJAX to get payment URL
                $.ajax({
                    url: $topupForm.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success && response.payment_url) {
                            // Redirect to payment URL after showing loading animation
                            setTimeout(function() {
                                window.location.href = response.payment_url;
                            }, 1500);
                        } else {
                            // Error in response
                            alert(response.message || 'Failed to initiate payment. Please try again.');
                            // Reset modal state
                            $modalOrderDetails.show().removeClass('modal-fade-out');
                            $modalLoadingState.hide().removeClass('modal-fade-in');
                            $modalConfirmButton.prop('disabled', false);
                            $modalCancel.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        console.error('Payment initiation error:', xhr);
                        
                        // Try to parse error response
                        let errorMessage = 'Failed to initiate payment. Please try again.';
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.message) {
                                errorMessage = errorResponse.message;
                            }
                        } catch (e) {
                            // Use default error message
                        }
                        
                        alert(errorMessage);
                        
                        // Reset modal state
                        $modalOrderDetails.show();
                        $modalLoadingState.hide();
                        $modalConfirmButton.prop('disabled', false);
                        $modalCancel.prop('disabled', false);
                        
                        // Fallback: try submitting form normally
                        // setTimeout(function() {
                        //     $topupForm.submit();
                        // }, 1000);
                    }
                });
            }
        });

        $modalCancel.on('click', function() {
            // Don't allow closing during payment processing
            if ($modalLoadingState.is(':visible')) {
                return false;
            }
            // Close modal and reset state
            resetModalState();
        });
        // END: Custom Modal Logic


        function showError($element, message) {
            $element.html(`<div class='alert alert-white alert-p5 m-lr-7'>${message}</div>`);
        }

        function clearError($element) {
            $element.html("");
        }

        function handleInputError($input, $errorElement, message) {
            $input.on('keyup', function() {
                if ($(this).val() === "") {
                    showError($errorElement, message);
                } else {
                    clearError($errorElement);
                }
            });
        }

        handleInputError($playerId, $playerIdError, "Player id required");
        handleInputError($gameAccount, $gameAccountError, "Gmail/number required");
        handleInputError($gamePassword, $gamePasswordError, "Password required");

        $('#payment_gateway').on('click', function() {
            $addMoneyInstruction.show();
        });

        $wallet.on('click', function() {
            $addMoneyInstruction.hide();
        });

        function selectVariation() {
            $('.variation_list').click(function() {
                var clickedVariation = $(this);
                var hasStockoutClass = clickedVariation.hasClass('stockout');

                if (!hasStockoutClass) {
                    $('.variation_list').removeClass('selected_variation');
                    clickedVariation.addClass('selected_variation');
                    $('.variation_list').each(function() {
                        var svg = $(this).find('svg');
                        if ($(this).hasClass('selected_variation')) {
                            svg.attr('data-icon', 'check-circle');
                            svg.html('<path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248 6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"></path>');
                            svg.css('color', 'var(--theme-color)');
                        } else {
                            svg.attr('data-icon', 'circle');
                            svg.html('<path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path>');
                            svg.css('color', '');
                        }
                    });
                    $('#quantity').val("1");
                    $('#variation_id').val(clickedVariation.attr('id'));
                    $('#variation_price').val(clickedVariation.data('price'));

                    // START: Logic to display gift coins next to the title (with theme color)
                    const giftCoins = clickedVariation.data('gift-coins');
                    selectedGiftCoins = parseInt(giftCoins, 10) || 0; 
                    const $giftCoinsDisplay = $('#selected_gift_coins_display');

                    if (selectedGiftCoins > 0) {
                        const displayCoins = selectedGiftCoins; 
                        
                        const coinsHtml = `
                            <span class="flex items-center text-base font-bold">
                                Win <span style="color: ${themeColor};">${displayCoins}</span> coins
                                <img src="{{ asset('assets/template/images/coins.svg') }}" alt="Coins" class="ml-1" style="height: 14px; width: 14px;">
                            </span>
                        `;
                        $giftCoinsDisplay.html(coinsHtml);
                    } else {
                        $giftCoinsDisplay.empty();
                    }
                    // END: Logic

                    enableBuyNow();
                    const unitCost = parseFloat($variationPrice.val());
                    autoSelectPaymentMethod(unitCost);
                    if (unitCost !== "" && unitCost !== "undifnied") {
                        $totalCost.text(formatPrice(unitCost));
                    } else {
                        $totalCost.text("0");
                    }
                    checkWallet();

                    // --- REMOVED: Scroll to the target section and start animation ---
                }
            });
        }

        function selectPaymentMethod() {
            $('.pm_list').click(function() {
                var clickedPM = $(this);
                $('.pm_list .check_selected').removeClass('element-check-label');
                clickedPM.find('.check_selected').addClass('element-check-label');
                $('#payment_method').val(clickedPM.attr('id'));
                checkWallet();
            });
        }

        function checkWallet() {
            const variationPrice = parseFloat($variationPrice.val()) || 0;
            const paymentMethod = $paymentMethod.val();
            const walletBalance = parseFloat($walletBalance.text()) || 0;

            if ($('#quantity').length) {
                var getQuantity = $('#quantity').val();
            } else {
                var getQuantity = 1;
            }
            var calNewCost = variationPrice * getQuantity;

            if (!isNaN(calNewCost)) {
                if (paymentMethod === "wallet" && calNewCost > walletBalance) {
                    disableBuyNow();
                } else {
                    enableBuyNow();
                }
            }
        }

        function disableBuyNow() {
            $buyNow.prop("disabled", true);
            $addFund.show();
        }

        function enableBuyNow() {
            $buyNow.prop("disabled", false);
            $addFund.hide();
        }

        function handleQuantityChange() {
            $(document).on('click', '.quantity-options div', function() {
                var $quantityInput = $('#quantity');
                var currentValue = parseInt($quantityInput.val());

                if ($(this).is('#decrease') && currentValue > 1) {
                    $quantityInput.val(currentValue - 1);
                } else if ($(this).is('#increase')) {
                    $quantityInput.val(currentValue + 1);
                }

                const unitCost = parseFloat($variationPrice.val());
                const newQuantity = parseInt($quantityInput.val());
                const newCost = unitCost * newQuantity;

                if (newCost !== "" && newCost !== "undifnied") {
                    $totalCost.text(formatPrice(newCost));
                } else {
                    $totalCost.text("0");
                }
                autoSelectPaymentMethod(newCost);
            });

            $(document).on('change', '#quantity', function() {
                const unitCost = parseFloat($variationPrice.val());
                const newQuantity = parseInt($quantityInput.val());
                const newCost = unitCost * newQuantity;

                if (newCost !== "" && newCost !== "undifnied") {
                    $totalCost.text(formatPrice(newCost));
                } else {
                    $totalCost.text("0");
                }
                autoSelectPaymentMethod(newCost);
            });
        }

        function autoSelectPaymentMethod(cost) {
            const walletBalance = parseFloat($walletBalance.text()) || 0;
            const currentPaymentMethod = $paymentMethod.val();

            if ($('#wallet').length && cost > walletBalance) {
                $('#payment_gateway').click();
            } else if ($('#wallet').length && cost <= walletBalance && currentPaymentMethod !== "wallet") {
                $wallet.click();
            }

            checkWallet();
        }

        function initializePaymentMethod() {
            if ($('#wallet').length) {
                const balanceText = $walletBalance.text();
                if (balanceText.trim() !== "") {
                    const walletBalance = parseFloat(balanceText);
                    if (walletBalance > 0) {
                        // $wallet.click();
                    } else {
                        $('#payment_gateway').click();
                    }
                } else {
                    $('#payment_gateway').click();
                }
            } else {
                $('#payment_gateway').click();
            }
        }


        // Initialize event handlers
        selectVariation();
        selectPaymentMethod();
        handleQuantityChange();

        // Initial check for Buy Now button state on load
        checkWallet();

        // Ensure a payment method is selected on load
        initializePaymentMethod();

    });
</script>
@endpush