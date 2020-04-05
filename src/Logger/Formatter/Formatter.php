<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Ktd\LogHandler\Logger\Formatter;

class Formatter extends \Monolog\Formatter\LineFormatter
{
    // sample $record
    /*
    array(7) {
      ["message"]=>
      string(6) "[PROC]"
      ["context"]=>
      array(1) {
        [0]=>
        string(63) "-------- Call Service|/MNPWebPortal/api/checkBlackList --------"
      }
      ["level"]=>
      int(200)
      ["level_name"]=>
      string(4) "INFO"
      ["channel"]=>
      string(19) "Marginframe_Orderapi"
      ["datetime"]=>
      object(DateTime)#571 (3) {
        ["date"]=>
        string(26) "2018-05-29 08:19:57.446784"
        ["timezone_type"]=>
        int(3)
        ["timezone"]=>
        string(3) "UTC"
      }
      ["extra"]=>
      array(0) {
      }
    }
    */
    public function format(array $record)
    {
        // change date to +7
        $record["datetime"] = new \DateTime("+ 7 hour");
        // add session_id
        $record["session_id"] = session_id();
        // change to use parent NormalizerFormatter
        $vars = \Monolog\Formatter\NormalizerFormatter::format($record);

        $output = $this->format;

        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }

        foreach ($vars['context'] as $var => $val) {
            if (false !== strpos($output, '%context.'.$var.'%')) {
                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (empty($vars['extra'])) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
        }

        return $output;
    }

    public function stringify($data)
    {
        // old from LineFormatter::stringify
        //return $this->replaceNewlines($this->convertToString($data));

        if (null === $data || is_bool($data)) {
            $text = var_export($data, true);
        }
        elseif (is_scalar($data)) {
            $text = (string) $data;
        }
        elseif(is_array($data)){
            foreach ($data as $key => &$value) {
                if(!is_scalar($value)){
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }
            $text = implode('|', array_values($data));
        }
        else{
            $text = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $text = $this->secureText($text);
        return $text;

        // old from LineFormatter::convertToString
        // if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
        //     return $this->toJson($data, true);
        // }
        // return str_replace('\\/', '/', @json_encode($data));
    }

    private function secureText($text){

        try{
            //remove \r\n
            $text = preg_replace("/>(\n|\r|\t| )+</", "><", $text);
            //mark phone
            // $text = preg_replace("/(^|\s|'|\"|\||>|;|,)(0\d{1}|66\d{1})(\d{4})(\d{3,4})($|\s|'|\"|\||<|;|,)/", "$1$2XXXX$4$5", $text);
            // //mark idcard
            // $text = preg_replace("/(^|\s|'|\"|\||>|;|,)(\d{4})(\d{5})(\d{4})($|\s|'|\"|\||<|;|,)/", "$1$2XXXXX$4$5", $text);

        } catch(\Exception $ex){}

        return $text;
    }

}

?>
