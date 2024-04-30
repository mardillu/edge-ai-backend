<!DOCTYPE html>
<html>
<head>
    <title>One-Time Password</title>
    <!-- CSS only -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>One-Time Password (OTP)</h2>
            </div>
            <div class="card-body">
                <h4>Hello there,</h4>
                <p>Your one-time password for account verification is:</p>
                <h3 class="text-center text-primary">{{ $user->otp }}</h3>
                <p>Please use this OTP to complete your account verification process. This OTP is valid for 10 minutes only.</p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
            <div class="card-footer text-muted">
                <small>© 2023, Your Company Name</small>
            </div>
        </div>
    </div>
</body>
</html>
