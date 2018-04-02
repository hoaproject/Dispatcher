<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2016, Hoa community. All rights reserved.
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

namespace Hoa\Dispatcher\Test\Unit;

use Hoa\Dispatcher as LUT;
use Hoa\Router;
use Hoa\Test;

function dispatchedMethod($test, $foo, $bar)
{
    $test
        ->string($foo)
            ->isEqualTo('foo')
        ->string($bar)
            ->isEqualTo('bar');
}

function dispatchedMethodOptional($test, $foo, $bar = null)
{
    $test
        ->string($foo)
            ->isEqualTo('foo')
        ->variable($bar)
            ->isNull();
}

/**
 * Class \Hoa\Dispatcher\Test\Unit\Closure.
 *
 * Test suite of the closure dispatcher.
 *
 * @copyright  Copyright © 2007-2016 Hoa community
 * @license    New BSD License
 */
class Closure extends Test\Unit\Suite
{
    public function case_function_name_in_rule_pattern()
    {
        $this
            ->given(
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<_call>' . preg_quote(__NAMESPACE__ . '\dispatchedMethod') . ') ' .
                    '(?<foo>foo) ' .
                    '(?<bar>bar)'
                ),
                $this->route(
                    $router,
                    __NAMESPACE__ . '\dispatchedMethod foo bar'
                ),

                $dispatcher = new LUT\Closure(),
                $dispatcher->getParameters()->setParameters([
                    'synchronous.call' => '(:call:U:)',
                    'synchronous.able' => '(:able:)'
                ])
            )
            ->when($dispatcher->dispatch($router));
    }

    public function case_callable_in_rule_definition()
    {
        $this
            ->given(
                $closure = function ($test, $foo, $bar) {
                    dispatchedMethod($test, $foo, $bar);
                },
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<foo>foo) (?<bar>bar)',
                    $closure
                ),
                $this->route($router, 'foo bar'),

                $dispatcher = new LUT\Closure(),
                $dispatcher->getParameters()->setParameters([
                    'synchronous.call' => '(:call:U:)',
                    'synchronous.able' => '(:able:)'
                ])
            )
            ->when($dispatcher->dispatch($router));
    }

    public function case_function_not_found()
    {
        $this
            ->given(
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<foo>foo) (?<bar>bar)',
                    __NAMESPACE__ . '_NOT_FOUND'
                ),
                $this->route($router, 'foo bar'),

                $dispatcher = new LUT\Closure(),
                $dispatcher->getParameters()->setParameters([
                    'synchronous.call' => '(:call:U:)',
                    'synchronous.able' => '(:able:)'
                ])
            )
            ->exception(function () use ($dispatcher, $router) {
                $dispatcher->dispatch($router);
            })
                ->isInstanceOf('Hoa\Dispatcher\Exception');
    }


    public function case_kit_dnew()
    {
        $this
            ->given(
                $closure = function ($test, $_this) {
                    $test
                        ->object($_this)
                            ->isInstanceOf(__NAMESPACE__ . '\MockKit')
                        ->boolean($_this->hasBeenConstructed)
                            ->isTrue();
                },
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    'foo',
                    $closure
                ),
                $this->route($router, 'foo'),

                $dispatcher = new LUT\Closure(),
                $dispatcher->getParameters()->setParameters([
                    'synchronous.call' => '(:call:U:)',
                    'synchronous.able' => '(:able:)'
                ]),

                $dispatcher->setKitName(__NAMESPACE__ . '\MockKit')
            )
            ->when($dispatcher->dispatch($router));
    }

    public function case_an_argument_is_missing()
    {
        $this
            ->given(
                $closure = function ($test, $foo, $bar) {
                    dispatchedMethod($test, $foo, $bar);
                },
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<foo>foo)',
                    $closure
                ),
                $this->route($router, 'foo'),

                $dispatcher = new LUT\Closure(),
                $dispatcher->getParameters()->setParameters([
                    'synchronous.call' => '(:call:U:)',
                    'synchronous.able' => '(:able:)'
                ])
            )
            ->exception(function () use ($dispatcher, $router) {
                $dispatcher->dispatch($router);
            })
                ->isInstanceOf('Hoa\Dispatcher\Exception');
    }

    public function case_an_optional_argument_is_missing()
    {
        $this
            ->given(
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<foo>foo)',
                    __NAMESPACE__ . '\dispatchedMethodOptional'
                ),
                $this->route($router, 'foo'),

                $dispatcher = new LUT\Closure(),
                $dispatcher->getParameters()->setParameters([
                    'synchronous.call' => '(:call:U:)',
                    'synchronous.able' => '(:able:)'
                ])
            )
            ->when($dispatcher->dispatch($router));
    }

    protected function route(Router $router, $uri, array $extraVariables = [])
    {
        $router->route($uri);
        $theRule                                  = &$router->getTheRule();
        $theRule[$router::RULE_VARIABLES]['test'] = $this;

        foreach ($extraVariables as $name => $value) {
            $theRule[$router::RULE_VARIABLES][$name] = $value;
        }

        return $router;
    }
}

if (!class_exists(__NAMESPACE__ . '\MockKit')) {
    class MockKit extends LUT\Kit
    {
        public $hasBeenConstructed = false;

        public function construct()
        {
            $this->hasBeenConstructed = true;
        }
    }
}
