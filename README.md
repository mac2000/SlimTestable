Slim Testable
=============

Suppose we have simple application:

    <?php
    use Slim\Slim;

    require_once 'vendor/autoload.php';

    $app = new Slim();

    $app->get('/', function(){
        echo 'home';
    })->name('home');

    $app->get('/hello/:name', function($name){
        echo "hello $name";
    })->name('hello');

    $app->map('/login', function() use($app) {
        if($app->request()->params('login')) {
            $app->flash('success', 'Successfully logged in');
            $app->redirect($app->urlFor('hello', array('name' => $app->request()->params('login'))));
        } else {
            $app->flash('error', 'Wrong login');
            $app->redirect($app->urlFor('home'));
        }
    })->via('GET', 'POST');

    $app->run();

How do we test it?

Create `App` class:

    <?php // src/App.php
    use Slim\Slim;

    class App extends Slim {
        function __construct(array $userSettings = array())
        {
            parent::__construct($userSettings);

            $this->get('/', function(){
                echo 'home';
            })->name('home');

            $this->get('/hello/:name', function($name){
                echo "hello $name";
            })->name('hello');

            $this->map('/login', function() {
                if($this->request()->params('login')) {
                    $this->flash('success', 'Successfully logged in');
                    $this->redirect($this->urlFor('hello', array('name' => $this->request()->params('login'))));
                } else {
                    $this->flash('error', 'Wrong login');
                    $this->redirect($this->urlFor('home'));
                }
            })->via('GET', 'POST');
        }

        /**
         * @return \Slim\Http\Response
         */
        public function invoke() {
            $this->middleware[0]->call();
            $this->response()->finalize();
            return $this->response();
        }
    }

Notice that we move all our routes to new class constructor, also notice new `invoke` method, which do the same as `run` method except it returns response rather than echoing it out.

Now your `index.php` file might be like this one:

    <?php
    require_once 'vendor/autoload.php';

    $app = new App();
    $app->run();

And now it is time for tests:

    <?php // tests/ExampleTest.php
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

You should notice few things:

While setting up test we are creating `$_SESSION` array for test purposes and instantiate our `App` class object.

In tests rather than `run` we are calling `invoke` which do the same, but returns response object.

`Environment::mock` used to mock requests which are processed with our application.