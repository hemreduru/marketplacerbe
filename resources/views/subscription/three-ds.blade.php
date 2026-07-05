<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>3D Secure · {{ config('app.name', 'Cirotik') }}</title>
</head>
<body>
    {{-- iyzico'dan gelen 3DS banka doğrulama formu; otomatik submit olur --}}
    {!! $html !!}
</body>
</html>
