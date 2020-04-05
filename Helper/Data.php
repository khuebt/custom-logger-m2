<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Ktd\LogHandler\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $customerSession;

    private $debug;
    private $trans;
    private $endpoint;

    private $funcDebug;
    private $funcEndpoint;
    private $funcBookApi;

    protected $registry;

    protected $handlerLog;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);

        $this->customerSession = $customerSession;

        $this->registry = $registry;

        $this->handlerLog = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->debug = $objectManager->create('\Ktd\LogHandler\Logger\Logger');
        $this->trans = $objectManager->create('\Ktd\LogHandler\Logger\Logger');

        $this->endpoint = $objectManager->create('\Ktd\LogHandler\Logger\Logger');
        $this->funcEndpoint = strtolower(get_called_class());
        $this->funcEndpoint = explode('\\', $this->funcEndpoint);
        $this->funcEndpoint = end($this->funcEndpoint);
        try {
            $this->endpoint->pushHandler(new \Ktd\LogHandler\Logger\EndpointHandler($this->funcEndpoint));
        } catch (\Exception $e) {
            die($e->getMessage());
        }

    }

    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function setFuncBookApi($bookApi)
    {
        $this->funcBookApi = $bookApi;
    }

    public function setFuncDebug($funcDebug)
    {
        $this->funcDebug = $funcDebug;
        // $this->registry->unregister('log_func_debug');
        // $this->registry->register('log_func_debug', $funcDebug);
    }

    public function getFuncDebug()
    {
        return ($this->funcDebug ? $this->funcDebug : 'data');
        //return $this->registry->registry('log_func_debug');
    }

    public function getFuncBookApi()
    {
        return ($this->funcBookApi ? $this->funcBookApi : 'data');
    }

    public function getFuncEndpoint()
    {
        return ($this->funcEndpoint ? $this->funcEndpoint : 'data');
    }

    public function setFuncEndpoint($funcEndpoint)
    {
        $this->funcEndpoint = $funcEndpoint;
    }

    public function debuglog($type, $logs)
    {
        $log_name = $this->getFuncDebug();
        $handler = null;
        if (isset($this->handlerLog[$log_name.'_debug'])) {
            $handler = $this->handlerLog[$log_name.'_debug'];
        } else {
            $handler = new \Ktd\LogHandler\Logger\DebugHandler($log_name);
            $this->handlerLog[$log_name.'_debug'] = $handler;
        }
        $this->debug->setHandlers([$handler]);
        if (!is_array($logs)) {
            $logs = array($logs);
        }
        $this->debug->debug($type, $logs);
    }


    public function bookApiLog($type, $logs)
    {
        $log_name = $this->getFuncBookApi();
        $handler = null;
        if (isset($this->handlerLog[$log_name.'_bookapi'])) {
            $handler = $this->handlerLog[$log_name.'_bookapi'];
        } else {
            $handler = new \Ktd\LogHandler\Logger\BookApiHander($log_name);
            $this->handlerLog[$log_name.'_bookapi'] = $handler;
        }
        $this->debug->setHandlers([$handler]);
        if (!is_array($logs)) {
            $logs = array($logs);
        }
        $this->debug->debug($type, $logs);
    }

    /* Transaction Log
    Objective : summarize log file to display on splunk which represent each api call is success or fail
    [DTTM]|[REF No (Tracking ID)]|[SOURCE SYSTEM]|[SUBR NUM]|[REQUEST IP]|[SERVICE NAME]|[STATUS CODE]|[ERROR CODE]|[ERROR DESCRIPTION]|[RESPONSE TIME]
    DTTM                    - Process Data Time in Format YYYYMMDD HH:mm:ss
    REF No (Tracking ID)    - Reference Number
    SOURCE SYSTEM           - Source System Name
    SUBR NUM                - Subscriber Number
    REQUEST IP              - Real Client IP
    SERVICE NAME            - Last Call Service Name
    STATUS CODE             - Status Code ( S : Success , B : Business Error , T : Technical Error)
    ERROR CODE              - Error Code
    ERROR DESCRIPTION       - Error Description
    RESPONSE TIME           - Response Time in millisecond format
    */
    public function translog($trans)
    {
        //ipune !! important to select log file
        $log_name = $trans['log_name'];

        $code = $trans['error_code'];
        $status_code = 'B';
        if ($code == 200) {
            $status_code = 'S';
        } elseif ($code == 500) {
            $status_code = 'T';
        }

        // $format66 = $this->phoneFormat66($trans['ref_no']);
        // if($format66){
        //     $trans['ref_no'] = $format66;
        // }

        if (!isset($trans['source_system']) || empty($trans['source_system'])) {
            $trans['source_system'] = 'occweb';
        }
        if (!isset($trans['ref_no']) || empty($trans['ref_no'])) {
            $trans['ref_no'] = '0000000000';
        }
        if (!isset($trans['service_name']) || empty($trans['service_name'])) {
            $trans['service_name'] = '';
        }
        if (!isset($trans['endpoint']) || empty($trans['endpoint'])) {
            $trans['endpoint'] = $this->getFuncEndpoint();
        }
        if (!isset($trans['step']) || empty($trans['step'])) {
            $trans['step'] = '';
        }
        if (!isset($trans['error_code']) || empty($trans['error_code'])) {
            $trans['error_code'] = 417;
        }
        if (!isset($trans['error_description']) || empty($trans['error_description'])) {
            $trans['error_description'] = '';
        }
        if (!isset($trans['start_time']) || empty($trans['start_time'])) {
            $trans['start_time'] = microtime(true);
        }
        $response_time = round((microtime(true) - $trans['start_time']) * 1000, 2);

        $logs = array(
            "source_system" => 'occweb', //$trans['source_system']
            "ref_no" => $trans['ref_no'],
            "request_ip" => $this->getClientIP(),
            "service_name" => $trans['service_name'],
            "endpoint" => $trans['endpoint'],
            "step" => $trans['step'],
            "status_code" => $status_code,
            "error_code" => $trans['error_code'],
            "error_description" => $trans['error_description'],
            "response_time" => $response_time
        );

        $handler = null;
        if (isset($this->handlerLog[$log_name.'_trans'])) {
            $handler = $this->handlerLog[$log_name.'_trans'];
        } else {
            $handler = new \Ktd\LogHandler\Logger\TranHandler($log_name);
            $this->handlerLog[$log_name.'_trans'] = $handler;
        }
        $this->trans->setHandlers([$handler]);
        $this->trans->info('', $logs);

    }

    /* End Point Log
    Objective : detail log for show all step in api
    [DTTM]|[REF No (Tracking ID)]|[SOURCE SYSTEM]|[SUBR NUM] |[REQUEST IP]|[SERVICE NAME]|[STATUS CODE]|[ERROR CODE]|[ERROR DESCRIPTION]|[RESPONSE TIME]
    DTTM                    - Process Data Time in Format YYYYMMDD HH:mm:ss
    REF No (Tracking ID)    - Reference Number
    SOURCE SYSTEM           - Source System Name
    SUBR NUM                - Subscriber Number
    REQUEST IP              - Real Client IP
    SERVICE NAME            - Last Call Service Name
    STATUS CODE             - Status Code ( S : Success , B : Business Error , T : Technical Error)
    ERROR CODE              - Error Code
    ERROR DESCRIPTION       - Error Description
    RESPONSE TIME           - Response Time in millisecond format
    */
    public function endpointlog($epoint)
    {

        if (!isset($epoint['source_system']) || empty($epoint['source_system'])) {
            $epoint['source_system'] = 'occweb';
        }
        if (!isset($epoint['ref_trans']) || empty($epoint['ref_trans'])) {
            $epoint['ref_trans'] = '';
        }
        if (!isset($epoint['ref_no']) || empty($epoint['ref_no'])) {
            $epoint['ref_no'] = '0000000000';
        }
        if (!isset($epoint['service_name']) || empty($epoint['service_name'])) {
            $epoint['service_name'] = '';
        }
        if (!isset($epoint['status_code']) || empty($epoint['status_code'])) {
            $epoint['status_code'] = '';
        }
        if (!isset($epoint['error_code']) || empty($epoint['error_code'])) {
            $epoint['error_code'] = '';
        }
        if (!isset($epoint['error_description']) || empty($epoint['error_description'])) {
            $epoint['error_description'] = $message;
        }
        if (!isset($epoint['response_time']) || empty($epoint['response_time'])) {
            $epoint['response_time'] = 0;
        }

        // $format66 = $this->phoneFormat66($epoint['ref_no']);
        // if($format66){
        //     $epoint['ref_no'] = $format66;
        // }
        $logs = array(
            "source_system" => 'occweb',
            "ref_trans" => $epoint['ref_trans'],
            "ref_no" => $epoint['ref_no'],
            "request_ip" => $this->getClientIP(),
            "service_name" => $epoint['service_name'],
            "status_code" => $epoint['status_code'],
            "error_code" => $epoint['error_code'],
            "error_description" => $epoint['error_description'],
            "response_time" => round($epoint['response_time'], 2)
        );

        $handler = null;
        $log_name = $this->getFuncEndpoint();
        if (isset($this->handlerLog[$log_name.'_endpoint'])) {
            $handler = $this->handlerLog[$log_name.'_endpoint'];
        } else {
            $handler = new \Ktd\LogHandler\Logger\EndpointHandler($log_name);
            $this->handlerLog[$log_name.'_endpoint'] = $handler;
        }
        $this->endpoint->setHandlers([$handler]);
        $this->endpoint->info('', $logs);
    }

    // Function to get the client IP address
    protected function getClientIP()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                if (isset($_SERVER['HTTP_X_FORWARDED'])) {
                    $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
                } else {
                    if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
                        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
                    } else {
                        if (isset($_SERVER['HTTP_FORWARDED'])) {
                            $ipaddress = $_SERVER['HTTP_FORWARDED'];
                        } else {
                            if (isset($_SERVER['REMOTE_ADDR'])) {
                                $ipaddress = $_SERVER['REMOTE_ADDR'];
                            } else {
                                $ipaddress = 'UNKNOWN';
                            }
                        }
                    }
                }
            }
        }

        $ipaddressArry = explode(',', $ipaddress);
        if (count($ipaddressArry) > 1) {
            $ipaddress = $ipaddressArry[0];
        }

        return $ipaddress;
    }

    //2018-12-31T23:59:59.123
    public function dateFormatISO8601($date)
    {
        $date = strtotime($date);

        return date("Y-m-d", $date).'T'.substr(date("H:i:s.u", $date), 0, -3);
    }

    public function phoneFormat66($subrNumb)
    {
        if (preg_match("/^0[\d]{9}$/", $subrNumb)) {
            return '66'.substr($subrNumb, 1);
        }
        if (preg_match("/^66[\d]{9}$/", $subrNumb)) {
            return $subrNumb;
        }

        return false;
    }

    public function getResponse($code, $message, $response = null, $data = null, $trans = null)
    {
        //$enable_debug = $this->scopeConfig->getValue('orderapi/general/debug_response');
        $enable_debug = true;
        $code = is_null($code) ? 500 : $code;
        $resp = array('code' => $code);
        if (!empty($message)) {
            $resp['message'] = $message;
        }
        if (!empty($data)) {
            $resp['data'] = $data;
        }
        if ($enable_debug && !empty($response)) {
            $resp['debug'] = $response;
        }

        if ($trans) {
            $trans['error_code'] = $code;
            $trans['error_description'] = $message;
            $this->translog($trans);
        }

        return $resp;
    }

    /**
     * @param  string  $string    [description]
     * @param  string  $delimiter [description]
     * @param  boolean $addheader [description]
     * @param  string  $key       [description]
     *
     * @return [type]             [description]
     */
    public function csvToArray($string = '', $delimiter = ',', $addheader = true, $key = '')
    {
        $enclosure = '"';
        $escape = "\\";
        //$rows = array_filter(explode($row_delimiter, $string));
        $string = trim($string, "\r\n");
        $string = trim($string, "\r");
        $string = trim($string, "\n");
        $string = trim($string);
        $rows = array_filter(preg_split('/\r*\n+|\r+/', $string));
        $data = array();
        if ($addheader) {
            $header = array_shift($rows);
            $header = str_getcsv($header, $delimiter, $enclosure, $escape);
            if (!in_array($key, $header)) {
                $key = '';
            }
            foreach ($rows as $row) {
                $row = str_getcsv($row, $delimiter, $enclosure, $escape);
                $data[] = array_combine($header, $row);
            }
        } else {
            foreach ($rows as $row) {
                $data[] = str_getcsv($row, $delimiter, $enclosure, $escape);
            }
        }
        if (!empty($key)) {
            $keyarr = array_column($data, $key);
            $keydata = array_combine($keyarr, $data);
            $data = $keydata;
        }

        return $data;
    }

    function arrayToCsv($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
    {
        $f = fopen('php://memory', 'r+');
        $header = @$data[0] ?: [];
        $header = array_keys($header);
        if ($header) {
            fputcsv($f, $header, $delimiter, $enclosure, $escape_char);
        }
        foreach ($data as $row) {
            $value = array_values($row);
            fputcsv($f, $value, $delimiter, $enclosure, $escape_char);
        }
        rewind($f);

        return stream_get_contents($f);
    }


    public function xmlToArray($xmlstr)
    {
        if (empty($xmlstr)) {
            return null;
        }
        //remove <?xml
        $xmlstr = preg_replace("/(<\?xml \w+)(\w+\/*)*([^>]*>)/", "", $xmlstr);
        //change /> to </tag>
        $xmlstr = preg_replace("/(<\/?)([\w-_]+):([\w-_]+)([^>])*(\/>)/", "$1$3>$1/$3>", $xmlstr);
        //remove ns
        $xmlstr = preg_replace("/(<\/?)([\w-_]+):(\w+\/*)[ ]*([^>]*>)/", "$1$3>", $xmlstr);
        //TODO: check Om work?
        /*$xmlstr = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n".$xmlstr;*/
        $xml = @simplexml_load_string($xmlstr);
        $jsonstr = json_encode($xml, JSON_UNESCAPED_UNICODE);
        $json = json_decode($jsonstr, true);
        if ($json === null || JSON_ERROR_NONE !== json_last_error()) {
            $backtrace = array_slice(debug_backtrace(2), 0, 5);
            $class = __CLASS__;
            $func = __FUNCTION__;
            array_walk($backtrace, function (&$a) use (&$debug) {
                $debug .= "{$a['file']}:{$a['line']} {$a['function']} \r\n";
            });
            $this->debuglog('[XML]', [$xmlstr]);
            $this->debuglog('[ERROR]', ['error parse json. '.json_last_error()]);
            $this->debuglog('[ERROR]', [@$debug]);
        }
        foreach ($json as $key => $val) {
            if (is_array($val) && empty($val)) {
                $json[$key] = '';
            }
        }

        return $json;
    }

    public function arrToXml($arr)
    {
        $dom = new \DOMDocument();
        self::recursiveParser($dom, $arr, $dom);

        return $dom->saveXML($dom->documentElement);
    }

    private function recursiveParser(&$root, $arr, &$dom)
    {
        foreach ($arr as $key => $item) {
            if (is_array($item) && !is_numeric($key)) {
                $node = $dom->createElement($key);
                self::recursiveParser($node, $item, $dom);
                $root->appendChild($node);
            } elseif (is_array($item) && is_numeric($key)) {
                self::recursiveParser($root, $item, $dom);
            } else {
                $node = $dom->createElement($key, $item);
                $root->appendChild($node);
            }
        }
    }

    public static function jsonErrorCode($code)
    {
        $match = array(
            0 => 'JSON_ERROR_NONE',
            1 => 'JSON_ERROR_DEPTH',
            2 => 'JSON_ERROR_STATE_MISMATCH',
            3 => 'JSON_ERROR_CTRL_CHAR',
            4 => 'JSON_ERROR_SYNTAX',
            5 => 'JSON_ERROR_UTF8'
        );
        if (isset($match[$code])) {
            return $match[$code];
        }

        return 'JSON_ERROR_UNKNOWN';
    }

    public function getValueFromData($search, $data)
    {
        if (!is_array($search)) {
            $search = [$search];
        }
        $results = [];
        array_walk_recursive($data,
            function ($item, $key) use ($search, &$results) {
                if (in_array($key, $search)) {
                    $results[] = $item;
                }
            }
        );
        if (count($results)) {
            return $results[0];
        }

        return '';
    }

    public function getFilesize($path)
    {
        $size = filesize($path);
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return number_format($size / pow(1024, $power), 2, '.', ',').' '.$units[$power];
    }

    /*
    [0] => design_config_grid
    [1] => customer_grid
    [2] => amasty_label
    [3] => amasty_label_main
    [4] => amasty_mostviewed_rule
    [5] => catalog_category_product
    [6] => catalog_product_category
    [7] => catalog_product_price
    [8] => catalog_product_attribute
    [9] => cataloginventory_stock
    [10] => catalogrule_rule
    [11] => catalogrule_product
    [12] => amasty_sorting_bestseller
    [13] => amasty_sorting_most_viewed
    [14] => amasty_sorting_wished
    [15] => catalogsearch_fulltext
    [16] => amasty_multiinventory_warehouse
    [17] => mirasvit_search_score_rule_product
    [18] => amasty_segments_customer
    [19] => mst_misspell
    */
    public function reIndexing($indexIds)
    {
        if (empty($indexIds)) {
            return false;
        }
        try {
            $obj = \Magento\Framework\App\ObjectManager::getInstance();
            $indexerCollectionFactory = $obj->get("\Magento\Indexer\Model\Indexer\CollectionFactory");
            $indexerFactory = $obj->get("\Magento\Indexer\Model\IndexerFactory");
            $indexerCollection = $indexerCollectionFactory->create();
            $allIds = $indexerCollection->getAllIds();
            if ($indexIds == 'test') {
                return $allIds;
            } elseif (!is_array($indexIds)) {
                $indexIds = explode(",", $indexIds);
            }
            foreach ($allIds as $id) {
                if (in_array($id, $indexIds)) {
                    $indexer = $indexerFactory->create()->load($id);
                    $indexer->reindexAll();
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}



