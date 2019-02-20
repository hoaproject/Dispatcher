<?php

declare(strict_types=1);

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
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

namespace Hoa\Dispatcher;

use Hoa\Consistency;
use Hoa\Router;
use Hoa\View;
use Hoa\Zformat;

/**
 * Class \Hoa\Dispatcher.
 *
 * Abstract dispatcher.
 */
abstract class Dispatcher implements Zformat\Parameterizable
{
    /**
     * Parameters.
     *
     * @var Zformat\Parameter
     */
    protected $_parameters  = null;

    /**
     * Current view.
     *
     * @var ?View\Viewable
     */
    protected $_currentView = null;

    /**
     * Kit's name.
     *
     * @var string
     */
    protected $_kit         = Kit::class;



    /**
     * Build a new dispatcher.
     */
    public function __construct(array $parameters = [])
    {
        $this->_parameters = new Zformat\Parameter(
            __CLASS__,
            [
                'call' => 'main',
                'able' => 'main'
            ],
            [
                'synchronous.call' => '(:call:U:)',
                'synchronous.able' => '(:able:U:)',

                'asynchronous.call' => '(:%synchronous.call:)',
                'asynchronous.able' => '(:%synchronous.able:)Async',

                /**
                 * Router variables.
                 *
                 * 'variables.…'          => …
                 */
            ]
        );
        $this->_parameters->setParameters($parameters);

        return;
    }

    /**
     * Get parameters.
     */
    public function getParameters(): Zformat\Parameter
    {
        return $this->_parameters;
    }

    /**
     * Dispatch a router rule.
     */
    public function dispatch(Router $router, ?View\Viewable $view = null)
    {
        $rule = $router->getTheRule();

        if (null === $rule) {
            $router->route();
            $rule = $router->getTheRule();
        }

        if (null === $view) {
            $view = $this->_currentView;
        } else {
            $this->_currentView = $view;
        }

        $parameters        = $this->_parameters;
        $this->_parameters = clone $this->_parameters;

        foreach ($rule[Router::RULE_VARIABLES] as $key => $value) {
            $this->_parameters->setParameter('variables.' . $key, $value);
        }

        $out = $this->resolve($rule, $router, $view);
        unset($this->_parameters);
        $this->_parameters = $parameters;

        return $out;
    }

    /**
     * Resolve the dispatch call.
     */
    abstract protected function resolve(
        array          $rule,
        Router         $router,
        ?View\Viewable $view = null
    );

    /**
     * Set kit's name.
     */
    public function setKitName(string $kit): string
    {
        $old        = $this->_kit;
        $this->_kit = $kit;

        return $old;
    }

    /**
     * Get kit's name.
     */
    public function getKitName(): string
    {
        return $this->_kit;
    }
}

/**
 * Flex entity.
 */
Consistency::flexEntity(Dispatcher::class);
