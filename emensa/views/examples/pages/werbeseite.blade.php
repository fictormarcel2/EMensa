@extends('examples.layouts.layout_werbeseite')

@section('title')
    Ihre E-Mensa
@endsection

@section('begruessungtext')
    <h2>Bald gibt es auch Online ;)</h2>
    <p id="paragraph">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium atque fugit nobis sint
        soluta. A adipisci autem ducimus, expedita iste officia sint. Mollitia neque nulla placeat sunt! Delectus, ea,
        optio!
        Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aspernatur beatae doloremque doloribus? Aliquid
        asperiores perferendis quidem repellendus unde veritatis? Accusantium cupiditate doloribus ea error itaque
        laudantium nemo quos repellendus, sequi?
        <br>
        Mauris in nibh eget quam eleifend pellentesque quis non urna. Phasellus facilisis tincidunt dapibus. Praesent
        pharetra blandit eleifend. Quisque scelerisque, tortor at mollis laoreet, tortor enim semper est, venenatis
        porta mauris libero nec nisl.
        Curabitur lobortis ut purus id dictum. Vestibulum faucibus lacus nulla, non suscipit arcu viverra sodales. Nulla
        mi nunc, euismod ac tristique eu, eleifend eget nunc.
    </p>
@endsection

@section('main')
    <h2>Köstlichen, die Sie erwarten</h2>
    <a href="?asc={{$order}}"> Auf/Ab-steigen? </a>
    <table class="table">
        <thead>
        <tr>
            <th></th>
            <th>Preis intern</th>
            <th>Preis extern</th>
            <th>Allergen</th>
        </tr>
        </thead>
        <tbody>
        @foreach($gerichte as $gericht)
            <tr>
                <td>{{$gericht['name']}}</td>
                <td>{{number_format($gericht['preisintern'], 2)}}</td>
                <td>{{number_format($gericht['preisextern'], 2)}}</td>
                <td>{{$gericht['allergene']}}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td>...</td>
            <td>...</td>
            <td>...</td>
            <td>...</td>
        </tr>
        </tfoot>
    </table>
    <br>
    <table class='table'>
        <thead>
        <tr>
            <th>code</th>
            <th>name</th>
            <th>type</th>
        </tr>
        </thead>
        <tbody>
        @foreach($allergene as $allergen)
            <tr>
                <td>{{$allergen['code']}}</td>
                <td>{{$allergen['name']}}</td>
                <td>{{$allergen['typ']}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <br>
    <a href="wunschgericht">Du hast einen Wunschgericht?</a>
    <h2>E-Mensa in Zahlen</h2>
    <div class="E-Mensa">
        <div class="x">{{$besucherCount}} Besuche</div>
        <div class="y">{{$anmeldungCount}}</div>
        <div class="anmeldung">Anmeldungen zum Newsletter</div>
        <div class="z">{{$gerichtCount}} Speisen</div>
    </div>
    <br>
    <h2>Interesse geweckt? Wir informieren Sie</h2>
    <form method="post">
        <div class="form">
            <div class="name">
                <label for="vorname">Ihr Name:</label>
                <br>
                <input type="text" id="vorname" name="vor" placeholder="Name"
                       value="@if(empty($anmeldungMessage)){{$name}}@endif" required>
            </div>
            <div class="email">
                <label for="mail">Ihre E-Mail</label>
                <br>
                <input type="email" id="mail" name="email" placeholder="Email"
                       value="@if(empty($anmeldungMessage)){{$email}}@endif" required>
            </div>
            <div class="newsletter">
                <label for="sprache">Newsletter bitte im</label>
                <br>
                <select id="sprache" name="sprache">
                    <option value="deutsch" selected>Deutsch</option>
                    <option value="englisch">Englisch</option>
                </select>
            </div>
        </div>
        <div class="daten">
            <div class="datenschutz"><input type="checkbox" id="schutz" required><label for="schutz">Den
                    Datenschutzbetimmungen stimme ich zu</label></div>
            <div class="news"><input type="submit" name="submit" value="zum Newsletter anmelden"></div>
        </div>
    </form>
    <div class="errorMessage">
        {{$emailExistMessage}}
        {{$nameInvalidMessage}}
        {{$emailInvalidMessage}}
        <p style="color:green">{{$anmeldungMessage}}</p>
    </div>
    <br>
    <h2>Das ist uns wichtig</h2>
    <div class="uns">
        <ul>
            <li>Beste frische saisonale Zutaten</li>
            <li>Ausgewagene obwechslungsreiche Gerichte</li>
            <li>Sauberkeit</li>
        </ul>
    </div>
    <h2 style="text-align: center">Wir freuen uns auf Ihren Besuch</h2>
    </div>
@endsection