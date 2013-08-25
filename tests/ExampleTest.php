<?php
use Slim\Environment;

class ExampleTest extends PHPUnit_Framework_TestCase {
    private $app;

    public function setUp()
    {
        $_SESSION = array();
        $this->app = new App();
    }

    public function testHome() {
        Environment::mock(array(
            'PATH_INFO' => '/'
        ));
        $response = $this->app->invoke();

        $this->assertContains('home', $response->getBody());
    }

    public function testHello() {
        Environment::mock(array(
            'PATH_INFO' => '/hello/world'
        ));
        $response = $this->app->invoke();

        $this->assertTrue($response->isOk());
        $this->assertContains('hello world', $response->getBody());
    }

    public function testNotFound() {
        Environment::mock(array(
            'PATH_INFO' => '/not-exists'
        ));
        $response = $this->app->invoke();

        $this->assertTrue($response->isNotFound());
    }

    public function testLogin() {
        Environment::mock(array(
            'PATH_INFO' => '/login'
        ));
        $response = $this->app->invoke();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('Wrong login', $_SESSION['slim.flash']['error']);
        $this->assertEquals('/', $response->headers()->get('Location'));
    }

    public function testPostLogin() {
        Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'PATH_INFO' => '/login',
            'slim.input' => 'login=world'
        ));
        $response = $this->app->invoke();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('Successfully logged in', $_SESSION['slim.flash']['success']);
        $this->assertEquals('/hello/world', $response->headers()->get('Location'));
    }

    public function testGetLogin() {
        Environment::mock(array(
            'PATH_INFO' => '/login',
            'QUERY_STRING' => 'login=world'
        ));
        $response = $this->app->invoke();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('Successfully logged in', $_SESSION['slim.flash']['success']);
        $this->assertEquals('/hello/world', $response->headers()->get('Location'));
    }
}