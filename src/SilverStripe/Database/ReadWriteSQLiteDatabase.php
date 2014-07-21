<?php

/**
 * A read/write splitting mysql database
 * 
 * Implements custom versions of
 * 
 * <ul>
 * <li>query</li>
 * <li>manipulate</li>
 * <li>getGeneratedID</li>
 * <li>affectedRows</li>
 * </ul>
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class ReadWriteSQLiteDatabase extends SQLite3Database {
	/**
	 *
	 * @var SS_Database
	 */
	public $writeDatabase;
	
	public $writeQueries = array('insert','update','delete','replace', 'drop', 'create', 'truncate');
	
	private $writePerformed = false;
	
	/**
	 * If a write query is detected, hand it off to the configured write database
	 * 
	 * @param string $sql
	 * @param int $errorLevel
	 * @return \MySQLQuery
	 */
	public function query($sql, $errorLevel = E_USER_ERROR) {
		if (in_array(strtolower(substr($sql,0,strpos($sql,' '))), $this->writeQueries) || $this->writePerformed) {
			$alternateReturn = $this->writeDb()->query($sql, $errorLevel);
			$this->writePerformed = true;
			return $alternateReturn;
		}
		
		if (stripos($sql, 'content')) {
			$o = 1;
		}

		return parent::query($sql, $errorLevel);
	}
	
	/**
	 * Manipulate is _always_ a write query
	 * 
	 * @param array $manipulation
	 */
	public function manipulate($manipulation) {
		$this->writePerformed = true;
		return $this->writeDb()->manipulate($manipulation);
	}
	
	/**
	 * getGeneratedID is only relevant in context of a write statement, which must have
	 * been pushed to the write dB
	 * 
	 * @param string $table
	 * @return int
	 */
	public function getGeneratedID($table) {
		return $this->writeDb()->getGeneratedID($table);
	}
	
	/**
	 * affectedRows is only relevant in context of a write statement, which must have
	 * been pushed to the write dB
	 * 
	 * @return int
	 */
	public function affectedRows() {
		return $this->writeDb()->affectedRows();
	}
	
	/**
	 * Retrieve the write DB
	 * 
	 * @return SS_Database
	 */
	protected function writeDb() {
		if (!$this->writeDatabase) {
			$this->writeDatabase = Injector::inst()->get('SplitterWriteDatabase');
		}
		
		return $this->writeDatabase;
	}
}
