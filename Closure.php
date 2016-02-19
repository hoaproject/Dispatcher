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

namespace Hoa\Dispatcher;

use Hoa\Router;
use Hoa\View;

/**
 * Class \Hoa\Dispatcher\Closure.
 *
 * This class dispatches on a closure, nothing more. There is
 * no concept of controller or action, it is just _call and _able.
 *
 * @copyright  Copyright © 2007-2016 Hoa community
 * @license    New BSD License
 */
class Closure extends Dispatcher
{
    /**
     * Resolve the dispatch call.
     *
     * @param   array                $rule      Rule.
     * @param   \Hoa\Router          $router    Router.
     * @param   \Hoa\View\Viewable   $view      View.
     * @return  mixed
     * @throws  \Hoa\Dispatcher\Exception
     */
    protected function resolve(
        array $rule,
        Router $router,
        View\Viewable $view = null
    ) {
        $called     = null;
        $variables  = &$rule[Router::RULE_VARIABLES];
        $call       = (isset($variables['_call'])
                          ? $variables['_call']
                          : $rule[Router::RULE_CALL]);
        $able       = (isset($variables['_able'])
                          ? $variables['_able']
                          : $rule[Router::RULE_ABLE]);
        $rtv        = [$router, $this, $view];
        $arguments  = [];
        $reflection = null;

        $this->populateKit($variables, $rtv);
        if ($call instanceof \Closure) {
            $called     = $call;
            $reflection = new \ReflectionMethod($call, '__invoke');

            foreach ($reflection->getParameters() as $parameter) {
                $name = strtolower($parameter->getName());

                if (true === array_key_exists($name, $variables)) {
                    $arguments[$name] = $variables[$name];

                    continue;
                }

                if (false === $parameter->isOptional()) {
                    throw new Exception(
                        'The closured action for the rule with pattern %s ' .
                        'needs a value for the parameter $%s and this value ' .
                        'does not exist.',
                        1,
                        [$rule[Router::RULE_PATTERN], $name]
                    );
                }
            }
        } elseif (is_string($call) && null === $able) {
            try {
                $reflection = new \ReflectionFunction($call);
            } catch (\Exception $e) {
                throw new Exception(
                    'Function %s is not found ',
                    0,
                    [ $call ],
                    $e
                );
            }

            foreach ($reflection->getParameters() as $parameter) {
                $name = strtolower($parameter->getName());

                if (true === array_key_exists($name, $variables)) {
                    $arguments[$name] = $variables[$name];

                    continue;
                }

                if (false === $parameter->isOptional()) {
                    throw new Exception(
                        'The functional action for the rule with pattern %s ' .
                        'needs a value for the parameter $%s and this value ' .
                        'does not exist.',
                        3,
                        [$rule[Router::RULE_PATTERN], $name]
                    );
                }
            }
        }

        if ($reflection instanceof \ReflectionFunction) {
            $return = $reflection->invokeArgs($arguments);
        } else {
            $return = $reflection->invokeArgs($called, $arguments);
        }

        return $return;
    }

    /**
     * Populate Dispatcher\Kit in the variable collection
     * @param array $variables Variable collection used as resolver parameters
     * @param array $rtv       Constructor's arguments.
     */
    protected function populateKit(&$variables, $rtv)
    {
        $kitname = $this->getKitName();
        if (!empty($kitname)) {
            $kit = dnew($this->getKitName(), $rtv);

            if (!($kit instanceof Kit)) {
                throw new Exception(
                    'Your kit %s must extend Hoa\Dispatcher\Kit.',
                    2,
                    $kitname
                );
            }

            $variables['_this'] = $kit;
            $variables['_this']->construct();
        }
    }
}
