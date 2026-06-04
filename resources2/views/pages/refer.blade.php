@extends('layout.master')

@section('title', 'Refer & Earn')

@section('content')
@php
    $user = Auth::user();
    $totalCoins = $user->coins ?? 0;
    
    // Get coin to taka rate from settings (new format: just the taka amount)
    $takaAmount = \App\Models\Setting::get('coin_to_taka') ?? '7';
    
    // Handle old format (1000=7) if exists
    if (strpos($takaAmount, '=') !== false) {
        [, $takaAmount] = explode('=', $takaAmount);
    }
    
    $takaPerUnit = (float) $takaAmount;
    $coinsPerTaka = 1000; // Always 1000 coins base
    
    // Calculate redeem options
    $redeemOptions = [];
    if ($totalCoins >= 1000) {
        for ($i = 1000; $i <= $totalCoins; $i += 1000) {
            $takaAmount = floor($i / $coinsPerTaka) * $takaPerUnit;
            $redeemOptions[] = [
                'coins' => $i,
                'taka' => $takaAmount
            ];
        }
    }
@endphp

<div class="max-w-md mx-auto p-4">
    {{-- 💰 Your Wallet --}}
    <div class="wallet-section" style="background: {{ $settings->theme_color }};">
        <h2 class="wallet-title">Your Wallet</h2>
        
        <div class="wallet-amount flex items-center">
            <img src="{{ asset('assets/template/images/coins.svg') }}" class="w-8 h-8 mr-2" alt="Coins">
            <span class="coin-count text-lg font-semibold">{{ number_format($totalCoins) }}</span>
        </div>

        {{-- 💸 Redeem Section --}}
        <div class="redeem-box">
            <p class="redeem-limit">Redeem Coin Limit: <b>{{ $coinsPerTaka }} Coins = {{ $takaPerUnit }}৳</b></p> 
            
            <button type="button" id="redeemBtn" class="redeem-btn px-4 py-2 rounded-md text-white font-semibold">
                Redeem
            </button>
        </div>
    </div>

    {{-- 🎉 Invite Section --}}
    <div class="invite-section p-4 rounded-xl shadow-sm border mb-4" style="border-color: {{ $settings->theme_color ?? '#3b82f6' }};">
        <h3 class="section-title text-xl font-bold mb-4 flex items-center justify-center" style="color: {{ $settings->theme_color ?? '#3b82f6' }};">
            <i class="fas fa-user-plus mr-2"></i>
            রেফার করুন এবং উপার্জন করুন
        </h3>
        
        <div class="referral-rules text-sm text-gray-700 text-left mb-5 space-y-2 p-3 border rounded-lg bg-gray-50">
            <p>
                <b style="color: {{ $settings->theme_color ?? '#3b82f6' }};">১.</b> রেফার কোড কপি করে বন্ধুদেরকে শেয়ার করুন!
            </p>
            <p>
                <b style="color: {{ $settings->theme_color ?? '#3b82f6' }};">২.</b> যদি আপনার রেফারে কেউ অ্যাকাউন্ট করে, এবং উনারা যত টাকা দিয়ে প্রোডাক্ট কেনে, আপনি তত কয়েন পাবেন!
            </p>
        </div>

        <div class="referral-code flex items-center justify-between" id="referralCode" style="border-color: {{ $settings->theme_color ?? '#3b82f6' }};">
            <span class="truncate mr-3 text-base font-medium">{{ $user->referral_link }}</span>
            <button id="copyButton" class="copy-btn text-white flex items-center" 
                    style="background: {{ $settings->theme_color ?? '#3b82f6' }};">
                <i class="fas fa-copy mr-1"></i>
                <span>কপি করুন</span>
            </button>
        </div>

        <div class="divider my-4 text-center text-gray-400 text-xs">
            <span>অথবা শেয়ার করুন</span>
        </div>

        <div class="social-icons flex gap-3 justify-center mb-2 text-sm">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($user->referral_link) }}" target="_blank" class="social-icon facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://wa.me/?text={{ urlencode($user->referral_link) }}" target="_blank" class="social-icon whatsapp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="mailto:?subject=Join%20me&body={{ urlencode($user->referral_link) }}" class="social-icon gmail">
                <i class="fas fa-envelope"></i>
            </a>
            <a href="https://m.me/share?link={{ urlencode($user->referral_link) }}" target="_blank" class="social-icon messenger">
                <i class="fab fa-facebook-messenger"></i>
            </a>
            <a href="https://imo.im" target="_blank" class="social-icon imo">
                <i class="fas fa-comment-dots"></i>
            </a>
        </div>
    </div>
</div>

