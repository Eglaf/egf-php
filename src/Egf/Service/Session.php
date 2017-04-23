<?php

namespace Egf\Service;

use Egf\Util;

/**
 * Class Session
 * @url http://www.wikihow.com/Create-a-Secure-Session-Management-System-in-PHP-and-MySQL
 */
class Session extends \Egf\Ancient\Service {

    /** @var \mysqli */
    protected $db;


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Read/Write                                                 **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Get session variable.
     * @param string $sKey
     * @param mixed  $xDefault
     * @return null
     */
    public function get($sKey, $xDefault = NULL) {
        $this->startOnce();

        if (isset($_SESSION[$sKey])) {
            return $_SESSION[$sKey];
        }

        return $xDefault;
    }

    /**
     * Set session variable.
     * @param string $sKey
     * @param mixed  $xValue
     * @return $this
     */
    public function set($sKey, $xValue) {
        $this->startOnce();

        $_SESSION[$sKey] = $xValue;

        return $this;
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Init                                                       **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /** @var bool $bStarted It decides if the session is started or not. */
    protected $bStarted = FALSE;

    /**
     * Initialize. Called from construct.
     */
    protected function init() {
        // Set custom session functions.
        session_set_save_handler([$this, 'open'], [$this, 'close'], [$this, 'read'], [$this, 'write'], [$this, 'destroy'], [$this, 'gc']); // session_set_save_handler($this); ... open(params?)

        // This line prevents unexpected effects when using objects as save handlers.
        register_shutdown_function('session_write_close');
    }

    /**
     * Start session only once.
     */
    protected function startOnce() {
        if ( !$this->bStarted) {
            $this->startSession();
            $this->bStarted = TRUE;

            $this->gc();
        }
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * System                                                     **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Start session.
     * Parts ini_set('session.use_only_cookies', 1); AND ini_set('session.hash_bits_per_character', 6); are removed, because deprecated or default.
     */
    protected function startSession() {
        // Make sure the session cookie is not accessible via javascript.
        $bHttpOnly = TRUE;

        // Hash algorithm to use for the session. (use hash_algos() to get a list of available hashes.)
        $sSessionHash = 'sha512';

        // Check if hash is available
        if (in_array($sSessionHash, hash_algos())) {
            // Set the has function.
            ini_set('session.hash_function', $sSessionHash);
        }

        // Get session cookie parameters
        $aCookieParams = session_get_cookie_params();
        
        // Set the parameters
        session_set_cookie_params($aCookieParams["lifetime"], $aCookieParams["path"], $aCookieParams["domain"], $this->getConfig('is_https', FALSE), $bHttpOnly);

        // Change the session name
        session_name($this->getConfig('session_name', 'egf-ses'));

        // Now we cat start the session
        session_start();

        // This line regenerates the session and delete the old one. It also generates a new encryption key in the database.
        session_regenerate_id(TRUE);
    }

    /**
     * Write to session.
     * @param $sId
     * @param $sData
     * @return bool
     */
    public function write($sId, $sData) {
        // Get unique key
        $sKey = $this->getKey($sId);
        // Encrypt the data
        $sData = $this->encrypt($sData, $sKey);
        $dtNow = (new \DateTime())->format('Y-m-d H:i:s');

        if ( !isset($this->w_stmt)) {
            $this->w_stmt = $this->db->prepare("REPLACE INTO egf_session (id, date, data, session_key) VALUES (?, ?, ?, ?)");
        }

        $this->w_stmt->bind_param('ssss', $sId, $dtNow, $sData, $sKey);
        $this->w_stmt->execute();

        return TRUE;
    }

    /**
     * Read from session.
     * @param $sId
     * @return mixed
     */
    public function read($sId) {
        if ( !isset($this->read_stmt)) {
            $this->read_stmt = $this->db->prepare("SELECT data FROM egf_session WHERE id = ? LIMIT 1");
        }

        $this->read_stmt->bind_param('s', $sId);
        $this->read_stmt->execute();
        $this->read_stmt->store_result();
        $this->read_stmt->bind_result($data);
        $this->read_stmt->fetch();
        $key = $this->getKey($sId);
        $data = $this->decrypt($data, $key);

        return $data;
    }

    /**
     * Delete old session contents.
     * @return bool
     */
    public function gc() {
        if ( !isset($this->gc_stmt)) {
            $this->gc_stmt = $this->db->prepare("DELETE FROM egf_session WHERE date < ?");
        }
        $dtOld = (new \DateTime)->modify('-1 hour')->format('Y-m-d H:i:s');
        $this->gc_stmt->bind_param('s', $dtOld);
        $this->gc_stmt->execute();

        return TRUE;
    }

    /**
     * Open connection.
     * @return bool
     */
    public function open() {
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $name = 'egf-teszt';
        $mysqli = new \mysqli($host, $user, $pass, $name);
        $this->db = $mysqli;

        return TRUE;
    }

    /**
     * Close connection.
     * @return bool
     */
    public function close() {
        $this->db->close();

        return TRUE;
    }

    /**
     * Destroy session.
     * @param $sId
     * @return bool
     */
    public function destroy($sId) {
        if ( !isset($this->delete_stmt)) {
            $this->delete_stmt = $this->db->prepare("DELETE FROM egf_session WHERE id = ?");
        }
        $this->delete_stmt->bind_param('s', $sId);
        $this->delete_stmt->execute();

        return TRUE;
    }

    /**
     * Get key.
     * @param string $sId
     * @return string
     */
    protected function getKey($sId) {
        if ( !isset($this->key_stmt)) {
            $this->key_stmt = $this->db->prepare("SELECT session_key FROM egf_session WHERE id = ? LIMIT 1");
        }
        $this->key_stmt->bind_param('s', $sId);
        $this->key_stmt->execute();
        $this->key_stmt->store_result();
        if ($this->key_stmt->num_rows == 1) {
            $this->key_stmt->bind_result($sId);
            $this->key_stmt->fetch();

            return $sId;
        }
        else {
            return hash('sha512', uniqid(Util::getRandomString(64, 'secure')), TRUE);
        }
    }

    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Encrypt/Decrypt                                            **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Encrypt data.
     * @param $sData
     * @param $sKey
     * @return mixed
     */
    protected function encrypt($sData, $sKey) {
        $sKey = substr(hash('sha256', $this->getParam('secret') . $sKey .$this->getParam('secret')), 0, 32);
        $iIvSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $sIv = mcrypt_create_iv($iIvSize, MCRYPT_RAND);

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $sKey, $sData, MCRYPT_MODE_ECB, $sIv));
    }

    /**
     * Decrypt data.
     * @param $sData
     * @param $sKey
     * @return mixed
     */
    protected function decrypt($sData, $sKey) {
        $sKey = substr(hash('sha256', $this->getParam('secret') . $sKey . $this->getParam('secret')), 0, 32);
        $iIvSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $sIv = mcrypt_create_iv($iIvSize, MCRYPT_RAND);

        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $sKey, base64_decode($sData), MCRYPT_MODE_ECB, $sIv), "\0");
    }

}