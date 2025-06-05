<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Session;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_login_when_unauthenticated()
    {
        Session::forget('access_token');
        Session::forget('user_role');

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_root_redirects_to_dashboard_when_authenticated()
    {
        Session::put('access_token', 'test-token');
        Session::put('user_role', 'cook');

        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}