{{-- Redeem Modal --}}
<div id="redeemModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Redeem Coins</h3>
            <button type="button" class="modal-close" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            @if($totalCoins < 1000)
                <div class="alert-message">
                    <i class="fas fa-info-circle"></i>
                    <p>You need at least <strong>{{ $coinsPerTaka }} coins</strong> to redeem.</p>
                    <p class="mt-2">Current coins: <strong>{{ number_format($totalCoins) }}</strong></p>
                </div>
            @else
                <p class="mb-4 text-gray-600">Select how many coins you want to redeem:</p>
                <div class="redeem-options">
                    @foreach($redeemOptions as $option)
                        <button type="button" class="redeem-option-btn" 
                                data-coins="{{ $option['coins'] }}" 
                                data-taka="{{ $option['taka'] }}">
                            <div class="option-amount">
                                <span class="coins">{{ number_format($option['coins']) }} Coins</span>
                                <span class="arrow">→</span>
                                <span class="taka">{{ number_format($option['taka'], 2) }}৳</span>
                            </div>
                        </button>
                    @endforeach
                </div>
                <form id="redeemForm" action="{{ route('referral.redeem') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="coins" id="selectedCoins" value="">
                    <div class="selected-info" id="selectedInfo" style="display: none;">
                        <p class="text-sm text-gray-600 mb-2">You will receive:</p>
                        <p class="text-2xl font-bold" style="color: {{ $settings->theme_color ?? '#3b82f6' }};" id="takaAmount">0৳</p>
                    </div>
                    <button type="submit" class="confirm-redeem-btn" id="confirmRedeemBtn" disabled>
                        Confirm Redeem
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<style>
/* Base Styles */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%); min-height: 100vh; }

/* Wallet Section */
.wallet-section {
    color: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    text-align: center; margin-bottom: 25px; position: relative; overflow: hidden;
}
.wallet-section::before {
    content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%); pointer-events: none;
}
.wallet-title { font-size: 20px; font-weight: 700; margin-bottom: 15px; position: relative; color: white; }
.wallet-amount { display: flex; align-items: center; justify-content: center; margin: 15px 0; position: relative; }
.coin-count { font-size: 42px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2); color: white; }

