<?php

namespace DeliciousBrains\WPMDBTP;

class ServiceProvider extends \DeliciousBrains\WPMDB\Pro\ServiceProvider {

	public $tp_addon_finalize;
	public $tp_addon;
	public $tp_addon_transfer_check;
	public $tp_addon_local;
	public $tp_addon_remote;

	public function __construct() {
		parent::__construct();

		$this->tp_addon_finalize = new ThemePluginFilesFinalize(
			$this->form_data,
			$this->filesystem,
			$this->transfers_util,
			$this->error_log,
			$this->http,
			$this->state_data_container,
			$this->queue_manager,
			$this->migration_state_manager
		);

		$this->tp_addon = new ThemePluginFilesAddon(
			$this->addon,
			$this->properties,
			$this->template,
			$this->filesystem,
			$this->profile_manager,
			$this->util,
			$this->transfers_util,
			$this->transfers_receiver,
			$this->tp_addon_finalize
		);

		$this->tp_addon_transfer_check = new TransferCheck(
			$this->form_data,
			$this->http,
			$this->error_log
		);

		$this->tp_addon_local = new ThemePluginFilesLocal(
			$this->transfers_util,
			$this->util,
			$this->transfers_file_processor,
			$this->queue_manager,
			$this->transfers_manager,
			$this->transfers_receiver,
			$this->migration_state_manager,
			$this->http,
			$this->filesystem,
			$this->tp_addon_transfer_check
		);

		$this->tp_addon_remote = new ThemePluginFilesRemote(
			$this->transfers_util,
			$this->transfers_file_processor,
			$this->queue_manager,
			$this->transfers_manager,
			$this->transfers_receiver,
			$this->http,
			$this->http_helper,
			$this->migration_state_manager,
			$this->settings,
			$this->properties,
			$this->transfers_sender,
			$this->filesystem,
			$this->scrambler
		);

	}
}
