<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/gericht.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/../models/kategorie.php');

class ExampleController
{
    public function m4_7a_queryparameter(RequestData $rd) {
        $name = $rd->query['name'] ?? 'Fictor Yassir';
        return view('examples.m4_7a_queryparameter', ["name" => $name]);
    }

    public function m4_7b_kategorie(RequestData $rd) {
        $kategorie = db_kategorie_select_all_sorted();
        return view('examples.m4_7b_kategorie', ['kategorie' => $kategorie]);
    }

    public function m4_7c_gerichte(RequestData $rd) {
        $data = getGerichtNameUndPreisIntern();
        return view('examples.m4_7c_gerichte', ['data' => $data]);
    }

    public function m4_7d_layout(RequestData $rd) {
        $no = $rd->query['no'] ?? 1;
        $no = in_array($no, ['1', '2'], true) ? $no : '1';
        $dir = 'examples.pages.m4_7d_page_'.$no;
        return view($dir);
    }
}