<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2014, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace {

from('Hoa')

/**
 * \Hoa\Dispatcher\Exception
 */
-> import('Dispatcher.Exception')

/**
 * \Hoa\Dispatcher
 */
-> import('Dispatcher.~');

}

namespace Hoa\Dispatcher {

/**
 * Class \Hoa\Dispatcher\Basic.
 *
 * A basic and generic dispatcher. It supports function, closure, object and
 * class (controller::action).
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2014 Ivan Enderlin.
 * @license    New BSD License
 */

class Basic extends Dispatcher {

    /**
     * Resolve the dispatch call.
     *
     * @access  protected
     * @param   array                $rule      Rule.
     * @param   \Hoa\Router          $router    Router.
     * @param   \Hoa\View\Viewable   $view      View.
     * @return  mixed
     * @throw   \Hoa\Dispatcher\Exception
     */
    protected function resolve ( Array $rule, \Hoa\Router $router,
                                 \Hoa\View\Viewable $view = null ) {

        $called     = null;
        $variables  = &$rule[\Hoa\Router::RULE_VARIABLES];
        $call       = isset($variables['controller'])
                          ? $variables['controller']
                          : (isset($variables['_call'])
                                 ? $variables['_call']
                                 : $rule[\Hoa\Router::RULE_CALL]);
        $able       = isset($variables['action'])
                          ? $variables['action']
                          : (isset($variables['_able'])
                                 ? $variables['_able']
                                 : $rule[\Hoa\Router::RULE_ABLE]);
        $rtv        = array($router, $this, $view);
        $arguments  = array();
        $reflection = null;

        if($call instanceof \Closure) {

            $kitname = $this->getKitName();

            if(!empty($kitname)) {

                $kit = dnew($this->getKitName(), $rtv);

                if(!($kit instanceof Kit))
                    throw new Exception(
                        'Your kit %s must extend Hoa\Dispatcher\Kit.',
                        0, $kitname);

                $variables['_this'] = $kit;
            }

            $called     = $call;
            $reflection = new \ReflectionMethod($call, '__invoke');

            foreach($reflection->getParameters() as $parameter) {

                $name = strtolower($parameter->getName());

                if(true === array_key_exists($name, $variables)) {

                    $arguments[$name] = $variables[$name];
                    continue;
                }

                if(false === $parameter->isOptional())
                    throw new Exception(
                        'The closured action for the rule with pattern %s needs ' .
                        'a value for the parameter $%s and this value does not ' .
                        'exist.',
                        1, array($rule[\Hoa\Router::RULE_PATTERN], $name));
            }
        }
        elseif(is_string($call) && null === $able) {

            $kitname = $this->getKitName();

            if(!empty($kitname)) {

                $kit = dnew($this->getKitName(), $rtv);

                if(!($kit instanceof Kit))
                    throw new Exception(
                        'Your kit %s must extend Hoa\Dispatcher\Kit.',
                        2, $kitname);

                $variables['_this'] = $kit;
            }

            $reflection = new \ReflectionFunction($call);

            foreach($reflection->getParameters() as $parameter) {

                $name = strtolower($parameter->getName());

                if(true === array_key_exists($name, $variables)) {

                    $arguments[$name] = $variables[$name];
                    continue;
                }

                if(false === $parameter->isOptional())
                    throw new Exception(
                        'The functional action for the rule with pattern %s needs ' .
                        'a value for the parameter $%s and this value does not ' .
                        'exist.',
                        3, array($rule[\Hoa\Router::RULE_PATTERN], $name));
            }
        }
        else {

            $async      = $router->isAsynchronous();
            $controller = $call;
            $action     = $able;

            if(!is_object($call)) {

                if(false === $async) {

                    $_controller = 'synchronous.controller';
                    $_action     = 'synchronous.action';
                }
                else {

                    $_controller = 'asynchronous.controller';
                    $_action     = 'asynchronous.action';
                }

                $this->_parameters->setKeyword('controller', $controller);
                $this->_parameters->setKeyword('action',     $action);

                $controller = $this->_parameters->getFormattedParameter($_controller);
                $action     = $this->_parameters->getFormattedParameter($_action);

                try {

                    $controller = dnew($controller, $rtv);
                }
                catch ( \Exception $e ) {

                    throw new Exception(
                        'Controller %s is not found ' .
                        '(method: %s, asynchronous: %s).',
                        4, array($controller, strtoupper($router->getMethod()),
                                 true === $async ? 'true': 'false'), $e);
                }

                $kitname = $this->getKitName();

                if(!empty($kitname))
                    $variables['_this'] = dnew($kitname, $rtv);

                if(method_exists($controller, 'construct'))
                    $controller->construct();
            }

            if(!method_exists($controller, $action))
                throw new Exception(
                    'Action %s does not exist on the controller %s ' .
                    '(method: %s, asynchronous: %s).',
                    5, array($action, get_class($controller),
                             strtoupper($router->getMethod()),
                             true === $async ? 'true': 'false'));

            $called     = $controller;
            $reflection = new \ReflectionMethod($controller, $action);

            foreach($reflection->getParameters() as $parameter) {

                $name = strtolower($parameter->getName());

                if(true === array_key_exists($name, $variables)) {

                    $arguments[$name] = $variables[$name];
                    continue;
                }

                if(false === $parameter->isOptional())
                    throw new Exception(
                        'The action %s on the controller %s needs a value for ' .
                        'the parameter $%s and this value does not exist.',
                        6, array($action, get_class($controller), $name));
            }
        }

        if($reflection instanceof \ReflectionFunction)
            $return = $reflection->invokeArgs($arguments);
        elseif($reflection instanceof \ReflectionMethod)
            $return = $reflection->invokeArgs($called, $arguments);

        return $return;
    }
}

}
