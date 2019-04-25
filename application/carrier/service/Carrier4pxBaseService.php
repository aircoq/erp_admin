<?php
namespace app\carrier\service;

use app\carrier\service\configurationResolver as ConfigurationResolver;

/**
 * The base class for 4px service class.
 */
class Carrier4pxBaseService
{
    /**
     * Helper constent when build requests that contain attachments.
     */
    const CRLF = "\r\n";

    /**
     * @var app\carrier\service\ConfigurationResolver Resolves configuration options.
     */
    private $resolver;

    /**
     * @var array Associative array storing the current configuration option values.
     */
    private $config;

    /**
     * @var string The production URL for the service.
     */
    private $productionUrl;

    /**
     * @var string The sandbox URL for the service.
     */
    private $sandboxUrl;

    /**
     * @param string $productionUrl The production URL.
     * @param string $sandboxUrl The sandbox URL.
     * @param array $config Configuration option values.
     */
    public function __construct(
        $productionUrl,
        $sandboxUrl,
        array $config
    ) {
        $this->resolver = new ConfigurationResolver(static::getConfigDefinitions());
        $this->config = $this->resolver->resolve($config);
        $this->productionUrl = $productionUrl;
        $this->sandboxUrl = $sandboxUrl;
    }

    /**
     * Get an array of service configuration option definitions.
     *
     * @return array
     */
    public static function getConfigDefinitions()
    {
        return [
            'sandbox' => [
                'valid'   => ['bool'],
                'default' => false
            ],
            'token' => [
                'valid' => ['string'],
                'required' => true
            ],
            'customerId' => [
                'valid' => ['string'],
                'required' => true
            ],
            'format' => [
                'valid' => ['string']
            ],
            'language' => [
                'valid' => ['string'],
                'default' => 'zh_CN'
            ]
        ];
    }

    /**
     * Method to get the service's configuration.
     *
     * @return mixed Returns an associative array of configuration options if no parameters are passed, otherwise returns the value for the specified configuration option.
     */
    public function getConfig($option = null)
    {
        return $option === null
            ? $this->config
            : (isset($this->config[$option])
                ? $this->config[$option]
                : null);
    }

    public function setConfig($configuration)
    {
        $this->config = $this->arrayMergeDeep(
            $this->config,
            $this->resolver->resolveOptions($configuration)
        );
    }

    /**
     * Sends an asynchronous API request.
     *
     * @param string $name The name of the operation.
     * @param \app\carrier\type\BaseType $request Request object containing the request information.
     * @param string The request path.
     *
     * @return object json decode.
     */
    protected function callOperationAsync($name, \app\carrier\type\BaseType $request, $path)
    {
        $url = $this->getUrl($path);
        
        $post_data = $request->toJson();
        
        $headers = $this->buildRequestHeaders($name, $request, $post_data);
        
	$response = $this->curl($url, $post_data, $headers);
        
        $result = json_decode($response);
        
        if (!$result) {           
            $result = new \stdClass();
            
            $result->errorCode = 400;
            
            preg_match('/<errorMsg\>(.*)<\/errorMsg>/', $response, $match);
            
            $result->errorMsg = $match ? $match[1]: 'System Error!!';
        }
        
        return $result;
    }

    /**
     * Helper function to return the URL as determined by the sandbox configuration option.
     *
     * @return string Either the production or sandbox URL.
     */
    private function getUrl($path)
    {
        $url = $this->getConfig('sandbox') ? $this->sandboxUrl : $this->productionUrl;
        $url .= '/'. $path 
                . '?token='. $this->getConfig('token');
        $url .= '&customerId='. $this->getConfig('customerId');
        $url .= '&language='. $this->getConfig('language');
        $url .= '&format=json';
        
        return $url;
    }

    /**
     * Builds the request body string.
     *
     * @param \app\carrier\type\BaseType $request Request object containing the request information.
     *
     * @return string The request body.
     */
    private function buildRequestBody(\app\carrier\type\BaseType $request)
    {
        if (!$request->hasAttachment()) {
            return $request->toRequestXml();
        } else {
            return $this->buildXopDocument($request).$this->buildAttachmentBody($request->attachment());
        }
    }

    /**
     * Builds the XOP document part of the request body string.
     *
     * @param \app\carrier\type\BaseType $request Request object containing the request information.
     *
     * @return string The XOP document part of request body.
     */
    private function buildXopDocument(\app\carrier\type\BaseType $request)
    {
        return sprintf(
            '%s%s%s%s%s',
            '--MIME_boundary'.self::CRLF,
            'Content-Type: application/xop+xml;charset=UTF-8;type="text/xml"'.self::CRLF,
            'Content-Transfer-Encoding: 8bit'.self::CRLF,
            'Content-ID: <request.xml@devbay.net>'.self::CRLF.self::CRLF,
            $request->toRequestXml().self::CRLF
        );
    }

    /**
     * Builds the attachment part of the request body string.
     *
     * @param array $attachment The attachement
     *
     * @return string The attachment part of request body.
     */
    private function buildAttachmentBody($attachment)
    {
        return sprintf(
            '%s%s%s%s%s%s',
            '--MIME_boundary'.self::CRLF,
            'Content-Type: '.$attachment['mimeType'].self::CRLF,
            'Content-Transfer-Encoding: binary'.self::CRLF,
            'Content-ID: <attachment.bin@devbay.net>'.self::CRLF.self::CRLF,
            $attachment['data'].self::CRLF,
            '--MIME_boundary--'
        );
    }

