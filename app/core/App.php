<?php
namespace Jenga\App\Core;

use Jenga\App\Request\Input;
use Jenga\App\Request\Session;
use Jenga\App\Project\Core\Project;
use Jenga\App\Console\CommandsLoader;
use Jenga\App\Project\Routing\Router;
use Jenga\App\Controllers\ControllerEvent;
use Jenga\App\Project\EventsHandler\Events;

use Jenga\MyProject\Config;
use Jenga\MyProject\Elements;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Symfony\Component\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

//symfony console
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;

use Monolog\Logger;
use Monolog\ErrorHandler;
use Jenga\App\Project\Logs\Log;
use Jenga\App\Project\Logs\LogHandler;

class App extends HttpKernel\HttpKernel{  
    
    public static $config;
    public static $shell;
    public static $ioc;
    public static $kernel;
    public static $elements;
    
    public $request;
    
    /**
     * @
     * @var Project;
     */
    public $project;
    public $mode;
    public $response;

    protected $defaultElement;
    protected $defaultModel;
    protected $defaultController;
    protected $defaultView;
    
    /**
     * 
     * @var Events
     */
    protected $eventshandler;
    protected $dispatcher;
    protected $matcher;
    protected $urlgenerator;
    protected $resolver;
    protected $requestStack;

    public function __construct($jconfig, $jroutes, $jevents = null){
        
        self::$config = $this->getConfigs($jconfig);
       
        //initiate and run the handlers
        $ioc = new IoC(self::$config);        
        $ioc->registerHandlers();
        
        //register the IoC shell into the application as the shell
        self::$shell = $ioc->register();
        self::$ioc = $ioc;
        
        //add configurations to App shell
        self::$shell->set('_config', self::$config);
        
        //set the development environment
        self::setReporting();
        
        //set the project name
        if(!is_null(self::$config))
            define('PROJECT_NAME', self::$config->project);
        
        //set the timezone settings
        self::setTimeZone();
        
        //mount the local disk
        File::mount();
        
        //get current request
        $this->request = Input::load(true);
        self::$shell->set('_request', $this->request);
        
        //start and load the Project
        $this->project = $this->buildProject();
        self::$shell->set('_project', $this->project);
        
        //bypass process if on startup mode
        $this->mode = $this->project->mode;
        
        if($this->project->mode != 'startup'){
            
            //process the routing section
            $routes = $this->router($jroutes);

            //assign the resources
            self::$shell->set('_resources', $routes->resources);

            //process current request
            $request_context = $this->buildRequestContext();
            $this->requestStack = new RequestStack();

            //process routes based on current request
            $urlmatch = $this->mapUrl($routes->collector, $request_context);

            //add generator to shell
            self::$shell->set('_urlgenerator', $this->urlgenerator);

            //resolve the linked controller in the processed route
            $controller = $this->resolveController();

            //process the events and route events
            self::$shell->set('_jevents', $jevents);
            
            $this->eventshandler = self::$shell->get(Events::class);
            $this->eventshandler->addRouteEvents($routes->eventscheduler);

            //translate Symfony Kernel Events
            $this->eventshandler->translateKernelEventClasses();
            $this->eventshandler->registerKernelEvents();

            $this->dispatcher = $this->eventshandler->process();

            Events::addQueue( new HttpKernel\EventListener\RouterListener($urlmatch, new RequestStack()) );
            Events::addQueue( new HttpKernel\EventListener\ResponseListener('UTF-8') );

            //match the url to processed controller
            parent::__construct($this->dispatcher, $controller);
            
            $this->response = new Response();
        }
        
        return $this;
    }
    
    /**
     * Overloads the App class to allow the IoC shell to be called directly
     * 
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public static function __callStatic($name, $arguments) {
        
        //this happens when the function
        if($name == 'call'){
            return self::$shell->call($arguments[0]);
        }
        else{
            if(method_exists(self::$shell, $name)){
                return call_user_func_array([self::$shell, $name], $arguments);
            }
        }
    }
    
    /**
     * Extends the PHP-DI shell to resolve class construct parameters defined in the shell 
     * but can only be inserted with annotations
     * 
     * @param string $class Fully Namespaced ClassName
     */
    public static function resolve($class){
        
        if(class_exists($class)){
            
            $reflectionclass = new \ReflectionClass($class);
            
            if ($reflectionclass->isInstantiable()) {
                
                $construct = $reflectionclass->getConstructor();
                
                //check for construct
                if(!is_null($construct)){
                    
                    $params = $construct->getParameters();
                    
                    //the entity construct will be analysed to inject any necessary elements or classes
                    if(count($params) >= 1){

                        $args = [];

                        foreach ($params as $arg) {

                            $name = $arg->getName();
                            
                            if(self::$shell->has($name)){
                                $args[$name] = self::$shell->get($name);
                            }
                            elseif(!is_null($arg->getClass())){

                                $reflectclass = $arg->getClass();
                                $ctrl = Elements::resolveControllerInArgument($reflectclass->name);
                                
                                if(!is_null($ctrl)){
                                    $args[$name] = $ctrl;
                                }
                            }
                        }

                        return self::$shell->make($class, $args);
                    }
                } 
                
                return self::$shell->make($class);
            }
        }
        else{
            throw self::exception('The "'.$class.'" does not exists');
        }
    }
    
