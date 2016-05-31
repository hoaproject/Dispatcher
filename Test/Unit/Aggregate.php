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

/**
 * Class \Hoa\Dispatcher\Test\Unit\Aggregate.
 *
 * Test suite of the aggregate dispatcher.
 *
 * @copyright  Copyright © 2007-2016 Hoa community
 * @license    New BSD License
 */
class Aggregate extends Test\Unit\Suite
{
    public function case_no_dispatchers()
    {
        $this
            ->given(
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<_call>' . preg_quote(__CLASS__) . ') ' .
                    '(?<_able>dispatchedMethod) ' .
                    '(?<foo>foo) ' .
                    '(?<bar>bar)'
                ),
                $this->route(
                    $router,
                    __CLASS__ . ' dispatchedMethod foo bar'
                ),
                $dispatcher = new LUT\Aggregate([])
            )
            ->exception(function () use ($dispatcher, $router) {
                $dispatcher->dispatch($router);
            })
                ->isInstanceOf(LUT\Exception::class);
    }

    public function case_invalid_dispatcher()
    {
        $this
            ->given(
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<_call>' . preg_quote(__CLASS__) . ') ' .
                    '(?<_able>unknownMethod) ' .
                    '(?<foo>foo) ' .
                    '(?<bar>bar)'
                ),
                $this->route(
                    $router,
                    __CLASS__ . ' unknownMethod foo bar'
                ),
                $parameters = [
                    'synchronous.call' => '(:call:U:)',
                    'synchronous.able' => '(:able:)'
                ],
                $dispatcher = new LUT\Aggregate([
                    new LUT\ClassMethod($parameters)
                ])
            )
            ->exception(function () use ($dispatcher, $router) {
                $dispatcher->dispatch($router);
            })
                ->isInstanceOf(LUT\Exception::class);
    }

    public function case_resolved()
    {
        $this
            ->given(
                $router = new Router\Cli(),
                $router->get(
                    'a',
                    '(?<foo>foo) (?<bar>bar)',
                    __CLASS__,
                    'dispatchedMethod'
                ),
                $this->route(
                    $router,
                    'foo bar'
                ),
                $dispatcher = new LUT\Aggregate([
                    new LUT\ClassMethod()
                ])
            )
            ->when($dispatcher->dispatch($router));
    }

    public function dispatchedMethod($test, $foo, $bar)
    {
        $test
            ->string($foo)
                ->isEqualTo('foo')
            ->string($bar)
                ->isEqualTo('bar');
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
