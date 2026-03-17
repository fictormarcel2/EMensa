@extends('examples.layouts.layout_werbeseite')

@section('header')
    <div class="head">
        <div class="img"><img src="/img/logo.png" alt="E-Mensa Logo" id="logo">
            <span id="links">
                    <a href="{{'/'}}" class="link">zurück</a>
            </span>
        </div>
    </div>
    <hr>
@endsection

@section('extracss')
    <link rel="stylesheet" href="css/wunschgericht.css">
@endsection

@section('begruessungtext')
    <h1>Sagen Sie uns Ihr Wunschgericht!</h1>
@endsection

@section('main')
    <div class="wunschForm">
        <form method="POST">
            <label for="name">Name (optional):</label>
            <input type="text" name="name" id="name">
            <br>
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" required>
            <br>
            <label for="gerichtName">Name des Gerichts:</label>
            <input type="text" name="gerichtName" id="gerichtName" required>
            <br>
            <label for="beschreibung">Beschreibung des Gerichts:</label>
            <input type="text" name="beschreibung" id="beschreibung" required>
            <br>
            <input type="submit" name="submit">
        </form>
    </div>
@endsection