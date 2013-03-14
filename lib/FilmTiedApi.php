<?php

/**
* FilmTiedApi API Library
*/

class FilmTiedApiException extends Exception
{}


class FilmTiedApi
{
    /**
     * Pnop api server address
     * @var String
     */
    protected $_apiServerAddress = "http://api.filmtied.com";

    /**
     * User agent
     * @var String
     */
    protected $_userAgent = "FilmTied API PHP Library";

    /**
     * Library version
     * @var String
     */
    protected $_version = "1.1";

    /**
    * Connection Timeout
    * @var Integer
    */
    protected $_connectionTimeOut = 5;

    /**
     * Timeout for getting data
     * @var Integer
     */
    protected $_timeOut = 5;

    /**
     * jsonrpc
     * @var String
     */
    protected $_jsonrpc = '2.0';

    /**
     * Cache
     * @var Resource
     */
    protected $_cache;

    /**
     * Cache Type
     * @var String
     */
    protected $_cacheType;

    /**
     * Cache Expiration
     * @var Integer
     */
    protected $_cacheExpiration = 600;

    /**
     * json error messages
     */
    protected $_jsonErrorMessages = array(
        JSON_ERROR_NONE             => 'No error has occurred',
        JSON_ERROR_DEPTH            => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH   => 'Invalid or malformed JSON',
        JSON_ERROR_CTRL_CHAR        => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX           => 'Syntax error',
        JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    );

    /**
     * Constructor
     * @param String $token
     * @param Array $params:
     *        apiServerAddress   - (string) our api server url, hardcoded in this file, only for testing purposes (optional)
     *        connectionTimeOut  - (int) connection timeout, default 5s (optional)
     *        timeOut            - (int) data retrive timeout, default 5s (optional)
     *        cache              - (string) users cache server type: 'memcache' or 'memcached', enter to enable caching (optional)
     *        cacheServerAddress - (string) users cache server adres (optional, required if cache is enabled)
     *        cacheServerPort    - (int) users cache server port (optional, required if cache is enabled )
     *        cacheExpiration    - (string) user cache expiration time, default 600s (10 min) (optional)
     *
     *
     * @example
     *
     *    $token = '#someSecredToken#';
     *
     *    $params = array(
     *        'cache'                 => 'memcache',
     *        'cacheServerAddress'    => '127.0.0.1',
     *        'cacheServerPort'       => '11211',
     *    );
     *
     *    $filmTiedApi = new FilmTiedApi($token, $params);
     *
     */
    public function __construct($token, $params = null)
    {
        if (!function_exists('json_encode') || !function_exists('json_decode')) {
            throw new FilmTiedApiException('This software requires json_encode and json_decode functions (PHP 5.2)');
        }

        if (!function_exists('curl_init')) {
            throw new FilmTiedApiException('This software requires cURL extension.');
        }

        $this->_token = $token;

        if ($params && is_array($params) && count($params) > 0) {
            if (isset($params['apiServerAddress'])) $this->_apiServerAddress = $params['apiServerAddress'];
            if (isset($params['connectionTimeOut'])) $this->_connectionTimeOut = (int)$params['connectionTimeOut'];
            if (isset($params['timeOut'])) $this->_timeOut = (int)$params['timeOut'];
            if (isset($params['cache']) && isset($params['cacheServerAddress']) && isset($params['cacheServerPort'])) {
                if ($params['cache'] === 'memcache') {
                    $this->_cache = new Memcache();
                    $this->_cache->connect($params['cacheServerAddress'], (int)$params['cacheServerPort']);
                    $this->_cacheType = 'Memcache';
                } elseif ($params['cache'] === 'memcached')  {
                    $this->_cache = new Memcached();
                    $this->_cache->addServer($params['cacheServerAddress'], (int)$params['cacheServerPort']);
                    $this->_cacheType = 'Memcached';
                }
            }

            if (isset($params['cache']) && $params['cache'] === 'file' && isset($params['cacheDirPath'])) {
                $this->_cache = new FileCache();
                $this->_cache->setCacheDir($params['cacheDirPath']);
                if (isset($params['cacheExpiration'])) {
                    $this->_cache->setCacheExpiration($params['cacheExpiration']);
                }
                $this->_cacheType = 'File';
            }

            if (isset($params['cache'])
                && $params['cache'] === 'database'
                && isset($params['cacheServerAddress'])
                && isset($params['cacheServerUsername'])
                && isset($params['cacheServerPassword'])
                && isset($params['cacheServerDbName'])
                && isset($params['cacheServerTable'])
                ) {
                $this->_cache = new DatabaseCache(
                    $params['cacheServerAddress'],
                    $params['cacheServerUsername'],
                    $params['cacheServerPassword'],
                    $params['cacheServerDbName'],
                    $params['cacheServerTable'],
                    !empty($params['cacheServerPort']) ? $params['cacheServerPort'] : null,
                    !empty($params['cacheServerCharset']) ? $params['cacheServerCharset'] : null
                );

                if (isset($params['cacheExpiration'])) {
                    $this->_cache->setCacheExpiration($params['cacheExpiration']);
                }

                if (!empty($params['cacheServerAutoCreateTable']) && $params['cacheServerAutoCreateTable'] == true) {
                    $this->_cache->setAutoCreateTable(true);
                }

                $this->_cacheType = 'Database';
            }

            if (isset($params['cacheExpiration'])) {
                $this->_cacheExpiration = (int)$params['cacheExpiration'];
            }

        }
    }

