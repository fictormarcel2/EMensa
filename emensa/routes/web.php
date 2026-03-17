<?php
/**
 * Mapping of paths to controllers.
 * Note, that the path only supports one level of directory depth:
 *     /demo is ok,
 *     /demo/subpage will not work as expected
 */

return array(
    '/'             => "HomeController@index",
    "/abc"         => "DemoController@abc",
    '/dbconnect'    => 'DemoController@dbconnect',
    '/debug'        => 'HomeController@debug',
    '/error'        => 'DemoController@error',
    '/requestdata'   => 'DemoController@requestdata',

    '/requestdata/test/args/arg1/arg2'   => 'DemoController@requestdata',
    '/requestdata/test/args/arg3/arg4'   => 'DemoController@requestdata',

    // Erstes Beispiel:
    '/m4_6a_queryparameter' => 'ExampleController@m4_6a_queryparameter',
    '/m4' => 'ExampleController@m4_6a_queryparameter',

    '/m4_7a_queryparameter' => 'ExampleController@m4_7a_queryparameter',
    '/m4_7b_kategorie' => 'ExampleController@m4_7b_kategorie',
    '/m4_7c_gerichte' => 'ExampleController@m4_7c_gerichte',
    '/m4_7d_layout' => 'ExampleController@m4_7d_layout',
    '/wunschgericht' => 'HomeController@wunschgericht',

    // Anmeldung anzeigen
    '/anmeldung' => 'AuthController@index',

    // Formulardaten verarbeiten
    '/anmeldung_verfizieren' => 'AuthController@checkLogin',

    // Abmeldung
    '/abmeldung' => 'AuthController@logout',

);