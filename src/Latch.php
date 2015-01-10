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
    private static $API_HOST_DOMAIN = "https://latch.elevenpaths.com";
    private static $API_URL = "/api";
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

    private function requestProxy($method, $query_string, $query=null) {
        $headers = $this->authHeaders($method, $query_string, $query);
        $url_api = self::$API_HOST_DOMAIN . $query_string;
        return $this->request($method, $url_api . $query_string, $headers, $query);
    }

    private function generateUrl($url, $params='') {
        return self::$API_URL . '/' . self::$API_VERSION . $url . $params;
    }

    public function status($accountId) {
        $url = generateUrl(self::$API_CHECK_STATUS_URL, "/" . $accountId);
        return $this->requestProxy('GET', $url);
    }

    public function operationStatus($accountId, $operationId) {
        $url = generateUrl(self::$API_CHECK_STATUS_URL, "/" . $accountId . "/op/" . $operationId);
        return $this->requestProxy('GET', $url);
    }

    public function pair($token) {
        $url = generateUrl(self::$API_PAIR_URL, "/" . $token);
        return $this->requestProxy('GET', $url);
    }

    public function pairWithId($accountId) {
        $url = generateUrl(self::$API_PAIR_WITH_ID_URL, "/" . $accountId);
        return $this->requestProxy('GET', $url);
    }

    public function unpair($accountId) {
        $url = generateUrl(self::$API_UNPAIR_URL, "/" . $accountId);
        return $this->requestProxy('GET', $url);
    }

    public function lock($accountId, $operationId=null) {
        if(!$operationId){
            $url = generateUrl(self::$API_LOCK_URL, "/" . $accountId);
        } else {
            $url = generateUrl(self::$API_LOCK_URL, "/" . $accountId . "/op/" . $operationId);
        }
        return $this->requestProxy('POST', $url);
    }

    public function unlock($accountId, $operationId=null) {
        if(!$operationId){
            $url = generateUrl(self::$API_UNLOCK_URL, "/" . $accountId);
        } else {
            $url = generateUrl(self::$API_UNLOCK_URL, "/" . $accountId . "/op/" . $operationId);
        }
        return $this->requestProxy('POST', $url);
    }

    public function history($accountId, $from=0, $to=null) {
        if(!$to) {
            $date = time();
            $to = $date * 1000;
        }
        $url = generateUrl(self::$API_HISTORY_URL, "/" . $accountId . "/" . $from . "/" . $to);
        return $this->requestProxy('GET', $url);
    }

    public function getOperation($operationId=null) {
        if (!$operationId){
            $url = generateUrl(self::$API_OPERATION_URL);
        } else {
            $url = generateUrl(self::$API_OPERATION_URL, "/" . $operationId);
        }
        return $this->requestProxy('GET', $url);
    }

    public function createOperation($parentId, $name, $twoFactor, $lockOnRequest) {
        $query = array(
            'parentId' => urlencode($parentId),
            'name' => urlencode($name),
            'two_factor' => urlencode($twoFactor),
            'lock_on_request' => urlencode($lockOnRequest)
        );
        $url = generateUrl(self::$API_OPERATION_URL);
        return $this->request_proxy('POST', $url, $query);
    }

    public function updateOperation($operationId, $name, $twoFactor, $lockOnRequest) {
        $query = array(
            'name' => urlencode($name),
            'two_factor' => urlencode($twoFactor),
            'lock_on_request' => urlencode($lockOnRequest)
        );
        $url = generateUrl(self::$API_OPERATION_URL, "/" . $operationId);
        return $this->requestProxy('POST', $url, $query);
    }

    public function removeOperation($operationId) {
        $url = generateUrl(self::$API_OPERATION_URL, "/" . $operationId);
        return $this->requestProxy('DELETE', $url);
    }

    private function authHeaders($method, $query_string, $query=null) {
        $utc = $this->getCurrentUTC();
        $string_to_sign = trim(strtoupper($method)) . "\n" . $utc . "\n\n" . trim($query_string);

        if($query && sizeof($query) > 0) {
            $serialized_params = $this->getSerializedParams($query);
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
        return http_build_query($params);
    }

    private function getCurrentUTC() {
        $time = new \DateTime('now', new \DateTimeZone('UTC'));
        return $time->format("Y-m-d H:i:s");
    }

}
