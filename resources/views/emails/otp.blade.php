<!DOCTYPE html>
<html>
<head>
    <title>Account email verification</title>
    <!-- CSS only -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
                <h4>Hello there,</h4>
                <p>thank you for your interest in using Diaload to super-charge your testing work-flow. To continue with the signup, we need to comfirm your identity.</p>
                <p>Please use the following verification code complete your account signup process:</p>
                <h2 class="text-center text-primary secondary">{{ $user->otp }}</h2>
                <p>This code is valid for 10 minutes only. If it expires, request a new one <a href="https://diaload.com/verify">here</a></p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>&nbsp;</p>
                <p>Best Regards,<br>Diaload Team</br></p>

            </div>
            <div class="card-footer text-muted">
                <small>© 2023, Your Company Name</small>
            </div>
        </div>
    </div>
</body>
</html>
