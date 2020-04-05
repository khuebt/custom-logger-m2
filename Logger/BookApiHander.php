<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Ktd\LogHandler\Logger;

/**
 * Log handler for
 */
class BookApiHander extends \Monolog\Handler\StreamHandler
{

    const LOG_PATH = BP.'/var/log/api/';
    const CUSTOM_FORMAT = "%datetime%|%level_name%|%session_id%|%message%|%context%\n";

    public $fileName = BP.'/var/log/api/bookapi.log';
    public $loggerType = \Monolog\Logger::DEBUG;

    public function __construct($logName, $format = self::CUSTOM_FORMAT)
    {
        $servername = gethostname().'_';
        $servername = '';
        $thaidate = date('Y-m-d', strtotime('+7 hour', strtotime(gmdate('Y-m-d H:i:s'))));
        $this->fileName = self::LOG_PATH.$servername.$logName.'_bookapi_'.$thaidate.'.log';
        parent::__construct($this->fileName, $this->loggerType);
        $this->setFormatter(new \Ktd\LogHandler\Logger\Formatter\Formatter($format, null, true));
    }

}
