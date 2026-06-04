<!DOCTYPE html>
<html>
<head>
    <title>🎉 Welcome to Our Website</title>
</head>
<body>
    <h2>Hello, {{ $user->name }}!</h2>
    <p>Welcome to our website! We are glad you have joined us.</p>
    <p>Your account has been successfully created. Now you can enjoy all the features of our website.</p>
    <p>You can access you account from <a herf="{{ route('account') }}">Here</a></p>
    <br>
    <p>ThankYou</p>
    <p><strong>{{ $details->site_name }}</strong></p>
</body>
</html>