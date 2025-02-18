<?php
namespace Jenga\App\Request\Handlers;

use Jenga\App\Core\App;
use Jenga\App\Models\ORM;
use Jenga\MyProject\Config;
use Jenga\App\Request\Session;

class SessionHandler implements \SessionHandlerInterface {

    public $link;
    public $session_lifetime;
    public $lock_timeout;
    public $lock_to_ip;
    public $lock_to_user_agent;
    public $securitycode;
    public $gc_probability;
    public $gc_divisor;
            
    private $table_name;
    private $flashdata;
    private $flashdata_varname;
    
    function __construct(ORM $db, Config $config){
        
        // store the connection link
        $this->link = $db->table($config->session_table);

        $security_code = $config->session_key; 
        $lock_to_user_agent = $config->lock_to_user_agent;
        $lock_to_ip = $config->lock_to_ip;
        $table_name = $config->session_table; 
        $lock_timeout = $config->lock_timeout;
        
        // get session lifetime
        $this->session_lifetime = ini_get('session.gc_maxlifetime');
        
        // we'll use this later on in order to try to prevent HTTP_USER_AGENT spoofing
        $this->security_code = $security_code;

        // some other defaults
        $this->lock_to_user_agent = $lock_to_user_agent;
        $this->lock_to_ip = $lock_to_ip;

        // the table to be used by the class
        $this->table_name = $config->dbprefix.$table_name;

        // the maximum amount of time (in seconds) for which a process can lock the session
        $this->lock_timeout = $lock_timeout;

        // register the new handler
        session_set_save_handler(
            array(&$this, 'open'),
            array(&$this, 'close'),
            array(&$this, 'read'),
            array(&$this, 'write'),
            array(&$this, 'destroy'),
            array(&$this, 'gc')
        );

        // the name for the session variable that will be created upon script execution
        // and destroyed when instantiating this library, and which will hold information
        // about flashdata session variables
        $this->flashdata_varname = '_session_flashdata_ec3asbuiad';

        // assume no flashdata
        $this->flashdata = array();

        // if there are any flashdata variables that need to be handled
        if (Session::has($this->flashdata_varname)) {

            // store them
            $this->flashdata = unserialize(Session::get($this->flashdata_varname));

            // and destroy the temporary session variable
            unset(Session::delete($this->flashdata_varname));
        }

        // handle flashdata after script execution
        register_shutdown_function(array($this, '_manage_flashdata'));

    }   

    /**
     *  Get the number of active sessions - sessions that have not expired.
     *
     *  <i>The returned value does not represent the exact number of active users as some sessions may be unused
     *  although they haven't expired.</i>
     *
     *  <code>
     *  // first, connect to a database containing the sessions table
     *
     *  //  include the class
     *  require 'path/to/session.php';
     *
     *  //  start the session
     *  //  where $dbAdapter is a connection link returned by mysqli_connect
     *  $session = new session($dbAdapter, 'sEcUr1tY_c0dE');
     *
     *  //  get the (approximate) number of active sessions
     *  $active_sessions = $session->get_active_sessions();
     *  </code>
     *
     *  @return integer     Returns the number of active (not expired) sessions.
     */
    public function get_active_sessions(){

        // call the garbage collector
        $this->gc();
        
        $this->link->show();
        $count = $this->link->count();

        // return the number of found rows
        return $count;
    }
    
    public function get_settings(){

        // get the settings
        $gc_maxlifetime = ini_get('session.gc_maxlifetime');
        $gc_probability = ini_get('session.gc_probability');
        $gc_divisor     = ini_get('session.gc_divisor');

        // return them as an array
        return array(
            'session.gc_maxlifetime'    =>  $gc_maxlifetime . ' seconds (' . round($gc_maxlifetime / 60) . ' minutes)',
            'session.gc_probability'    =>  $gc_probability,
            'session.gc_divisor'        =>  $gc_divisor,
            'probability'               =>  $gc_probability / $gc_divisor * 100 . '%',
        );

    }