    /**
    * Make request
    *
    * @param String $method
    * @param Array $params
    * @return void
    */
    protected function processRequest($jsonData)
    {
        if ($this->_cache) {
            $cacheName = 'FilmtiedApi_' . md5($jsonData);
            $result = $this->_cache->get($cacheName);
        }

        if (empty($result)) {
            $ch = curl_init($this->_apiServerAddress);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_userAgent." v.".$this->_version);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_timeOut);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->_connectionTimeOut);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData))
            );

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new FilmTiedApiException(curl_error($ch));
            }

            curl_close($ch);

            if (!empty($result) && $this->_cache) {
                if ($this->_cacheType === 'Memcache') {
                    $this->_cache->set($cacheName, $result, false, $this->_cacheExpiration);
                } else {
                    $this->_cache->set($cacheName, $result, $this->_cacheExpiration);
                }
            }
        }

        return $result;
    }

    /**
    * prepare json
    *
    * @param String $method
    * @param Array $params
    * @return void
    */
    protected function prepareJson($method, $params)
    {
        if (!$method || !is_array($params)) {
            return null;
        }

        $params['token'] = $this->_token;

        $data = array(
            'jsonrpc'   => $this->_jsonrpc,
            'method'    => $method,
            'params'    => $params,
            'id'        => $this->_cache ? 1 : microtime(true)
        );

        $jsonData = json_encode($data);

        if (empty($jsonData)) {
            throw new FilmTiedApiException('json_encode: ' . $this->_jsonErrorMessages[json_last_error()]);
        }

        return $jsonData;
    }

    /**
    * process outpur json
    *
    * @param String $method
    * @param Array $params
    * @return void
    */
    protected function getResult($jsonData)
    {
        $data = json_decode($jsonData, true);

        if (empty($data)) {
            throw new FilmTiedApiException('json_decode: ' . $this->_jsonErrorMessages[json_last_error()]);
        }

        if (array_key_exists('result', $data)) {
            return $data['result'];
        } elseif (array_key_exists('error', $data)) {
            throw new FilmTiedApiException($data['error']['message']);
        }

        return '';
    }

    /**
     * Find FilmTied item corresponding to imdb.com url
     *
     * @param String $imdbUrl
     * @return String
     */
    public function changeUrl($imdbUrl)
    {
        if (!$imdbUrl) {
            throw new FilmTiedApiException('Missing required param.');
        }

        $jsonData = $this->prepareJson('changeUrl', array('url' => trim($imdbUrl)));
        $jsonResult = $this->processRequest($jsonData);
        $data = $this->getResult($jsonResult);

        return $data;
    }


    /**
     * Get FilmTied item data by url
     *
     * @param String $url
     * @param int $imageSize [1,2,3]
     * @return Array
     */
    public function get($url, $imageSize = 2)
    {
        if (!$url) {
            throw new FilmTiedApiException('Missing required param.');
        }

        $params = array('url' => $url);

        if ($imageSize && is_numeric($imageSize)) {
            $params['imageSize'] = (int) $imageSize;
        }

        $jsonData = $this->prepareJson('get', $params);
        $jsonResult = $this->processRequest($jsonData);
        $data = $this->getResult($jsonResult);

        return $data;
    }

    /**
     * Search FilmTied for given query
     *
     * @param string $query search phrase
     * @param int $page
     * @param int $limit
     * @param string $type [movies|tv-series]
     * @param int $imageSize [1,2,3]
     * @return array
     */
    public function search($query, $page = 1, $limit = 15, $type = null, $imageSize = 2)
    {
        $query = trim($query);

        if (!$query) {
            throw new FilmTiedApiException('Missing required param.');
        }

        $params = array('query' => $query);

        if ($page && is_numeric($page)) {
            $params['page'] = (int) $page;
        }

        if ($limit && is_numeric($limit)) {
            $params['limit'] = (int) $limit;
        }

        if ($imageSize && is_numeric($imageSize)) {
            $params['imageSize'] = (int) $imageSize;
        }

        if ($type && in_array($type, array('movies', 'tv-series'))) {
            $params['type'] = $type;
        }

        $jsonData = $this->prepareJson('search', $params);
        $jsonResult = $this->processRequest($jsonData);
        $data = $this->getResult($jsonResult);

        return $data;
    }
}


