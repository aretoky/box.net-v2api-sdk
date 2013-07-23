<?php
/**
 * @package     Box
 * @subpackage  Box_Client
 * @author      Chance Garcia
 * @copyright   (C)Copyright 2013 chancegarcia.com
 */

namespace Box\Client;

use Box\Exception;
use Box\Model\Connection\ConnectionInterface;
use Box\Model\File;
use Box\Model\Folder;
use Box\Model;
use Box\Model\Connection\Token\TokenInterface;

class Client extends Model
{
    CONST AUTH_URI = "https://www.box.com/api/oauth2/authorize";
    CONST TOKEN_URI = "https://www.box.com/api/oauth2/token";
    CONST REVOKE_URI = "https://www.box.com/api/oauth2/revoke";

    protected $_state;

    /**
     * @var ConnectionInterface
     */
    protected $_connection;
    protected $_folders;
    protected $_files;

    /**
     * @var TokenInterface
     */
    protected $_token;

    protected $_authorizationCode;
    protected $_clientId;
    protected $_clientSecret;
    protected $_redirectUri;

    protected $_deviceId;
    protected $_deviceName;


    /**
     * allow for class injection by using an interface for these classes
     */
    protected $_folderClass;
    protected $_fileClass;
    protected $_connectionClass;
    protected $_tokenClass;

    public function getAccessToken()
    {
        $connection = $this->getConnection();
        $params['grant_type'] = 'authorization_code';
        $params['code'] = $this->getAuthorizationCode();
        $params['client_id'] = $this->getClientId();
        $params['client_secret'] = $this->getClientSecret();

        $redirectUri = $this->getRedirectUri();
        if (null !== $redirectUri)
        {
            $params['redirect_uri'] = $redirectUri;
        }



        $data = $connection->post(self::TOKEN_URI,$params);

        if (array_key_exists('error',$data))
        {
            $this->error($data);
        }

        $token = $this->getToken();
        $this->setTokenData($token , $data);

        return $token;

    }

    public function refreshToken()
    {

        // outside script will set token via getAccessToken
        $token = $this->getToken();

        $params['refresh_token'] = $token->getRefreshToken();
        $params['client_id'] = $this->getClientId();
        $params['client_secret'] = $this->getClientSecret();
        $params['grant_type'] = 'refresh_token';

        $deviceId = $this->getDeviceId();
        if (null !== $deviceId)
        {
            $params['device_id'] = $deviceId;
        }

        $deviceName = $this->getDeviceName();
        if (null !== $deviceName)
        {
            $params['device_name'] = $deviceName;
        }

        $connection = $this->getConnection();

        $data = $connection->post(self::TOKEN_URI,$params);

        if (array_key_exists('error',$data))
        {
            $this->error($data);
        }

        $this->setTokenData($token,$data);

        $this->setToken($token);

        return $token;

    }

    public function getAuthorizationHeader()
    {
        $token = $this->getToken();

        $header = "Authorization: Bearer " . $token->getAccessToken();

        return $header;
    }

    /**
     * @param $token \Box\Model\Connection\Token\TokenInterface
     * @param $data
     * @return \Box\Model\Connection\Token\TokenInterface
     */
    public function setTokenData($token , $data)
    {
        $token->setAccessToken($data['access_token']);
        $token->setExpiresIn($data['expires_in']);
        $token->setTokenType($data['token_type']);
        $token->setRefreshToken($data['refresh_token']);

        return $token;
    }

    /**
     * @param $token \Box\Model\Connection\Token\TokenInterface
     */
    public function destroyToken($token)
    {
        $params['client_id'] = $this->getClientId();
        $params['client_secret'] = $this->getClientSecret();
        $params['token'] = $token->getAccessToken();

        $connection = $this->getConnection();

        $data = $connection->post(self::REVOKE_URI,$params);

        return $data;
    }

    /**
     * @param $data
     * @throws \Box\Exception
     */
    public function error($data)
    {
        $exception = new Exception($data['error']);
        $exception->setError($data['error']);
        $exception->setErrorDescription($data['error_description']);
        throw $exception;
    }

    public function auth()
    {
        // build get query to auth uri
        $query = $this->buildAuthQuery();

        // send get query to auth uri (auth uri will redirect to app redirect uri)
        $connection = $this->getConnection();

        // can't get return data b/c of redirect
        $connection->query($query);
    }