    /**
     *  Regenerates the session id.
     *
     *  <b>Call this method whenever you do a privilege change in order to prevent session hijacking!</b>
     *
     *  <code>
     *  // first, connect to a database containing the sessions table
     *
     *  //  include the class
     *  require 'path/to/session.php';
     *
     *  //  start the session
     *  //  where $dbAdapter is a connection link returned by mysqli_connect
     *  $session = new session($dbAdapter, 'sEcUr1tY_c0dE');
     *
     *  //  regenerate the session's ID
     *  $session->regenerate_id();
     *  </code>
     *
     *  @return void
     */
    public function regenerate_id(){

        // saves the old session's id
        $old_session_id = session_id();

        // regenerates the id
        // this function will create a new session, with a new id and containing the data from the old session
        // but will not delete the old session
        session_regenerate_id();

        // because the session_regenerate_id() function does not delete the old session,
        // we have to delete it manually
        $this->destroy($old_session_id);
    }

    /**
     *  Sets a "flashdata" session variable which will only be available for the next server request, and which will be
     *  automatically deleted afterwards.
     *
     *  Typically used for informational or status messages (for example: "data has been successfully updated").
     *
     *  <code>
     *  // first, connect to a database containing the sessions table
     *
     *  // include the library
     *  require 'path/to/session.php';
     *
     *  //  start the session
     *  //  where $dbAdapter is a connection link returned by mysqli_connect
     *  $session = new session($dbAdapter, 'sEcUr1tY_c0dE');
     *
     *  // set "myvar" which will only be available
     *  // for the next server request and will be
     *  // automatically deleted afterwards
     *  $session->set_flashdata('myvar', 'myval');
     *  </code>
     *
     *  Flashdata session variables can be retrieved as any other session variable:
     *
     *  <code>
     *  if (isset($_SESSION['myvar'])) {
     *      // do something here but remember that the
     *      // flashdata session variable is available
     *      // for a single server request after it has
     *      // been set!
     *  }
     *  </code>
     *
     *  @param  string  $name   The name of the session variable.
     *
     *  @param  string  $value  The value of the session variable.
     *
     *  @return void
     */
    public function set_flashdata($name, $value)
    {

        // set session variable
        $_SESSION[$name] = $value;

        // initialize the counter for this flashdata
        $this->flashdata[$name] = 0;

    }

    /**
     *  Deletes all data related to the session
     *
     *  <code>
     *  // first, connect to a database containing the sessions table
     *
     *  //  include the class
     *  require 'path/to/session.php';
     *
     *  //  start the session
     *  //  where $dbAdapter is a connection link returned by mysqli_connect
     *  $session = new session($dbAdapter, 'sEcUr1tY_c0dE');
     *
     *  //  end current session
     *  $session->stop();
     *  </code>
     *
     *  @since 1.0.1
     *
     *  @return void
     */
    public function stop(){

        $this->regenerate_id();

        session_unset();
        session_destroy();
    }

    /**
     *  Custom close() function
     *
     *  @access private
     */
    function close(){

        // release the lock associated with the current session
        $this->link->select('RELEASE_LOCK("' . $this->session_lock . '")')->run();
        return true;
    }

    /**
     *  Custom destroy() function
     *
     *  @access private
     */
    public static function destroy($id){

        // deletes the current session id from the database
        $this->link->where('session_id', $id)->delete();
        
        // if anything happened
        // return true
        if ($this->link->count() !== -1) return true;

        // if something went wrong, return false
        return false;
    }

    /**
     *  Custom gc() function (garbage collector)
     *  @access private
     */
    function gc(){

        // deletes expired sessions from database
        if($this->link->where('session_expire','<',time())->delete()){
            App::critical_error($this->link->getLastError());
        }
    }

    /**
     *  Custom open() function
     *
     *  @access private
     */
    function open(){
        
        if($this->link->ping())
            return true;
        else
            return false;

    }

