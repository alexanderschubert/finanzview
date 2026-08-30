<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Die Startseite soll nicht mit einem Serverfehler antworten.
     *
     * Nicht eingeloggte Benutzer werden zur Login-Seite weitergeleitet.
     */
    public function test_the_application_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
