<?php
/**
 * This file is part of the PPI Framework.
 *
 * @copyright  Copyright (c) 2012 Paul Dragoonis <paul@ppi.io>
 * @license    http://opensource.org/licenses/mit-license.php MIT
 *
 * @link       http://www.ppi.io
 */

namespace PPI\Framework\Tests\Exception;

use PPI\Framework\Exception\Log;
use PPI\Framework\Test\Unit;

class LogTest extends Unit
{
    public $logger;

    public function setUp()
    {
        $this->logger = new Log();
    }

    public function testSetLogFile()
    {
        $this->logger->setLogFile('foobar');
        $this->assertAttributeContains('foobar', '_logFile', $this->logger);
    }
}
