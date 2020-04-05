<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Ktd\LogHandler\Logger;

class Logger extends \Monolog\Logger
{
	public function __construct($name, array $handlers = array(), array $processors = array())
    {
        $this->name = $name;
        $this->handlers = $handlers;
        $this->processors = $processors;
    }

	public function tran($message, array $context = array(), array $extra = array())
    {
        return $this->addRecord(static::INFO, $message, $context, $extra);
    }

}
