@extends('examples.layouts.layout_werbeseite') @section('main')
    <div style="width: 300px; margin: 50px auto; font-family: Arial;">
        <h2>Anmeldung</h2>

        {{-- Fehlermeldung anzeigen, wenn vorhanden --}}
        @if(!empty($msg))
            <div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 10px;">
                {{ $msg }}
            </div>
        @endif

        <form action="/anmeldung_verfizieren" method="post">
            <div style="margin-bottom: 10px;">
                <label for="email">E-Mail:</label><br>
                <input type="email" id="email" name="email" required style="width: 100%;">
            </div>

            <div style="margin-bottom: 10px;">
                <label for="passwort">Passwort:</label><br>
                <input type="password" id="passwort" name="passwort" required style="width: 100%;">
            </div>

            <button type="submit" style="background-color: #57b7c3; color: white; border: none; padding: 10px; width: 100%; cursor: pointer;">
                Anmelden
            </button>
        </form>
    </div>
@endsection