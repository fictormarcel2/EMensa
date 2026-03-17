@extends('examples.layouts.layout_werbeseite')

@section('title', 'Ihre E-Mensa')

@section('extracss')
    <link rel="stylesheet" href="/css/style.css">
@endsection

@section('begruessungtext')
    <div class="middle">
        <div class="hero-container">
            <img src="/img/mensa.png" alt="E-Mensa Banner" class="hero-image">
        </div>

        <h2>Bald gibt es Essen auch online ;)</h2>
        <p style="border: 2px solid #333; padding: 10px;">
            Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
        </p>
    </div>
@endsection

@section('main')
    <div class="middle">
        <section id="speisen">
            <h2>Köstlichkeiten, die Sie erwarten</h2>

            <p>
                <a href="/?sort=asc">Aufsteigend</a> |
                <a href="/?sort=desc">Absteigend</a>
            </p>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                <tr>
                    <th>Bild</th>
                    <th>Gericht</th>
                    <th>Preis intern</th>
                    <th>Preis extern</th>
                    <th>Allergene</th>
                </tr>
                </thead>
                <tbody>
                @forelse($gerichte as $gericht)
                    <tr>
                        {{-- HIER IST DER NEUE TEIL FÜR AUFGABE 2 MIT CHECK --}}
                        <td>
                            @php
                                $bildPfad = $_SERVER['DOCUMENT_ROOT'] . '/img/gerichte/' . ($gericht['bildname'] ?? '');
                                $bildVorhanden = !empty($gericht['bildname']) && file_exists($bildPfad);
                            @endphp

                            @if($bildVorhanden)
                                <img src="/img/gerichte/{{ $gericht['bildname'] }}"
                                     alt="{{ $gericht['name'] }}"
                                     style="width: 80px; height: auto; object-fit: cover;">
                            @else
                                <img src="/img/gerichte/00_image_missing.jpg"
                                     alt="Kein Bild"
                                     style="width: 80px; height: auto; opacity: 0.5;">
                            @endif
                        </td>
                        {{-- ENDE NEUER TEIL --}}

                        <td>{{ $gericht['name'] }}</td>
                        <td>{{ number_format($gericht['preisintern'], 2, ',', '.') }} €</td>
                        <td>{{ number_format($gericht['preisextern'], 2, ',', '.') }} €</td>
                        <td>
                            @if(!empty($gericht['allergene']))
                                @if(is_array($gericht['allergene']))
                                    {{ implode(', ', $gericht['allergene']) }}
                                @else
                                    {{ $gericht['allergene'] }}
                                @endif
                            @else
                                Keine
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        {{-- Colspan auf 5 erhöht, da wir jetzt 5 Spalten haben --}}
                        <td colspan="5">Keine Gerichte gefunden.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            <h3>Verwendete Allergene:</h3>
            <ul style="list-style: none;">
                @foreach($allergene as $allergen)
                    <li><strong>{{ $allergen['code'] }}:</strong> {{ $allergen['name'] }}</li>
                @endforeach
            </ul>
        </section>

        <section id="zahlen">
            <h2>E-Mensa in Zahlen</h2>
            <div class="stats-container">
                <div class="stat"><span>{{ $besucherCount ?? 0 }}</span> Besuche</div>
                <div class="stat"><span>{{ $anmeldungCount ?? 0 }}</span> Anmeldungen</div>
                <div class="stat"><span>{{ $gerichtCount ?? 0 }}</span> Speisen</div>
            </div>
        </section>

        <section id="kontakt">
            <h2>Interesse geweckt? Wir informieren Sie!</h2>

            @if(isset($anmeldungMessage) && $anmeldungMessage)
                <p style="color:green">{{ $anmeldungMessage }}</p>
            @endif
            @if(isset($emailExistMessage) && $emailExistMessage)
                <p style="color:red">{{ $emailExistMessage }}</p>
            @endif

            <form method="post" action="/#kontakt">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Ihr Name:</label>
                        <input type="text" id="name" name="name" placeholder="Vorname" value="{{ $name ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label for="email">Ihre E-Mail:</label>
                        <input type="email" id="email" name="email" value="{{ $email ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label for="sprache">Newsletter bitte in:</label>
                        <select id="sprache" name="sprache">
                            <option value="deutsch">Deutsch</option>
                            <option value="englisch">Englisch</option>
                        </select>
                    </div>
                </div>
                <div class="form-bottom">
                    <div class="checkbox-group">
                        <input type="checkbox" id="datenschutz" name="datenschutz" value="1">
                        <label for="datenschutz">Datenschutz akzeptieren</label>
                    </div>
                    <button type="submit" name="newsletter_submit">Anmelden</button>
                </div>
            </form>
        </section>

        <br>

        <section id="wichtig">
            <h2>Das ist uns wichtig</h2>
            <ul style="margin-left: 100px">
                <li>Beste frische saisonale Zutaten</li>
                <li>Ausgewogene abwechslungsreiche Gerichte</li>
                <li>Sauberkeit</li>
            </ul>
        </section>

        <h2 class="center-text">Wir freuen uns auf Ihren Besuch!</h2>

        <div style="text-align: center;">
            <a href="/wunschgericht" style="font-weight:bold; color:#57b7c3;">Wunschgericht melden</a>
        </div>

    </div>
@endsection