class FileCache
{
    protected $_cacheDir = '';
    protected $_cacheExpiration = 604800;

    /**
     * set(
     * @param string $key
     * @param type $data
     * @param int $dump
     */
    public function set($key, $data, $dump = null)
    {
        $result = false;
        $filename = $this->_fileName($key);
        $filepath = $this->_filePath($filename);
        $serializedData = serialize($data);

        $this->_prepareDirectories($filepath, $filename);

        if (false !== file_put_contents($this->getCacheDir() . '/' . $filepath . '/' . $filename, $serializedData)) {
            $result = true;
        }

        return $result;
    }

    /**
     * get
     * @param string $key
     */
    public function get($key)
    {
        $filename = $this->_fileName($key);
        $filepath = $this->_filePath($filename);
        $file = $this->getCacheDir() . '/' . $filepath . '/' . $filename;

        $time = @filemtime($file);
        $now = time();
        if ($time === false || ($now - $time) > $this->_cacheExpiration) {
            return false;
        }

        $serializedData = file_get_contents($file);
        $data = unserialize($serializedData);
        return $data;
    }

    /**
     * setCacheDir
     * @param string $path
     * @throws Exception
     */
    public function setCacheDir($path)
    {
        if (!is_dir($path)) {
            throw new Exception('Cache dir "' . $path . '" must be a directory');
        }
        if (!is_writable($path)) {
            throw new Exception('Cache dir "' . $path . '" is not writable');
        }
        $this->_cacheDir = $path;
    }

    /**
     * getCacheDir
     * @return string
     */
    public function getCacheDir()
    {
        return $this->_cacheDir;
    }

    /**
     * setCacheExpiration
     * @param int $cacheExpiration
     */
    public function setCacheExpiration($cacheExpiration)
    {
        $this->_cacheExpiration = $cacheExpiration;
    }

    /**
     * getCacheExpiration
     * @return int
     */
    public function getCacheExpiration()
    {
        return $this->_cacheExpiration;
    }

    /**
     * _fileName
     * @param string $key
     * @return string
     */
    protected function _fileName($key)
    {
        return sha1($key);
    }

    /**
     * _filePath
     * @param string $filename
     * @return string
     */
    protected function _filePath($filename)
    {
        $path = $filename{0} . $filename{1} . '/' . $filename{2} . $filename{3} . '/' . $filename{4} . $filename{5};
        return $path;
    }

    /**
     * _prepareDirectories
     * @param string $filepath
     * @param string $filename
     * @return boolean
     */
    protected function _prepareDirectories($filepath, $filename)
    {
        if (file_exists($this->getCacheDir() . '/' . $filepath . '/' . $filename)) {
            return true;
        }

        $dirsToCreate = array();
        $dirs = explode('/', $filepath);

        $dirsCount = count($dirs);
        foreach ($dirs as $key => $dir) {
            for ($i = 0; $i < $dirsCount; $i++) {
                if ($i >= ($dirsCount - $key)) {
                    break;
                }
                if (!isset($dirsToCreate[$i])) {
                    $dirsToCreate[$i] = $dir;
                } else {
                    $dirsToCreate[$i].= '/' . $dir;
                }
            }
        }
        $a = -1;
        foreach ($dirsToCreate as $key => $dir) {
            if (!is_dir($this->getCacheDir() . '/' . $dir)) {
                $a = $key;
            } else {
                break;
            }
        }

        if ($a > -1) {
            for ($i = $a; $i >= 0; $i--) {
                mkdir($this->getCacheDir() . '/' . $dirsToCreate[$i]);
            }
        }
    }
}


