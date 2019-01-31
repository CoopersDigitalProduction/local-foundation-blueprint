<?php

if (!defined('UPDRAFTPLUS_DIR')) die('No direct access allowed');

class UpdraftPlus_Database_Utility {

	private $whichdb;

	private $table_prefix_raw;

	private $dbhandle;

	/**
	 * Constructor
	 */
	/**
	 * Constructor
	 *
	 * @param String $whichdb          - which database is being backed up
	 * @param String $table_prefix_raw - the base table prefix
	 * @param Object $dbhandle         - WPDB object
	 */
	public function __construct($whichdb, $table_prefix_raw, $dbhandle) {
		$this->whichdb = $whichdb;
		$this->table_prefix_raw = $table_prefix_raw;
		$this->dbhandle = $dbhandle;
	}

	/**
	 * The purpose of this function is to make sure that the options table is put in the database first, then the users table, then the site + blogs tables (if present - multisite), then the usermeta table; and after that the core WP tables - so that when restoring we restore the core tables first
	 *
	 * @param Array $a_arr the first array
	 * @param Array $b_arr the second array
	 *
	 * @return Integer - the sort result, according to the rules of PHP custom sorting functions
	 */
	public function backup_db_sorttables($a_arr, $b_arr) {

		$a = $a_arr['name'];
		$a_table_type = $a_arr['type'];
		$b = $b_arr['name'];
		$b_table_type = $b_arr['type'];
	
		// Views must always go after tables (since they can depend upon them)
		if ('VIEW' == $a_table_type && 'VIEW' != $b_table_type) return 1;
		if ('VIEW' == $b_table_type && 'VIEW' != $a_table_type) return -1;
	
		if ('wp' != $this->whichdb) return strcmp($a, $b);

		global $updraftplus;
		if ($a == $b) return 0;
		$our_table_prefix = $this->table_prefix_raw;
		if ($a == $our_table_prefix.'options') return -1;
		if ($b == $our_table_prefix.'options') return 1;
		if ($a == $our_table_prefix.'site') return -1;
		if ($b == $our_table_prefix.'site') return 1;
		if ($a == $our_table_prefix.'blogs') return -1;
		if ($b == $our_table_prefix.'blogs') return 1;
		if ($a == $our_table_prefix.'users') return -1;
		if ($b == $our_table_prefix.'users') return 1;
		if ($a == $our_table_prefix.'usermeta') return -1;
		if ($b == $our_table_prefix.'usermeta') return 1;

		if (empty($our_table_prefix)) return strcmp($a, $b);

		try {
			$core_tables = array_merge($this->dbhandle->tables, $this->dbhandle->global_tables, $this->dbhandle->ms_global_tables);
		} catch (Exception $e) {
			$updraftplus->log($e->getMessage());
		}
		
		if (empty($core_tables)) $core_tables = array('terms', 'term_taxonomy', 'termmeta', 'term_relationships', 'commentmeta', 'comments', 'links', 'postmeta', 'posts', 'site', 'sitemeta', 'blogs', 'blogversions', 'blogmeta');

		$na = UpdraftPlus_Manipulation_Functions::str_replace_once($our_table_prefix, '', $a);
		$nb = UpdraftPlus_Manipulation_Functions::str_replace_once($our_table_prefix, '', $b);
		if (in_array($na, $core_tables) && !in_array($nb, $core_tables)) return -1;
		if (!in_array($na, $core_tables) && in_array($nb, $core_tables)) return 1;
		return strcmp($a, $b);
	}
}

class UpdraftPlus_WPDB_OtherDB_Utility extends wpdb {
	/**
	 * This adjusted bail() does two things: 1) Never dies and 2) logs in the UD log
	 *
	 * @param String $message    a string containing a message
	 * @param String $error_code a string containing an error code
	 * @return Boolean returns false
	 */
	public function bail($message, $error_code = '500') {
		global $updraftplus;
		if ('db_connect_fail' == $error_code) $message = 'Connection failed: check your access details, that the database server is up, and that the network connection is not firewalled.';
		$updraftplus->log("WPDB_OtherDB error: $message ($error_code)");
		// Now do the things that would have been done anyway
		$this->error = class_exists('WP_Error') ? new WP_Error($error_code, $message) : $message;
		return false;
	}
}