/* Redeem Box */
.redeem-box {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.3);
}
.redeem-limit { 
    font-size: 14px; 
    font-weight: 500; 
    margin-bottom: 10px;
    opacity: 0.9; 
    color: rgba(255, 255, 255, 0.9); 
}
.redeem-btn {
    background: rgba(255, 255, 255, 0.2); color: white; border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 10px 30px; border-radius: 50px; font-weight: 600; font-size: 16px; cursor: pointer;
    transition: all 0.3s ease; position: relative; backdrop-filter: blur(10px);
}
.redeem-btn:hover:not(:disabled) { background: rgba(255, 255, 255, 0.3); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
.redeem-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Invite Section */
.invite-section {
    background: white; border-radius: 20px; padding: 25px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px; text-align: center;
}
.section-title { font-size: 22px; font-weight: 700; color: #2c3e50; margin-bottom: 8px; }
.referral-rules {
    border: 1px dashed #ddd;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.referral-code {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 10px 15px; 
    margin: 15px 0;
    font-family: 'Courier New', monospace;
    word-break: break-all;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid #e0e0e0;
}
.copy-btn {
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: opacity 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.copy-btn:hover { opacity: 0.9; }
.divider { text-align: center; margin: 20px 0; color: #bdc3c7; font-weight: 600; position: relative; }
.divider::before, .divider::after { content: ''; position: absolute; top: 50%; width: 42%; height: 1px; background: #ecf0f1; }
.divider::before { left: 0; }
.divider::after { right: 0; }
.divider span { background: white; padding: 0 15px; position: relative; z-index: 1; }
.social-icons { display: flex; justify-content: center; gap: 15px; margin-top: 10px; }
.social-icon {
    width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center;
    justify-content: center; color: white; font-size: 18px; cursor: pointer;
    transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); text-decoration: none;
}
.social-icon:hover { opacity: 0.9; }
.facebook { background: linear-gradient(45deg, #3b5998, #4c70ba); }
.whatsapp { background: linear-gradient(45deg, #25D366, #20b858); }
.gmail { background: linear-gradient(45deg, #DB4437, #e57373); }
.messenger { background: linear-gradient(45deg, #0084FF, #00a8ff); }
.imo { background: linear-gradient(45deg, #1DD2FD, #4ae0ff); }

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.modal-overlay.active {
    display: flex;
    animation: fadeIn 0.3s ease forwards;
}
.modal-overlay.closing {
    animation: fadeOut 0.3s ease forwards;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}
.modal-content {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.9);
    transition: transform 0.3s ease;
}
.modal-overlay.active .modal-content {
    animation: slideUp 0.3s ease forwards;
}
.modal-overlay.closing .modal-content {
    animation: slideDown 0.3s ease forwards;
}
@keyframes slideUp {
    from { transform: scale(0.9) translateY(20px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}
@keyframes slideDown {
    from { transform: scale(1) translateY(0); opacity: 1; }
    to { transform: scale(0.9) translateY(20px); opacity: 0; }
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}
.modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}
.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #6b7280;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}
.modal-close:hover {
    background: #f3f4f6;
    color: #1f2937;
}
.modal-body {
    padding: 20px;
}
.alert-message {
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.alert-message i {
    font-size: 48px;
    color: #f59e0b;
    margin-bottom: 10px;
}
.alert-message p {
    color: #92400e;
    font-size: 16px;
    margin: 5px 0;
}
.redeem-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.redeem-option-btn {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
    width: 100%;
}
.redeem-option-btn:hover {
    border-color: {{ $settings->theme_color ?? '#3b82f6' }};
    background: #f0f9ff;
    transform: translateX(5px);
}
.redeem-option-btn.selected {
    border-color: {{ $settings->theme_color ?? '#3b82f6' }};
    background: {{ $settings->theme_color ?? '#3b82f6' }}15;
}
.option-amount {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 16px;
    font-weight: 600;
}
.option-amount .coins {
    color: #6b7280;
}
.redeem-option-btn.selected .option-amount .coins {
    color: {{ $settings->theme_color ?? '#3b82f6' }};
}
.option-amount .arrow {
    color: #9ca3af;
    margin: 0 10px;
}
.option-amount .taka {
    color: #059669;
    font-size: 18px;
}
.selected-info {
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
}
.confirm-redeem-btn {
    width: 100%;
    padding: 14px;
    background: {{ $settings->theme_color ?? '#3b82f6' }};
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.confirm-redeem-btn:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.confirm-redeem-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 480px) {
    .max-w-md { padding: 15px; }
    .wallet-section, .invite-section { padding: 20px; }
    .coin-count { font-size: 36px; }
    .social-icon { width: 40px; height: 40px; font-size: 16px; }
    .modal-content { width: 95%; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const redeemBtn = document.getElementById('redeemBtn');
    const redeemModal = document.getElementById('redeemModal');
    const closeModal = document.getElementById('closeModal');
    const redeemOptions = document.querySelectorAll('.redeem-option-btn');
    const selectedCoinsInput = document.getElementById('selectedCoins');
    const selectedInfo = document.getElementById('selectedInfo');
    const takaAmount = document.getElementById('takaAmount');
    const confirmRedeemBtn = document.getElementById('confirmRedeemBtn');
    const redeemForm = document.getElementById('redeemForm');

    // Open modal
    if (redeemBtn) {
        redeemBtn.addEventListener('click', function() {
            redeemModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    // Close modal
    function closeModalFunc() {
        redeemModal.classList.add('closing');
        setTimeout(() => {
            redeemModal.classList.remove('active', 'closing');
            document.body.style.overflow = '';
        }, 300);
    }

    if (closeModal) {
        closeModal.addEventListener('click', closeModalFunc);
    }

    redeemModal.addEventListener('click', function(e) {
        if (e.target === redeemModal) {
            closeModalFunc();
        }
    });

    // Select redeem option
    redeemOptions.forEach(btn => {
        btn.addEventListener('click', function() {
            redeemOptions.forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            
            const coins = this.dataset.coins;
            const taka = this.dataset.taka;
            
            selectedCoinsInput.value = coins;
            takaAmount.textContent = parseFloat(taka).toFixed(2) + '৳';
            selectedInfo.style.display = 'block';
            confirmRedeemBtn.disabled = false;
        });
    });

    // Form submission
    if (redeemForm) {
        redeemForm.addEventListener('submit', function(e) {
            if (!selectedCoinsInput.value) {
                e.preventDefault();
                alert('Please select an amount to redeem');
                return false;
            }
            confirmRedeemBtn.disabled = true;
            confirmRedeemBtn.textContent = 'Processing...';
        });
    }

    // Copy referral link
    const copyButton = document.getElementById('copyButton');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const referralLink = '{{ $user->referral_link }}';
            const originalHTML = this.innerHTML;
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> অপেক্ষা করুন';
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(referralLink).then(() => {
                    this.innerHTML = '<i class="fas fa-check"></i> সফল হয়েছে!';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }, 1500);
                }).catch(() => {
                    this.innerHTML = '<i class="fas fa-times"></i> ব্যর্থ!';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }, 1500);
                });
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = referralLink;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    this.innerHTML = '<i class="fas fa-check"></i> সফল হয়েছে!';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }, 1500);
                } catch (err) {
                    this.innerHTML = '<i class="fas fa-times"></i> ব্যর্থ!';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }, 1500);
                } finally {
                    document.body.removeChild(textArea);
                }
            }
        });
    }
});
</script>
@endsection
