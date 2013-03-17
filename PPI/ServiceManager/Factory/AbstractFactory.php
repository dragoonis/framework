<?php
/**
 * This file is part of the PPI Framework.
 *
 * @copyright   Copyright (c) 2011-2013 Paul Dragoonis <paul@ppi.io>
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        http://www.ppi.io
 */

namespace PPI\ServiceManager\Factory;

use PPI\Config\ConfigurationProviderInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\Stdlib\ArrayUtils;

/**
 * AbstractFactory.
 *
 * @author     Vítor Brandão <vitor@ppi.io> <vitor@noiselabs.org>
 * @package    PPI
 * @subpackage ServiceManager
 */
abstract class AbstractFactory implements FactoryInterface, ConfigurationProviderInterface
{
    /**
     * Process an array with the application configuration.
     *
     * @param  array $config
     * @return array
     */
    abstract protected function processConfiguration(array $config);

    /**
     * Merges configuration.
     */
    protected function mergeConfiguration(array $defaults, array $config)
    {
        return ArrayUtils::merge($this->defaults, $config);
    }
}