    public function buildAuthQuery()
    {
        $uri = self::AUTH_URI . '?';
        $params = array();

        $params['response_type'] = "code";

        $clientId = $this->getClientId();
        $params['client_id'] = $clientId;

        $state = $this->getState();
        $params['state'] = $state;

        $query = $this->buildQuery($params);
        $uri .= '?' . $query;

        $redirectUri = $this->getRedirectUri();

        if (null !== $redirectUri)
        {
            $redirectUri = urlencode($redirectUri);
            $uri .= "&redirect_uri=" . $redirectUri;
        }

        return $uri;
    }

    public function setClientId($clientId = null)
    {
        $this->_clientId = $clientId;
        return $this;
    }

    public function getClientId()
    {
        return $this->_clientId;
    }

    public function setClientSecret($clientSecret = null)
    {
        $this->_clientSecret = $clientSecret;
        return $this;
    }

    public function getClientSecret()
    {
        return $this->_clientSecret;
    }

    public function setRedirectUri($redirectUri = null)
    {
        $this->_redirectUri = $redirectUri;
        return $this;
    }

    public function getRedirectUri()
    {
        return $this->_redirectUri;
    }


    public function setAuthorizationCode($authorizationCode = null)
    {
        $this->_authorizationCode = $authorizationCode;
        return $this;
    }

    public function getAuthorizationCode()
    {
        return $this->_authorizationCode;
    }

    public function setToken($token = null)
    {
        $this->_token = $token;
        return $this;
    }

    public function getToken()
    {
        if (null === $this->_token)
        {
            $tokenClass = $this->getTokenClass();
            $token = new $tokenClass();
            $this->_token = $token;
        }
        return $this->_token;
    }

    public function setTokenClass($tokenClass = null)
    {
        $this->validateClass($tokenClass,'TokenInterface');
        $this->_tokenClass = $tokenClass;
        return $this;
    }

    public function getTokenClass()
    {
        return $this->_tokenClass;
    }

    public function setConnectionClass($connectionClass = null)
    {
        $this->validateClass($connectionClass,'ConnectionInterface');

        $this->_connectionClass = $connectionClass;

        return $this;
    }

    public function getConnectionClass()
    {
        return $this->_connectionClass;
    }

    public function setConnection($connection = null)
    {
        if (!$connection instanceof ConnectionInterface)
        {
            throw new Exception("Invalid Class",Exception::INVALID_CLASS);
        }
        $this->_connection = $connection;
        return $this;
    }

    public function getConnection()
    {
        if (null === $this->_connection)
        {
            $connectionClass = $this->getConnectionClass();
            $connection = new $connectionClass();
            $this->_connection = $connection;
        }

        return $this->_connection;
    }

    public function setFileClass($fileClass = null)
    {
        $this->validateClass($fileClass,'FileInterface');
        $this->_fileClass = $fileClass;
        return $this;
    }

    public function getFileClass()
    {
        return $this->_fileClass;
    }

    /**
     * @todo determine best validation for this
     * @param null $files
     * @return $this
     */
    public function setFiles($files = null)
    {
        $this->_files = $files;
        return $this;
    }

    public function getFiles()
    {
        return $this->_files;
    }

    public function setFolderClass($folderClass = null)
    {
        $this->validateClass($folderClass,'FolderInterface');
        $this->_folderClass = $folderClass;
        return $this;
    }

    public function getFolderClass()
    {
        return $this->_folderClass;
    }

    public function setFolders($folders = null)
    {
        $this->_folders = $folders;
        return $this;
    }

    public function getFolders()
    {
        return $this->_folders;
    }


    public function getFolder()
    {
        $sFolderClass = $this->getFolderClass();

        $oFolder = new $sFolderClass();

        return $oFolder;
    }

    public function setDeviceId($deviceId = null)
    {
        $this->_deviceId = $deviceId;
        return $this;
    }

    public function getDeviceId()
    {
        return $this->_deviceId;
    }

    public function setDeviceName($deviceName = null)
    {
        $this->_deviceName = $deviceName;
        return $this;
    }

    public function getDeviceName()
    {
        return $this->_deviceName;
    }

    public function setState($state = null)
    {
        $this->_state = $state;
        return $this;
    }

    public function getState()
    {
        return $this->_state;
    }


}