    /**
     *  Custom read() function
     *
     *  @access private
     */
    function read($id){

        // get the lock name, associated with the current session
        $this->session_lock = ':id';

        // try to obtain a lock with the given name and timeout
        $result = $this->_mysql_query('SELECT GET_LOCK("' . $this->session_lock . '", ' . $this->_mysql_real_escape_string($this->lock_timeout) . ')');

        // if there was an error
        // stop execution
        if (!is_object($result) || strtolower(get_class($result)) != 'mysqli_result' || @mysqli_num_rows($result) != 1 || !($row = mysqli_fetch_array($result)) || $row[0] != 1) die('session: Could not obtain session lock!');

        //  reads session data associated with a session id, but only if
        //  -   the session ID exists;
        //  -   the session has not expired;
        //  -   if lock_to_user_agent is TRUE and the HTTP_USER_AGENT is the same as the one who had previously been associated with this particular session;
        //  -   if lock_to_ip is TRUE and the host is the same as the one who had previously been associated with this particular session;
        $hash = '';

        // if we need to identify sessions by also checking the user agent
        if ($this->lock_to_user_agent && isset($_SERVER['HTTP_USER_AGENT']))

            $hash .= $_SERVER['HTTP_USER_AGENT'];

        // if we need to identify sessions by also checking the host
        if ($this->lock_to_ip && isset($_SERVER['REMOTE_ADDR']))

            $hash .= $_SERVER['REMOTE_ADDR'];

        // append this to the end
        $hash .= $this->security_code;

        $result = $this->_mysql_query('

            SELECT
                session_data
            FROM
                ' . $this->table_name . '
            WHERE
                session_id = "' . $this->_mysql_real_escape_string($session_id) . '" AND
                session_expire > "' . time() . '" AND
                hash = "' . $this->_mysql_real_escape_string(md5($hash)) . '"
            LIMIT 1

        ') or die($this->link->getLastError());

        // if anything was found
        if (is_object($result) && strtolower(get_class($result)) == 'mysqli_result' && @mysqli_num_rows($result) > 0) {

            // return found data
            $fields = @mysqli_fetch_assoc($result);

            // don't bother with the unserialization - PHP handles this automatically
            return $fields['session_data'];

        }

        $this->regenerate_id();

        // on error return an empty string - this HAS to be an empty string
        return '';

    }

    /**
     *  Custom write() function
     *
     *  @access private
     */
    function write($session_id, $session_data)
    {

        // insert OR update session's data - this is how it works:
        // first it tries to insert a new row in the database BUT if session_id is already in the database then just
        // update session_data and session_expire for that specific session_id
        // read more here http://dev.mysql.com/doc/refman/4.1/en/insert-on-duplicate.html
        $result = $this->_mysql_query('

            INSERT INTO
                ' . $this->table_name . ' (
                    session_id,
                    hash,
                    session_data,
                    session_expire
                )
            VALUES (
                "' . $this->_mysql_real_escape_string($session_id) . '",
                "' . $this->_mysql_real_escape_string(md5(($this->lock_to_user_agent && isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . ($this->lock_to_ip && isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . $this->security_code)) . '",
                "' . $this->_mysql_real_escape_string($session_data) . '",
                "' . $this->_mysql_real_escape_string(time() + $this->session_lifetime) . '"
            )
            ON DUPLICATE KEY UPDATE
                session_data = "' . $this->_mysql_real_escape_string($session_data) . '",
                session_expire = "' . $this->_mysql_real_escape_string(time() + $this->session_lifetime) . '"

        ') or die($this->link->getLastError());

        // if anything happened
        if ($result) {

            // note that after this type of queries, mysqli_affected_rows() returns
            // - 1 if the row was inserted
            // - 2 if the row was updated

            // if the row was updated
            // return TRUE
            if (@$this->_mysql_affected_rows() > 1) return true;

            // if the row was inserted
            // return an empty string
            else return '';

        }

        // if something went wrong, return false
        return false;

    }

    /**
     *  Manages flashdata behind the scenes
     *
     *  @access private
     */
    function _manage_flashdata(){

        // if there is flashdata to be handled
        if (!empty($this->flashdata)) {

            // iterate through all the entries
            foreach ($this->flashdata as $variable => $counter) {

                // increment counter representing server requests
                $this->flashdata[$variable]++;

                // if we're past the first server request
                if ($this->flashdata[$variable] > 1) {

                    // unset the session variable
                    unset($_SESSION[$variable]);

                    // stop tracking
                    unset($this->flashdata[$variable]);
                }
            }

            // if there is any flashdata left to be handled
            if (!empty($this->flashdata))

                // store data in a temporary session variable
                $_SESSION[$this->flashdata_varname] = serialize($this->flashdata);

        }

    }
}
