@extends("examples.layouts.layout")

@section("content")
    <div>
        @if (isset($data['error']))
            <h1>Problem beim Verbinden mit der Datenbank</h1>
            <p>Fehlermeldung {{$data['error']}}</p>
            <pre> {{$data['beschreibung']}}</pre>
        @else

            <article>
                <h1>Daten aus der Tabelle: Gerichte</h1>
                <p>Der <abbr title="DemoController.php">Controller</abbr> inkludiert das benötigte Model (gerichte.php
                    in diesem Fall)
                    und ruft die benötigte Funktion <code>db_gerichte_select_all()</code> zum Laden der Daten auf.</p>
                <ul class="list-unstyled">
                    @forelse($data as $a)
                        <li>{{$a['name']}}</li>
                    @empty
                        <li>Keine Daten vorhanden.</li>
                    @endforelse
                </ul>
            </article>
        @endif
    </div>
@endsection