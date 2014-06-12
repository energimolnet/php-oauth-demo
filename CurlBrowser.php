<?php

class CurlBrowser {

    private $_headers = array();
    private $_cookies = array();

    private $lastResponseCode;

    public function __construct()
    {
        $this->resetHeaders();
    }


    public function get($url)
    {
        $result = $this->makeRequest($url);
        return json_decode($result['body'], true) ? json_decode($result['body'], true) : $result['body'];
    }

    public function post($url, $params = null, $content_type = 'application/json')
    {
        $result = $this->makeRequest($url, $params, false, 'POST', $content_type);


        return json_decode($result['body'], true) ? json_decode($result['body'], true) : $result['body'];
    }

    public function put($url, $params = null){


        $result = $this->makeRequest($url, $params, false, 'PUT');

        if (isset($result['headers']['Set-Cookie']))
            $this->setCookie($result['headers']['Set-Cookie']);

        return $result;
    }


    public function delete($url, $params = null){

        $result = $this->makeRequest($url, $params, false, 'DELETE');

        if (isset($result['headers']['Set-Cookie']))
            $this->setCookie($result['headers']['Set-Cookie']);

        return $result;
    }

    private function convertMongoIds(array &$array){
        foreach ($array as &$element){
            if (is_array($element)){
                self::convertMongoIds($element);
            }else if (is_object($element) && get_class($element) == "MongoId"){
                $element = (string) $element;
            }
        }
    }

    protected function makeRequest($url, $params = null, $followRedirect = false, $verb = 'GET', $content_type = 'application/json')
    {

        $ch = curl_init();

        if ($content_type == 'application/json' && is_array($params)){
            // Format the params into post body

            // Convert MongoIds to string
            $this->convertMongoIds($params);

            $this->addHeader('Content-Type', $content_type);

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
        }else{
            unset($this->_headers['Content-Type']); // Let curl set proper content types
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);

        if (count($this->getHeadersForCurl())>0){
            curl_setopt($ch,CURLOPT_HTTPHEADER, $this->getHeadersForCurl());
        }

        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($followRedirect)
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        $info = curl_getinfo($ch);


        $this->lastResponseCode = $info['http_code'];

        $responseHeaders = $this->parseHeaders(substr($response, 0, $info['header_size']));
        $responseBody = substr($response, $info['header_size']);

        // TODO: Make sure this works for all scraper classes
        if (isset($responseHeaders['Content-Type']) &&
            $responseHeaders['Content-Type'] == 'text/html; charset=utf-8'){
            $responseBody = utf8_decode($responseBody);
        }

        return array ('body' => $responseBody, 'headers' => $responseHeaders);

    }

    public function getLastResponseCode(){
        return $this->lastResponseCode;
    }

    /**
     * Returns the current headers in a format intended for cURL
     */
    public function getHeadersForCurl(){
        $headers = array();
        foreach ($this->_headers as $name => $value){
            $headers[] = "$name: $value";
        }
        return $headers;
    }


    /**
     * Removes a cookie from headers.
     *
     * @param $cookie
     */

    public function unsetCookie($cookie){
        $this->_cookies[$cookie];
        $this->updateCookieHeader();
    }

    /**
     * Removes all cookies. Often equivialent to "sign out".
     */

    public function clearCookies(){
        $this->_cookies = array();
        $this->updateCookieHeader();
    }

    /**
     * Updates the current headers with the current cookies
     */
    private function updateCookieHeader(){
        if ($this->getCookiesAsString() != "")
            $this->_headers['Cookie'] = $this->getCookiesAsString();
        else
            unset($this->_headers['Cookie']);
    }

    /**
     * Clear all headers except Cookie and the default ones.
     */
    public function resetHeaders(){
        $this->_headers = array(
            'User-Agent' => "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)",
            'Expect' => '', // BUG? See this post: http://pear.php.net/bugs/bug.php?id=15937
            //'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Content-Type' => 'application/json',
            //'Cache-Control' => 'max-age=0'

        );
        $this->updateCookieHeader();
    }


    /**
     * Parses raw headers and returns an array. Duplicated headers are
     * appended into one, which is important for handling multiple cookies.
     *
     * @param $string
     * @return array
     */

    private function parseHeaders($string){
        $headers = array();
        foreach (explode("\r\n", $string) as $h){
            $pos = strpos($h, ":");
            if (isset($h[0]) && isset($h[1])){
                if (isset($headers[substr($h,0,$pos)])){
                    $headers[substr($h,0,$pos)] .= "; ".trim(substr($h,$pos+1));
                }else{
                    $headers[substr($h,0,$pos)] = trim(substr($h,$pos+1));
                }

            }
        }
        return $headers;
    }

    public function setHeader($name, $value)
    {
        $this->_headers[$name] = $value;
    }

    public function getCookiesAsString(){
        $cookies = "";
        foreach ($this->_cookies as $key => $value){
            $cookies .= "$key=$value;";
        }
        return $cookies;
    }

    public function addHeader($name, $value){
        $this->_headers[$name] = $value;
    }
}
?>