<!DOCTYPE html>
<html lang="de">
<head><title>Gerichte > 2€</title></head>
<body>
@if(empty($data))
    <p>Es sind keine Gerichte vorhanden.</p>
@else
    <table border="1">
        <thead>
        <tr>
            <th>Name</th>
            <th>Preis Intern</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data as $dat)
            {{-- If you didn't filter in SQL, we filter here --}}
            @if($dat['preisintern'] > 2)
                <tr>
                    <td>{{$dat['name']}}</td>
                    <td>{{number_format($dat['preisintern'], 2)}} €</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
@endif
</body>
</html>