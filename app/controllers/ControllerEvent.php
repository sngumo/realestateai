<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jenga\App\Controllers;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Allows filtering of a controller callable.
 *
 * You can call getController() to retrieve the current controller. With
 * setController() you can set a new controller that is used in the processing
 * of the request.
 *
 * Controllers should be callables.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ControllerEvent extends KernelEvent{
    
    /**
     * The current controller.
     */
    private $controller;

    public function __construct(HttpKernelInterface $kernel, $controller, Request $request, $requestType){
        
        parent::__construct($kernel, $request, $requestType);
        $this->setController($controller);
    }

    /**
     * Returns the current controller.
     *
     * @return callable
     */
    public function getController(){
        return $this->controller;
    }

    /**
     * Sets a new controller.
     *
     * @param callable $controller
     *
     * @throws \LogicException
     */
    public function setController($controller){
        $this->controller = $controller;
    }
}