    /**
     * Binds sent name and value into IoC shell
     * 
     * @param type $name
     * @param type $value
     */
    public static function bind($name, $value) {        
        return self::$shell->set($name, $value);
    }

    /**
     * Sets the system development environment
     */
    public static function setReporting() {
        
        $level = null;
        if(self::$config->development_environment == TRUE){
    
            // Set the error_reporting
            switch (self::$config->error_reporting){
                case 'none':
                    
                    $level = Logger::ERROR;
                    error_reporting(0);
                        break;
                
                case 'default':
                case '-1':
                    
                    $level = Logger::ERROR;
                    error_reporting(E_ERROR | E_WARNING | E_PARSE);
                        break;

                case 'simple':
                    $level = Logger::ERROR;
                    break;

                case 'maximum':
                    $level = Logger::DEBUG;
                    break;

                case 'development':
                    $level = Logger::WARNING;
                    break;

                default:
                    $level = Logger::ERROR;
                    break;
            }
        }
        else{          
            
            //set error reporting for initial start up
            $level = Logger::ERROR;
            
            //set error reporting to zero - refine later
            error_reporting(0);
        }
        
        //set the loghandler into the container
        self::bind(LogHandler::class, \DI\object(LogHandler::class)->constructor('errors', $level, []));        
        $loghandle = self::get(LogHandler::class);
        
        $phphandler = new ErrorHandler($loghandle->logger);        
        $phphandler->registerErrorHandler([], false);
        $phphandler->registerExceptionHandler();
        $phphandler->registerFatalHandler();
        
        //$loghandle->logger->pushHandler($phphandler);
    }
    
    /**
     * Set the default timezone for the application
     */
    public static function setTimeZone() {
        
        if(!is_null(self::$config))
            date_default_timezone_set(self::$config->timezone);
    }
    