    /**
     * Helper function that builds the HTTP request headers.
     *
     * @param string $name The name of the operation.
     * @param \app\carrier\type $request Request object containing the request information.
     * @param string $body The request body.
     *
     * @return array An associative array of HTTP headers.
     */
    private function buildRequestHeaders($name, $request, $body)
    {
        $headers = array();

        if ($request->hasAttachment()) {
            $headers['Content-Type'] = 'multipart/related;boundary=MIME_boundary;type="application/xop+xml";start="<request.xml@devbay.net>";start-info="text/xml"';
        } else {
            $headers[] = 'Content-Type:application/json';
        }

        $headers[] ='Content-Length:' . strlen($body);

        return $headers;
    }

    /**
     * Extracts the XML from the response if it contains an attachment.
     *
     * @param string The XML response body.
     *
     * @return array first item is the XML part of response body and the second
     *               is an attachement if one was present in the API response.
     */
    private function extractXml($response)
    {
        /**
         * Ugly way of seeing if an attachment is present in the response.
         */
        if (strpos($response, 'application/xop+xml') === false) {
            return [$response, ['data' => null, 'mimeType' => null]];
        } else {
            return $this->extractXmlAndAttachment($response);
        }
    }

    /**
     * Extracts the XML and the attachment from the response if it contains an attachment.
     *
     * @param string The XML response body.
     *
     * @return string The XML part of response body.
     */
    private function extractXmlAndAttachment($response)
    {
        $attachment = ['data' => null, 'mimeType' => null];

        preg_match('/\r\n/', $response, $matches, PREG_OFFSET_CAPTURE);
        $boundary = substr($response, 0, $matches[0][1]);

        $xmlStartPos = strpos($response, '<?xml ');
        $xmlEndPos = strpos($response, $boundary, $xmlStartPos) - 2;
        $xml = substr($response, $xmlStartPos, $xmlEndPos - $xmlStartPos);

        preg_match('/\r\n\r\n/', $response, $matches, PREG_OFFSET_CAPTURE, $xmlEndPos);
        $attachmentStartPos = $matches[0][1] + 4;
        $attachmentEndPos = strpos($response, $boundary, $attachmentStartPos) - 2;
        $attachment['data'] = substr($response, $attachmentStartPos, $attachmentEndPos - $attachmentStartPos);

        $mimeTypeStartPos = strpos($response, 'Content-Type: ', $xmlEndPos) + 14;
        preg_match('/\r\n/', $response, $matches, PREG_OFFSET_CAPTURE, $mimeTypeStartPos);
        $mimeTypeEndPos = $matches[0][1];
        $attachment['mimeType'] = substr($response, $mimeTypeStartPos, $mimeTypeEndPos - $mimeTypeStartPos);

        return [$xml, $attachment];
    }

    /**
     * Derived classes must implement this method that will build the needed eBay http headers.
     *
     * @param string $operationName The name of the operation been called.
     *
     * @return array An associative array of eBay http headers.
     */
    // abstract protected function getEbayHeaders($operationName);

    /**
     * Sends a debug string of the request details.
     *
     * @param string $url API endpoint.
     * @param array  $headers Associative array of HTTP headers.
     * @param string $body The XML body of the POST request.
      */
    private function debugRequest($url, $headers, $body)
    {
        $str = $url.PHP_EOL;

        $str .= array_reduce(array_keys($headers), function ($str, $key) use ($headers) {
            $str .= $key.': '.$headers[$key].PHP_EOL;
            return $str;
        }, '');

        $str .= $body;

        $this->debug($str);
    }

    /**
     * Sends a debug string of the response details.
     *
     * @param string $body The XML body of the response.
      */
    private function debugResponse($body)
    {
        $this->debug($body);
    }

    /**
     * Sends a debug string via the attach debugger.
     */
    private function debug($str)
    {
        $debugger = $this->getConfig('debug');
        $debugger($str);
    }
    
    /**
     * 
     * @param string $url request url
     * @param string $post_data request parameters
     * @param array array An associative array of HTTP headers
     */
    private function curl($url,$post_data, $headers) {
        $ch = curl_init();      
        
        curl_setopt($ch, CURLOPT_URL, $url);
		
        curl_setopt($ch, CURLOPT_POST, 1);
                   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
				
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt ($ch, CURLOPT_HEADER, 0);
        
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
	$output = curl_exec($ch);
        
	curl_close($ch);
        
        return $output;
    }
    
    /**
     * Code taken from
     * https://api.drupal.org/api/drupal/includes!bootstrap.inc/function/drupal_array_merge_deep/7
     */
    public function arrayMergeDeep()
    {
        $args = func_get_args();
        return $this->arrayMergeDeepArray($args);
    }

    private function arrayMergeDeepArray($arrays)
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                // Renumber integer keys as array_merge_recursive() does. Note that PHP
                // automatically converts array keys that are integer strings (e.g., '1')
                // to integers.
                if (is_integer($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    // Recurse when both values are arrays.
                    $result[$key] = arrayMergeDeepArray(array($result[$key], $value));
                } else {
                    // Otherwise, use the latter value, overriding any previous value.
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}

