@extends('layout.master')
@section('title')
Register
@endsection
@section('content')
<div class="login">
  <div class="secondary-section">
    <div class="login-form mx-auto form-container-custom">
      <div class="w-auto px-0 md:px-3 pt-5 pb-1">
        <h1 class="text-2xl font-bold"> Register</h1>
        <div class="text-center my-3">
          <div class="social-login">
            
            {{-- 1. LOGIN WITH GOOGLE BUTTON --}}
            <a href="javascript:void(0)" type="button" class="google-blue-btn" id="googleBlueBtn"> 
              <span class="google-logo-wrapper mr-2">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" class="google-icon">
                  <g fill-rule="evenodd">
                    <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"></path>
                    <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.1.83-.64 2.08-1.84 2.92l2.84 2.2c-1.7-1.57 2.68-3.88 2.68-6.62z" fill="#4285F4"></path>
                    <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"></path>
                    <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.84-2.2c-.76.53-1.78.9-3.12.9-2.38 0-4.4-1.57-5.12-3.74L.97 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"></path>
                    <path fill="none" d="M0 0h18v18H0z"></path>
                  </g>
                </svg>
              </span>
              Login with Google
            </a>

            {{-- 2. CONNECTED GOOGLE BUTTON (Initially hidden) --}}
            <a href="{{ url('auth/redirect') }}?ref={{ request()->get('ref') }}" type="button" class="
              focus:outline-none focus-visible:outline-0 disabled:cursor-not-allowed disabled:opacity-75 flex-shrink-0
              font-medium rounded-md text-sm gap-x-1.5 px-2.5 py-2.5 shadow-sm ring-1 ring-inset ring-gray-300
              dark:ring-gray-700 text-gray-900 dark:text-white bg-white hover:bg-gray-50 disabled:bg-white
              dark:bg-gray-900 dark:hover:bg-gray-800/50 dark:disabled:bg-gray-900 focus-visible:ring-2
              focus-visible:ring-primary-500 dark:focus-visible:ring-primary-400 inline-flex items-center 
              
              google-connected-btn-custom
              hidden" id="googleConnectedBtn"> 
              
              <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" class="mr-2 google-icon">
                <g fill-rule="evenodd">
                  <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"></path>
                  <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.1.83-.64 2.08-1.84 2.92l2.84 2.2c-1.7-1.57 2.68-3.88 2.68-6.62z" fill="#4285F4"></path>
                  <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"></path>
                  <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.84-2.2c-.76.53-1.78.9-3.12.9-2.38 0-4.4-1.57-5.12-3.74L.97 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"></path>
                  <path fill="none" d="M0 0h18v18H0z"></path>
                </g>
              </svg>
              
              <span class="btn-text">Connected Google</span>
            </a>
          </div>
          
          {{-- UPDATED OR SEPARATOR DESIGN --}}
          <div class="flex justify-between items-center pt-0 mb-3 or-separator-final">
            <hr class="w-full hr-separator-final-thick">
            <h1 class="font-primary text-sm text-center or-text-final text-black"> Or sign up with credentials</h1>
            <hr class="w-full hr-separator-final-thick">
          </div>
          {{-- END UPDATED OR SEPARATOR DESIGN --}}
        </div>

        <form method="POST" action="{{ route('signup') }}">
          @csrf
          {{-- Name --}}
          <div class="relative py-1">
            <label class="label-title">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Name"
              class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900">
            @error('name')<p style="color:red;">{{ $message }}</p>@enderror
          </div>

          {{-- Phone --}}
          <div class="relative py-1">
            <label class="label-title">Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone"
              class="form-input py-1 block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900">
            @error('phone')<p style="color:red;">{{ $message }}</p>@enderror
          </div>

          {{-- Email --}}
          <div class="relative py-1">
            <label class="label-title">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email"
              class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900">
            @error('email')<p style="color:red;">{{ $message }}</p>@enderror
          </div>

          {{-- Password --}}
          <div class="relative py-1">
            <label class="label-title">Password</label>
            <input type="password" name="password" placeholder="Password"
              class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900">
            @error('password')<p style="color:red;">{{ $message }}</p>@enderror
          </div>

          {{-- Confirm Password --}}
          <div class="relative py-1">
            <label class="label-title">Confirm Password</label>
            <input type="password" name="password_confirmation" placeholder="Confirm Password"
              class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-red-400 focus:ring-2 focus:ring-black-500 dark:focus:ring-black-400">
            @error('password_confirmation')<p style="color:red;">{{ $message }}</p>@enderror
          </div>

          {{-- Referral --}}
          <div class="relative py-1">
            <label class="label-title">Referral Code (Optional)</label>
            <input type="text" name="referral_code" value="{{ old('referral_code', request()->get('ref')) }}" placeholder="Referral Code"
              class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900">
          </div>

          {{-- Submit --}}
          <div class="text-center mt-4">
            <button type="submit"
              class="justify-center focus:outline-none disabled:cursor-not-allowed disabled:opacity-75 flex-shrink-0 font-medium rounded-md text-sm gap-x-1.5 px-2.5 py-2.5 shadow-sm text-white dark:text-gray-900 bg-pink-500 hover:bg-pink-600 w-full">Register</button>
          </div>
        </form>

        {{-- Already Member --}}
        <div class="mb-5 text-center subtitle-4 font-primary font-normal mt-3">
          Already member? <a href="{{ url('login') }}" class="text-pink-500 font-primary font-normal">Login</a> Now
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* অতিরিক্ত স্টাইল */
.form-container-custom {
    margin-top: 0px; 
}
.hidden {
    display: none !important;
}

