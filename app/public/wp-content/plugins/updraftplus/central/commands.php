<?php

if (!defined('UPDRAFTCENTRAL_CLIENT_DIR')) die('No access.');

/**
 * - A container for all the RPC commands implemented. Commands map exactly onto method names (and hence this class should not implement anything else, beyond the constructor, and private methods)
 * - Return format is array('response' => (string - a code), 'data' => (mixed));
 *
 * RPC commands are not allowed to begin with an underscore. So, any private methods can be prefixed with an underscore.
 */
abstract class UpdraftCentral_Commands {

	protected $rc;

	protected $ud;

	public function __construct($rc) {
		$this->rc = $rc;
		global $updraftplus;
		$this->ud = $updraftplus;
	}

	final protected function _admin_include() {
		$files = func_get_args();
		foreach ($files as $file) {
			include_once(ABSPATH.'/wp-admin/includes/'.$file);
		}
	}
	
	final protected function _frontend_include() {
		$files = func_get_args();
		foreach ($files as $file) {
			include_once(ABSPATH.WPINC.'/'.$file);
		}
	}
	
	final protected function _response($data = null, $code = 'rpcok') {
		return array(
			'response' => $code,
			'data' => $data
		);
	}
	
	final protected function _generic_error_response($code = 'central_unspecified', $data = null) {
		return $this->_response(
			array(
				'code' => $code,
				'data' => $data
			),
			'rpcerror'
		);
	}

	/**
	 * Checks whether a backup and a security credentials is required for the given request
	 *
	 * @param array $dir The directory location to check
	 * @return array
	 */
	final protected function _get_backup_credentials_settings($dir) {
		// Do we need to ask the user for filesystem credentials? when installing and/or deleting items in the given directory
		$filesystem_method = get_filesystem_method(array(), $dir);
		ob_start();
		$filesystem_credentials_are_stored = request_filesystem_credentials(site_url());
		ob_end_clean();
		$request_filesystem_credentials = ('direct' != $filesystem_method && !$filesystem_credentials_are_stored);

		// Do we need to execute a backup process before installing/managing items
		$automatic_backups = (class_exists('UpdraftPlus_Options') && class_exists('UpdraftPlus_Addon_Autobackup') && UpdraftPlus_Options::get_updraft_option('updraft_autobackup_default', true)) ? true : false;
		
		return array(
			'request_filesystem_credentials' => $request_filesystem_credentials,
			'automatic_backups' => $automatic_backups
		);
	}
}
