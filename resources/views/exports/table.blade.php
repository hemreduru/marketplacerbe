<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        /* DejaVu Sans — dompdf'te Türkçe karakter desteği */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
        h3 { margin: 0 0 4px; }
        .period { color: #777; font-size: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f3f3f3; font-weight: bold; }
    </style>
</head>
<body>
    <h3>{{ $title }}</h3>
    @isset($period)<div class="period">{{ $period }}</div>@endisset
    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
