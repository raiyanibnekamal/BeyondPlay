<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Price Drop</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5;">
    <h1>Price drop alert</h1>
    <p>Hi {{ $user->username }},</p>
    <p><strong>{{ $product->name }}</strong> is now on sale!</p>
    <p>Was <s>{{ number_format($oldPrice, 2) }}</s> — now <strong>{{ number_format($newPrice, 2) }}</strong></p>
    <p><a href="{{ rtrim(config('app.frontend_url', config('app.url')), '/') }}/shop-details.html?slug={{ $product->slug }}">View product</a></p>
    <p>— BeyondPlay Esports</p>
</body>
</html>
