<?php
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