/* 🌟 LOGIN WITH GOOGLE (টেক্সট রঙ: সাদা) 🌟 */
.google-blue-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 6px 16px; 
    background: #2563eb; 
    border: 2px solid #2563eb;
    border-radius: 6px;
    color: white; /* সাধারণ টেক্সট রঙ: সাদা */
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    text-decoration: none;
    margin-bottom: 10px;
    position: relative;
    cursor: pointer;
}

/* 🌟 LOGIN WITH GOOGLE হোভার করলে (টেক্সট রঙ: সাদা) 🌟 */
.google-blue-btn:hover {
    background: #1e40af; 
    border-color: #1e40af;
    color: white; /* হোভার করার সময় টেক্সট রঙ: সাদা */
}

/* গুগল লোগো wrapper */
.google-logo-wrapper {
    background-color: white;
    border-radius: 4px; 
    padding: 6px; 
    display: flex;
    align-items: center;
    justify-content: center;
}

/* কানেক্টেড বাটন */
.google-connected-btn-custom {
    max-width: 80%; 
    margin: 0 auto 10px auto; 
}

/* 🌟 OR এর দাগ (রঙ: কালো) 🌟 */
.hr-separator-final-thick {
    border: none; 
    border-top: 2px solid #000; /* দাগের রঙ: কালো */
    width: 45%; 
    margin: 0;
}

.or-separator-final {
    margin-top: 10px; 
    margin-bottom: 15px; 
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 🌟 OR এর টেক্সট (রঙ: কালো) 🌟 */
.or-text-final {
    padding: 0 10px; 
    font-weight: bold; 
    flex-shrink: 0;
    color: #000; /* OR লেখাটির রঙ: কালো */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const blueBtn = document.getElementById('googleBlueBtn');
    const connectedBtn = document.getElementById('googleConnectedBtn');
    
    // যখন নীল বাটনে ক্লিক করা হবে
    blueBtn.addEventListener('click', function(e) {
        e.preventDefault(); 
        
        // 1. Hide the Blue Button
        blueBtn.classList.add('hidden');
        
        // 2. Show the Connected Google Button
        connectedBtn.classList.remove('hidden');

        // 3. Immediately trigger the navigation after a small delay
        setTimeout(() => {
            // Register-এর জন্য ref প্যারামিটার সহ URL এ রিডাইরেক্ট হবে
            window.location.href = "{{ url('auth/redirect') }}?ref={{ request()->get('ref') }}";
        }, 100); 
    });
});
</script>
@endsection