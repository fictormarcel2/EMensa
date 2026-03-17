<html>
<ul>
    @foreach ($kategorie as $kat)
        <li style="{{ $loop->even ? 'font-weight:bold;' : '' }}">
        {{$kat['name']}}
        </li>
    @endforeach
</ul>
</html>