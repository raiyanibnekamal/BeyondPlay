<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5;">
    <h1>Reset your password</h1>
    <p>Hi {{ $user->username }},</p>
    <p>Click the link below to reset your BeyondPlay account password. This link expires in 60 minutes.</p>
    <p><a href="{{ $resetUrl }}">Reset password</a></p>
    <p>If you did not request a reset, you can ignore this email.</p>
    <p>— BeyondPlay Esports</p>
</body>
</html>
