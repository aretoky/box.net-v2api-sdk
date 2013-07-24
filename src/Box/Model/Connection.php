<?php
/**
 * @package     Box
 * @subpackage  Box_Connection
 * @author      Chance Garcia
 * @copyright   (C)Copyright 2013 chancegarcia.com
 *
 * connection assumes a valid access token
 */

namespace Box\Model;
use Box\Model\Model;
use Box\Exception;
use Box\Model\Connection\Token\TokenInterface;
use Box\Model\Connection\ConnectionInterface;

class Connection extends Model implements ConnectionInterface
{

    protected $responseType = "code";
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $state;
    protected $requestType = "GET";

    protected $response;
    protected $responseClass;

    public function setResponseClass($responseClass = null)
    {
        $this->validateClass($responseClass,'ResponseInterface');
        $this->responseClass = $responseClass;
        return $this;
    }

    public function getResponseClass()
    {
        return $this->responseClass;
    }


    // relooking over auth flow, we have to assume app is already authorized externally. rewrite to use tokens for connection
    // may need to store the tokens
    public function connect()
    {

    }

    /**
     * @return resource
     */
    public function initCurl()
    {
        $ch = curl_init();
        $this->initCurlOpts($ch);
        return $ch;
    }

    /**
     * @param $ch
     */
    public function initCurlOpts($ch)
    {
        curl_setopt($ch , CURLOPT_RETURNTRANSFER , true);
        curl_setopt($ch , CURLOPT_SSL_VERIFYPEER , false);
    }

    /**
     * @param $ch
     * @return mixed
     */
    public function getCurlData($ch)
    {
        $data = curl_exec($ch);
        return $data;
    }

    /**
     * GET
     * @param $uri
     * @return mixed
     */
    public function query($uri)
    {
        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $uri);
        $data = $this->getCurlData($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * POST
     * @param       $uri
     * @param array $params
     * @throws \Box\Exception
     * @return mixed
     */
    public function post($uri,array $params = array())
    {
        if (!is_array($params))
        {
            throw new Exception("params must be an array",Exception::INVALID_INPUT);
        }

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = $this->getCurlData($ch);
        curl_close($ch);

        return $data;
    }



    public function setClientId($clientId = null)
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientSecret($clientSecret = null)
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setRedirectUri($redirectUri = null)
    {
        $this->redirectUri = $redirectUri;
        return $this;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function setRequestType($requestType = null)
    {
        $this->requestType = $requestType;
        return $this;
    }

    public function getRequestType()
    {
        return $this->requestType;
    }

    public function setResponse($response = null)
    {
        $this->response = $response;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponseType($responseType = null)
    {
        $this->responseType = $responseType;
        return $this;
    }

    public function getResponseType()
    {
        return $this->responseType;
    }

    public function setState($state = null)
    {
        $this->state = $state;
        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

}
