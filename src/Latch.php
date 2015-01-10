<?php
namespace Fourcoders\LatchSdk;
/*License ...*/

use GuzzleHttp;
use LatchResponse;

class Latch {

    private static $AUTH_HEADER_NAME = "Authorization";
    private static $AUTH_METHOD = "11PATHS";
    private static $AUTH_HEADER_FIELD_SEPARATOR = " ";
    private static $DATE_HEADER_NAME = "X-11Paths-Date";

    private static $HMAC_ALGORITHM = "sha1";

    private static $API_VERSION = "0.9";
    private static $API_HOST = "https://latch.elevenpaths.com/api";
    private static $API_CHECK_STATUS_URL = "/status";
    private static $API_PAIR_URL = "/pair";
    private static $API_PAIR_WITH_ID_URL = "/partWithId";
    private static $API_UNPAIR_URL = "/unpair";
    private static $API_LOCK_URL = "/lock";
    private static $API_UNLOCK_URL = "/unlock";
    private static $API_HISTORY_URL = "/history";
    private static $API_OPERATION_URL = "/operation";

    function __construct($appId, $secretKey) {
        $this->appId = $appId;
        $this->secretKey = $secretKey;
    }

    private function generate_url($url_resource) {
        return self::$API_HOST . '/' . self::$API_VERSION . $url_resource;
    }

    private function request($method, $url, $headers, $query=array()) {
        $client = new GuzzleHttp\Client();

        if('GET' == $method) {
            $opts = array('headers' => $headers);
            $response = $client->get($url, $opts);
        } else if('POST' == $method) {
            $opts = array('headers' => $headers, 'body' => $query);
            $response = $client->post($url, $body);
        } else if('PUT' == $method) {
            $opts = array('headers' => $headers, 'body' => $query);
            $response = $client->put($url, $body);
        } else if('DELETE' == $method) {
            $opts = array('headers' => $headers, 'query' => $query);
            $response = $client->delete($url, $opts);
        }

        return new LatchResponse($response->getBody());
    }

    private function requestProxy($method, $url, $query=null){
        $arrayUrl=explode('/',$url);
        for ($i=0; $i <= 2 ; $i++) {
        unset($arrayUrl[$i]);
        }
        $query_string=implode("/",$arrayUrl);
        $query_string = '/'.$query_string;
        $headers = $this->authHeaders($method, $query_string, $query);
        return $this->request($method, $url, $headers, $query);
    }

    public function status($accountId) {
        $url = $this->generate_url(self::$API_CHECK_STATUS_URL);
        return $this->requestProxy('GET', $url . "/" . $accountId);
    }

    public function operationStatus($accountId, $operationId) {
        $url = $this->generate_url(self::$API_CHECK_STATUS_URL);
        return $this->requestProxy('GET', $url . "/" . $accountId . "/op/" . $operationId);
    }

    public function pair($token) {
        $url = $this->generate_url(self::$API_PAIR_URL);
        return $this->requestProxy('GET', $url . "/" . $token);
    }

    public function pairWithId($accountId) {
        $url = $this->generate_url(self::$API_PAIR_WITH_ID_URL);
        return $this->requestProxy('GET', $url . "/" . $accountId);
    }

    public function unpair($accountId) {
        $url = $this->generate_url(self::$API_UNPAIR_URL);
        return $this->requestProxy('GET', $url . "/" . $accountId);
    }

    public function lock($accountId, $operationId=null) {
        $url = $this->generate_url(self::$API_LOCK_URL);

        if(!$operationId){
            return $this->requestProxy('POST', $url . "/" . $accountId);
        } else {
            return $this->requestProxy('POST', $url . "/" . $accountId . "/op/" . $operationId);
        }
    }

    public function unlock($accountId, $operationId=null) {
        $url = $this->generate_url(self::$API_UNLOCK_URL);

        if(!$operationId){
            return $this->request_proxy('POST', $url . "/" . $accountId);
        } else {
            return $this->request_proxy('POST', $url . "/" . $accountId . "/op/" . $operationId);
        }
    }

    public function history($accountId, $from=0, $to=null) {
        $url = $this->generate_url(self::$API_HISTORY_URL);

        if(!$to) {
            $date = time();
            $to = $date * 1000;
        }

        return $this->requestProxy('GET', $url . "/" . $accountId . "/" . $from . "/" . $to);
    }

    public function getOperation($operationId=null) {
        $url = $this->generate_url(self::$API_OPERATION_URL);

        if (!$operationId){
            return $this->requestProxy('GET', $url);
        } else {
            return $this->requestProxy('GET', $url . "/" . $operationId);
        }
    }

    public function createOperation($parentId, $name, $twoFactor, $lockOnRequest) {
        $url = $this->generate_url(self::$API_OPERATION_URL);
        $query = array(
            'parentId' => urlencode($parentId),
            'name' => urlencode($name),
            'two_factor' => urlencode($twoFactor),
            'lock_on_request' => urlencode($lockOnRequest)
        );

        return $this->request_proxy('POST', $url, $query);
    }

    public function updateOperation($operationId, $name, $twoFactor, $lockOnRequest) {
        $url = $this->generate_url(self::$API_OPERATION_URL);
        $query = array(
            'name' => urlencode($name),
            'two_factor' => urlencode($twoFactor),
            'lock_on_request' => urlencode($lockOnRequest)
        );

        return $this->requestProxy('POST', $url . "/" . $operationId, $query);
    }

    public function removeOperation($operationId) {
        $url = $this->generate_url(self::$API_OPERATION_URL);
        return $this->requestProxy('DELETE', $url . "/" . $operationId);
    }

    private function authHeaders($method, $query_string, $query=null) {
        $utc = $this->getCurrentUTC();
        $string_to_sign = trim(strtoupper($method)) . "\n" . $utc . "\n\n" . trim($query_string);

        if($query && sizeof($query) > 0) {
            $serialized_params = $this->getSerializedParams($params);
            if($serializedParams && sizeof($serializedParams) > 0) {
                $string_to_sign = trim($string_to_sign . "\n" . $serialized_params);
            }
        }

        $auth_header = self::$AUTH_METHOD .
                       self::$AUTH_HEADER_FIELD_SEPARATOR .
                       $this->appId .
                       self::$AUTH_HEADER_FIELD_SEPARATOR .
                       $this->signData($string_to_sign);


        $headers = array(
            self::$AUTH_HEADER_NAME => $auth_header,
            self::$DATE_HEADER_NAME => $utc
        );

        return $headers;
    }

    private function signData($data) {
        return base64_encode(hash_hmac(self::$HMAC_ALGORITHM, $data, $this->secretKey, true));
    }

    private function getSerializedParams($params) {
        if(!$params) {
            return "";
        }

        ksort($params);
        $serialized_params = "";

        foreach($params as $key => $value) {
            $serialized_params .= $key . "=" . $value . "&";
        }

        return trim($serialized_params, "&");
    }

    private function getCurrentUTC() {
        $time = new \DateTime('now', new \DateTimeZone('UTC'));
        return $time->format("Y-m-d H:i:s");
    }

}
