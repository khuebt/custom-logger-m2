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
class TranHandler extends \Monolog\Handler\StreamHandler
{

    const LOG_PATH = BP.'/var/log/api/';
    const CUSTOM_FORMAT = "%datetime%|%session_id%|%context%\n";

    public $fileName = BP.'/var/log/api/trans.log';
    public $loggerType = \Monolog\Logger::INFO;

    /*[DTTM]|[REF No (Tracking ID)]|[SOURCE SYSTEM]|[SUBR NUM]|[REQUEST IP]|[SERVICE NAME]|[STATUS CODE]|[ERROR CODE]|[ERROR DESCRIPTION]|[RESPONSE TIME]*/
    public function __construct($logName, $format = self::CUSTOM_FORMAT)
    {
        $servername = gethostname().'_';
        $servername = '';
        $thaidate = date('Y-m-d', strtotime('+7 hour', strtotime(gmdate('Y-m-d H:i:s'))));
        $this->fileName = self::LOG_PATH.$servername.$logName.'_trans_'.$thaidate.'.log';
        parent::__construct($this->fileName, $this->loggerType);
        $this->setFormatter(new \Ktd\LogHandler\Logger\Formatter\Formatter($format, null, true));
    }

}