class DatabaseCache
{
    protected $_db;
    protected $_dbHost;
    protected $_dbPort;
    protected $_dbUsername;
    protected $_dbPassword;
    protected $_dbName;
    protected $_dbTable;
    protected $_dbCharset;

    protected $_autoCreateTable = false;
    protected $_cacheExpiration = 604800;

    /**
     * __construct
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string $table
     * @param string $port
     * @param string $charset
     */
    public function __construct($host, $username, $password, $database, $table, $port = null, $charset = 'utf8')
    {
        $this->_dbHost = $host;
        $this->_dbUsername = $username;
        $this->_dbPassword = $password;
        $this->_dbName = $database;
        $this->_dbTable = $table;
        $this->_dbPort = $port;
        $this->_dbCharset = $charset;
    }

    /**
     * _connect
     * @throws Exception
     */
    protected function _connect()
    {
        if (!$this->_isConnected()) {
            $this->_db = mysql_connect($this->_dbHost, $this->_dbUsername, $this->_dbPassword);
            if (!mysql_select_db($this->_dbName)) {
                throw new Exception(mysql_error());
            }
            if (!mysql_query('SET NAMES ' . $this->_dbCharset) ) {
                throw new Exception(mysql_error());
            }
        }
    }

    /**
     * _isConnected
     * @return bool
     */
    protected function _isConnected()
    {
        return (!empty($this->_db));
    }

    /**
     * get
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->_connect();

        $query = "SELECT * FROM " . $this->_dbName . '.' .$this->_dbTable;
        $query.= " WHERE hash_name = CRC32('" . mysql_real_escape_string($key) . "') AND name = '" . mysql_real_escape_string($key) . "'";

        $result = mysql_query($query);

        if (empty($result)) {
            if (mysql_errno() == 1146 && $this->_autoCreateTable) {
                $this->createCacheTable();
                return false;
            } else {
                throw new Exception(mysql_errno() . ' ' . mysql_error());
            }
        }
        $row = mysql_fetch_assoc($result);
        $data = false;

        if (!empty($row['time'])) {
            $time =  strtotime($row['time']);
            if ((time() - $time) > $this->getCacheExpiration()) {
                return false;
            }
        }

        if (isset($row['data'])) {
            $data = unserialize($row['data']);
        }

        return $data;
    }

    /**
     * set
     * @param string $key
     * @param type $data
     * @param int $dump
     * @throws Exception
     */
    public function set($key, $data, $dump = null)
    {
        $this->_connect();
        $serializedData = serialize($data);

        $query = "INSERT INTO " . $this->_dbName . '.' .$this->_dbTable . " (name, hash_name, data, time)";
        $query.= sprintf(" VALUES ('%s', CRC32('%s'), '%s', NOW())", mysql_real_escape_string($key), mysql_real_escape_string($key), mysql_real_escape_string($serializedData));
        $query.= sprintf(" ON DUPLICATE KEY UPDATE time = NOW(), data = '%s'", mysql_real_escape_string($serializedData));

        if (!mysql_query($query)) {
            throw new Exception(mysql_errno() . ' ' . mysql_error());
        }
    }

    /**
     * createCacheTable
     * @throws Exception
     */
    public function createCacheTable()
    {
        $this->_connect();
        $query = "
        CREATE TABLE `" . $this->_dbTable . "` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(128) NOT NULL,
          `hash_name` int(10) unsigned NOT NULL,
          `data` text,
          `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_name` (`name`),
          KEY `hash_name` (`hash_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        if (!mysql_query($query)) {
            throw new Exception(mysql_errno() . ' ' . mysql_error());
        }
     }

    /**
     * setCacheExpiration
     * @param int $cacheExpiration
     */
    public function setCacheExpiration($cacheExpiration)
    {
        $this->_cacheExpiration = $cacheExpiration;
    }

    /**
     * getCacheExpiration
     * @return int
     */
    public function getCacheExpiration()
    {
        return $this->_cacheExpiration;
    }

    /**
     * setAutoCreateTable
     * @param bool $value
     */
    public function setAutoCreateTable($value)
    {
        $this->_autoCreateTable = $value;
    }
}

?>
