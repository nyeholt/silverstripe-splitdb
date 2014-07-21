<?php

namespace SilverStripe\Aspects\Database;

/**
 * An aspect that will direct some queries to a 'write only' database
 * if they match some specific criteria
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class ReadWriteSplitterAspect implements \BeforeCallAspect {
	/**
	 *
	 * @var \SS_Database
	 */
	public $writeDb;
	
	public $writeQueries = array('insert','update','delete','replace', 'drop', 'create', 'truncate');
	
	/**
	 * If we've done a write query, ensure all subsequent queries are executed against the same connection to 
	 * try and force some level of consistency. 
	 *
	 * @var boolean
	 */
	private $writePerformed = false;
 
	public function beforeCall($proxied, $method, $args, &$alternateReturn) {
		// we only query on the write DB if it's a write query, OR we've performed a write
		// by some other means
		if ($method == 'query') {
			if (isset($args[0])) {
				$sql = $args[0];
				$code = isset($args[1]) ? $args[1] : E_USER_ERROR;
				if (in_array(strtolower(substr($sql,0,strpos($sql,' '))), $this->writeQueries) || $this->writePerformed) {
					$alternateReturn = $this->writeDb->query($sql, $code);
					$this->writePerformed = true;
					return false;
				}
			}
		} else {
			$i = call_user_func_array(array($this->writeDb, $method), $args);
			// capture a call to manipulate, which basically performs a bunch of write queries
			// at once
			if ($method == 'manipulate') {
				$this->writePerformed = true;
			}
			$alternateReturn = $i;
			return false;
		}
	}

	public function setWritePerformed($v) {
		$this->writePerformed = $v;
	}
}