    protected function getConfigs($config_file){
        
        if(file_exists($config_file)){
            
        require_once $config_file;
        $cfg = new Config();
        }
        else{
            $cfg = NULL;
        }
        
        return $cfg;
    }
    
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true){
        
        //start the session
        Session::start();
        $request->headers->set('X-Php-Ob-Level', ob_get_level());
        
        try{     
            
            return $this->parseHandler($request, $type);            
        } 
        catch (\Exception $e) {
            if (false === $catch) {
                $this->finishRequest($request, $type);

                throw $e;
            }

            return $this->handleException($e, $request, $type);
        }
    }  
    
    public function parseHandler(Request $request, $type = self::MASTER_REQUEST){

        $this->requestStack->push($request);
        
        // run symfony kernel request event     
        $jevent = new \JRequestEvent($this, $request, $type);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $jevent);
        
        //initialize project
        $this->project->init($request, $jevent);
        
        //fire the before:request route events
        Events::fireOnRoute($this->project->current_route, KernelEvents::REQUEST);
        
        if (!is_null($jevent) && $jevent->hasResponse()) {
            return $this->filterResponse($jevent->getResponse(), $request, $type);
        }
        
        if (false === $controller = $this->project->getController()) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller for path "%s". The route is wrongly configured.', $request->getPathInfo()));
        }
        
        $event = new ControllerEvent($this, $controller, $request, $type);
        
        //fire the on:request and before route events
        Events::fireOnRoute($this->project->current_route, KernelEvents::CONTROLLER);        
        $controller = $event->getController();
        
        // controller arguments
        $arguments = $this->project->getArguments($request, $controller);         
        if(!is_null(self::$config) && self::$config->cache_files == TRUE){
           
            // call controller and output buffer
            ob_start();
            
            $this->project->run($controller, $arguments);            
            $output = ob_get_contents();
            ob_end_clean();
            
            if(!is_bool($output) && !is_null($output)){
                $this->response->setContent($output);
            }
        }
        else{
            $this->project->run($controller, $arguments);
        }
        
        //fire the on:complete and after route events
        Events::fireOnRoute($this->project->current_route, KernelEvents::TERMINATE);  
        
        // view
        $response = $this->response;
        
        if(!is_null($response)){
            
            if (!$response instanceof Response) {

                $event = new \JViewEvent($this, $request, $type, $response);
                $this->dispatcher->dispatch(KernelEvents::VIEW, $event);

                if ($event->hasResponse()) {
                    $response = $event->getResponse();
                }

                if (!$response instanceof Response) {
                    $msg = sprintf('The controller must return a response (%s given).', $this->varToString($response));

                    // the user may have forgotten to return something
                    if (null === $response) {
                        $msg .= ' Did you forget to add a return statement somewhere in your controller?';
                    }
                    throw new \LogicException($msg);
                }
            }

            return $this->filterResponse($response, $request, $type);
        }
    }     
    
    /**
     * Process the available routes
     */
    protected function router($routesfile){  
        
        self::$shell->set('routesfile', $routesfile);
        return self::$shell->get(Router::class);
    }
    
    /**
     * Return information based on current request
     */
    protected function buildRequestContext(){  
        return new RequestContext();
    }

    /**
     * Matches the sent routes to the current context
     *  
     * @param type $routes
     * @param type $context
     */
    protected function mapUrl($routes, $context){
        
        $this->matcher = new UrlMatcher($routes, $context);
        $this->urlgenerator = new UrlGenerator($routes, $context);
        
        return $this->matcher;
    }
    
    protected function resolveController() {
        
        $this->resolver = new ControllerResolver();
        return $this->resolver;
    }
    
    protected function buildProject(){ 
        
        $project = self::$shell->get(Project::class);
        $project->boot();
        
        return $project;
    }
    
    /**
     * Publishes the finish request event, then pop the request from the stack.
     *
     * Note that the order of the operations is important here, otherwise
     * operations such as {@link RequestStack::getParentRequest()} can lead to
     * weird results.
     *
     * @param Request $request
     * @param int     $type
     */
    public function finishRequest(Request $request, $type)
    {
        $this->dispatcher->dispatch(KernelEvents::FINISH_REQUEST, new \JResponseEvent($this, $request, $type));
        $this->requestStack->pop();
    }
    
    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this->dispatcher->dispatch(KernelEvents::TERMINATE, new \JTerminateEvent($this, $request, $response));
    }
    
    /**
     * Handles an exception by trying to convert it to a Response.
     *
     * @param \Exception $e       An \Exception instance
     * @param Request    $request A Request instance
     * @param int        $type    The type of the request
     *
     * @return Response A Response instance
     *
     * @throws \Exception
     */
    private function handleException(\Exception $e, $request, $type)
    {
        $event = new \JExceptionEvent($this, $request, $type, $e);
        $this->dispatcher->dispatch(KernelEvents::EXCEPTION, $event);

        // a listener might have replaced the exception
        $e = $event->getException();

        if (!$event->hasResponse()) {
            $this->finishRequest($request, $type);

            throw $e;
        }

        $response = $event->getResponse();

        // the developer asked for a specific status code
        if ($response->headers->has('X-Status-Code')) {
            $response->setStatusCode($response->headers->get('X-Status-Code'));

            $response->headers->remove('X-Status-Code');
        } elseif (!$response->isClientError() && !$response->isServerError() && !$response->isRedirect()) {
            // ensure that we actually have an error response
            if ($e instanceof HttpExceptionInterface) {
                // keep the HTTP status code and headers
                $response->setStatusCode($e->getStatusCode());
                $response->headers->add($e->getHeaders());
            } else {
                $response->setStatusCode(500);
            }
        }

        try {
            return $this->filterResponse($response, $request, $type);
        } catch (\Exception $e) {
            
            Log::critical($e->getMessage());
            return $response;
        }
    }
    
    /**
     * Throws a critical error and exits
     * 
     * @param type $message
     * @throws \Exception
     */
    public static function critical_error($message, $code = 409){
        
        http_response_code($code);
        Log::critical($message);
        
        die();
    }
    
    /**
     * Throws a warning
     * @param type $message
     */
    public static function warning($message){
        Log::warning($message);
    }
    
    /**
     * Throws an exception
     * @param type $message
     * @throws NotFoundHttpException
     */
    public static function exception($message){
        Log::critical($message);
    }
    
    /**
     * Filters a response object.
     *
     * @param Response $response A Response instance
     * @param Request  $request  An error message in case the response is not a Response object
     * @param int      $type     The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response The filtered Response instance
     *
     * @throws \RuntimeException if the passed object is not a Response instance
     */
    private function filterResponse(Response $response, Request $request, $type){
        
        $event = new FilterResponseEvent($this, $request, $type, $response);

        $this->dispatcher->dispatch(KernelEvents::RESPONSE, $event);

        $this->finishRequest($request, $type);

        return $event->getResponse();
    }
    
    /**
     * Allows the Console commands to be loaded within the framework or project
     * 
     * @param type $command the command to be loaded from the console
     * @param type $arguments the arguments to be inserted into the command e.g. --type
     * @param boolean $autoexit exit after running the command
     * @param boolean $suppressout this suppresses any output from the command
     * 
     */
    public static function terminal($command, $arguments = [], $autoexit = false, $suppressoutput = false) {
        
        //add command
        $args = [
            'command' => $command
        ];
        
        //loop through the arguments
        foreach($arguments as $flag => $arg){
            $args[$flag] = $arg;
        }
        
        $input = new ArrayInput($args);
        
        if($suppressoutput === FALSE)
            $output = new BufferedOutput();
        else
            $output = new NullOutput();
        
        $cli = new CommandsLoader();        
        $cli->app->setAutoExit($autoexit);
        
        //run the sent command
        $cli->app->run($input, $output);
        
        if($suppressoutput === FALSE){
            
            //return the command output
            $response = $output->fetch();      
            $output->write(sprintf("\033\143"));
            
            unset($input, $output);
        
            return $response;
        }
        else
            return true;
    }
}
