<?php
require('Response.php');

class OAuth2Client 
{
  public function __construct($client_id, $client_secret)
  {
    $this->client_id = $client_id;
    $this->client_secret = $client_secret;
    $this->token_endpoint = 'https://www.addressix.com/oauth2/v1/token';
  }
  
  public function getAccessToken($grant_type, array $parameters, array $extra_headers = array()) 
  {       
    $parameters['grant_type'] = $grant_type;
    $http_headers = $extra_headers;
    
    $parameters['client_id'] = $this->client_id;
    $parameters['client_secret'] = $this->client_secret;
    
    return $this->executeRequest($this->token_endpoint, $parameters, 'POST', $http_headers, 0);
  }

  public function setAccessToken($token)
  {
    $this->access_token = $token;
  }

    /**
     * Fetch a protected ressource
     *
     * @param string $url Protected resource URL
     * @param array  $parameters Array of parameters
     * @param string $http_method HTTP Method to use (POST, PUT, GET, HEAD, DELETE)
     * @param array  $http_headers HTTP headers
     * @param int    $form_content_type HTTP form content type to use
     * @return array
     */
    public function fetch($url, $parameters = array(), $http_method = 'GET', array $http_headers = array(), $form_content_type = 1)
    {
      $http_headers['Accept'] = 'application/json';
      if ($this->access_token) {
	$http_headers['Authorization'] = 'Bearer ' . $this->access_token;
      }
      return $this->executeRequest($url, $parameters, $http_method, $http_headers, $form_content_type);
    }

    public function getFormattedHeaders($headers)
    {
        $formattedHeaders = array();

        $combinedHeaders = array_change_key_case((array) $headers);

        foreach ($combinedHeaders as $key => $val) {
	  $key = trim(strtolower($key));
	  $fmh = $key . ': ' . $val;

	  $formattedHeaders[] = $fmh;
        }

        if (!array_key_exists('user-agent', $combinedHeaders)) {
            $formattedHeaders[] = 'user-agent: addressixoauth2-wp/1.0';
        }

        if (!array_key_exists('expect', $combinedHeaders)) {
            $formattedHeaders[] = 'expect:';
        }

        return $formattedHeaders;
    }


/**
 * Execute a request (with curl)
 *
 * @param string $url URL
 * @param mixed  $parameters Array of parameters
 * @param string $http_method HTTP Method
 * @param array  $http_headers HTTP Headers
 * @param int    $form_content_type HTTP form content type to use
 * @return array
 */
  private function executeRequest($url, $parameters = array(), $http_method = 'GET', array $http_headers = null, $form_content_type = 1)
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HEADER, true);
    $http_headers['Accept'] = 'application/json';

    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getFormattedHeaders($http_headers));

    switch($http_method) {
    case 'GET':
      if (is_array($parameters) && count($parameters) > 0) {
	$url .= '?' . http_build_query($parameters, null, '&');
      } elseif ($parameters) {
	$url .= '?' . $parameters;
      }
      break;
    case 'POST':
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
      break;
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $info = curl_getinfo($curl);

    $header_size = $info['header_size'];
    $header      = substr($response, 0, $header_size);
    $body        = substr($response, $header_size);
    $httpCode    = $info['http_code'];

    $resp = new AIX_UnirestResponse($httpCode, $body, $header, array());
    return $resp;
  }
}