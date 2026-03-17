<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="/css/style.css">
    @yield('extracss')
</head>
<body>
<header>
    @section('header')
        <div class="head">
            <div class="img">
                <img src="/img/logo.png" alt="E-Mensa Logo" id="logo">
                <nav id="links">
                    <a href="#ankuendigung" class="link">Ankündigung</a>
                    <a href="#speisen" class="link">Speisen</a>
                    <a href="#zahlen" class="link">Zahlen</a>
                    <a href="#kontakt" class="link">Kontakt</a>
                    <a href="#wichtig" class="link">Wichtig für uns</a>

                    {{-- Task 1.7  Login Status Display --}}
                    @if(isset($_SESSION['login_ok']) && $_SESSION['login_ok'])
                        <span class="link" style="color: #57b7c3; cursor: default; margin-right:50px">
                            Angemeldet als {{ $_SESSION['user_name'] }}
                        </span>
                        <a href="/abmeldung" class="link" style="color: red;">Abmelden</a>
                    @else
                        <a href="/anmeldung" class="link">Anmelden</a>
                    @endif

                </nav>
            </div>
        </div>
        <hr>
    @show
</header>

<main>
    <div class="data">
        @yield('begruessungtext')
        @yield('main')
    </div>
</main>

<footer>
    @section('footer')
        <hr>
        <p id="fut">
            &copy; E-Mensa GmbH &nbsp; | &nbsp;
            Yassir AIT SI ; Fictor Marcelbrian &nbsp; | &nbsp;
            <a href="/impressum" id="imp">Impressum</a>
        </p>
    @show
</footer>
</body>
</html>