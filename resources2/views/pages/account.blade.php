@extends('layout.master')
@section('title')
Account
@endsection
@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="p-2">
    <div class="text-center">
        <button>
            <img src="{{ Auth::user()->picture }}" class="user_style_profile">
        </button>
    </div>

    <div class="text-center w-full mt-2">
        <span class="capitalize theme-color-text font-16 font-semibold">Hi, {{ Auth::user()->name }}</span>
    </div>

    <div class="text-center w-full flex mb-3 md:mb-8 justify-center items-center">
        <span class="primary-font-color-text font-16">
            <b class="font-bold">Available Balance : ৳{{ Auth::user()->balance }}</b>
        </span>
    </div>

    <div style="max-width: 700px; margin: auto;">
        <div class="text-center grid md:grid-cols-4 grid-cols-2 md:gap-4 gap-3 my-2 md:my-5 mb-10 statics-container">

            <!-- Support Pin -->
            <div class="bg-white statics flex flex-col items-center justify-center p-3 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                <div class="flex items-center justify-center gap-1">
                    <h2 class="text-lg font-semibold fb-normal statics-heading">{{ Auth::user()->id }}</h2>
                    <button onclick="handleCopyClick(this, '{{ Auth::user()->id }}', 'Copied!')" class="copy-btn small">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
                <h2 class="text-md primary-font-color-text font-normal fb-normal mt-1">Support Pin</h2>
            </div>

            <!-- Total Coins -->
            <div class="bg-white statics flex flex-col items-center justify-center p-3 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                <h2 class="text-lg font-semibold fb-normall statics-heading">{{ number_format(Auth::user()->coins) }}</h2>
                <h2 class="text-md primary-font-color-text font-normal fb-normal">Total Coins</h2>

                @if(Auth::user()->coins >= 1000)
                    <a href="{{ route('account.refer') }}" class="mt-2 w-full flex justify-center">
                        <button type="button" class="redeem-btn">
                            <i class="fa-solid fa-gift mr-1"></i> Redeem
                        </button>
                    </a>
                @endif
            </div>

            <!-- Total Order -->
            <div class="bg-white statics flex flex-col items-center justify-center p-3 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                <h2 class="text-lg font-semibold fb-normall statics-heading">{{ Auth::user()->total_order }}</h2>
                <h2 class="text-md primary-font-color-text font-normal fb-normal">Total Order</h2>
            </div>

            <!-- Total Referrals -->
            <div class="bg-white statics flex flex-col items-center justify-center p-3 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                <h2 class="text-lg font-semibold fb-normall statics-heading">{{ Auth::user()->referrals()->count() }}</h2>
                <h2 class="text-md primary-font-color-text font-normal fb-normal">Total Referrals</h2>
            </div>
        </div>

        <!-- Referral Card -->
        <div class="w-full text-left bg-white my-4 account-info-container p-4 rounded-2xl shadow-sm border border-gray-100">
            <div class="text-left px-3 flex items-center">
                <h2 class="text-lg primary-font-color-text py-2 font-semibold fb">Referral</h2>
            </div>
            <hr>
            <div class="px-4 py-2">
                <h4 class="text-lg primary-font-color-text py-2 font-normal fb">Email : {{ Auth::user()->email }}</h4>
                <h4 class="text-lg primary-font-color-text py-2 font-normal fb">Phone : {{ Auth::user()->phone }}</h4>

                <!-- Referral Code -->
                <div class="my-3">
                    <label class="label-title">Your Referral Code</label>
                    <div class="flex items-center">
                        <input type="text" readonly value="{{ Auth::user()->referral_code }}" class="form-input w-full" />
                        <button onclick="handleCopyClick(this, '{{ Auth::user()->referral_code }}', 'Copied!')" class="copy-btn small ml-2">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>

                <!-- Referral Link -->
                <div class="my-3">
                    <label class="label-title">Your Referral Link</label>
                    <div class="flex items-center">
                        <input type="text" readonly value="{{ Auth::user()->referral_link }}" class="form-input w-full" />
                        <button onclick="handleCopyClick(this, '{{ Auth::user()->referral_link }}', 'Copied!')" class="copy-btn small ml-2">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function handleCopyClick(button, text, message) {
    navigator.clipboard.writeText(text).then(() => {
        button.classList.add('copied');
        Swal.fire({
            toast: true,
            position: 'top',          // উপরের মাঝখানে
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 1500,
            background: '#ffffff',
            color: '{{ $settings->theme_color ?? "#3b82f6" }}',
            timerProgressBar: true,
            customClass: {
                popup: 'compact-toast'  // compact style
            },
            showClass: {
                popup: 'swal2-slide-down'
            },
            hideClass: {
                popup: 'swal2-slide-up'
            }
        });
        setTimeout(() => button.classList.remove('copied'), 800);
    });
}
</script>

<style>
/* Copy Button - Small */
.copy-btn {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 4px 6px;
    font-size: 12px;
    transition: all 0.3s ease;
    color: #374151;
}
.copy-btn:hover {
    background: #2563eb;
    color: #fff;
    transform: scale(1.1);
    box-shadow: 0 0 10px rgba(37,99,235,0.4);
}
.copy-btn.copied {
    background: #22c55e;
    color: #fff;
    transform: scale(1.15);
    box-shadow: 0 0 12px rgba(34,197,94,0.6);
}

/* Redeem Buttonyu */
.redeem-btn {
    background: linear-gradient(90deg, #3b82f6, #6366f1);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 5px 12px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 2px 5px rgba(99,102,241,0.3);
}
.redeem-btn:hover {
    transform: scale(1.07);
    background: linear-gradient(90deg, #2563eb, #4f46e5);
    box-shadow: 0 4px 10px rgba(79,70,229,0.4);
}

/* Claim Button */
.claim-btn {
    background: linear-gradient(90deg, #16a34a, #22c55e);
    color: #fff;
    border-radius: 10px;
    padding: 5px 12px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(34,197,94,0.3);
}
.claim-btn:hover {
    transform: scale(1.07);
    background: linear-gradient(90deg, #15803d, #16a34a);
    box-shadow: 0 4px 10px rgba(22,163,74,0.4);
}

/* Compact toast according to text length */
.compact-toast {
    font-size: 13px;
    padding: 6px 8px;    /* লেখা থেকে সামান্য বড় */
    width: auto;           /* লেখার দৈর্ঘ্য অনুযায়ী */
    max-width: 35%;        /* খুব বড় না হলে screen এর 90% পর্যন্ত */
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Slide down/up animation */
.swal2-slide-down {
    transform: translateY(-20px);
    opacity: 0;
    animation: slide-down 0.35s forwards;
}
.swal2-slide-up {
    animation: slide-up 0.35s forwards;
}

@keyframes slide-down {
    0% { transform: translateY(-20px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}
@keyframes slide-up {
    0% { transform: translateY(0); opacity: 1; }
    100% { transform: translateY(-20px); opacity: 0; }
}
</style>

@endsection