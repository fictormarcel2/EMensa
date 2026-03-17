<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset=utf-8">
    <title>@yield('title')</title>
</head>
<body>
<header>
    @yield('header')
</header>
<main>
    @yield('main')
</main>
<footer>
    @section('footer')
        <p>Test Fictor und Yassir</p>
    @show
</footer>
</body>
</html>