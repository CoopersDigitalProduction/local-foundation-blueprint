<?php

namespace DeliciousBrains\WPMDBMST;

use DeliciousBrains\WPMDBMST\CliCommand\MultisiteToolsAddonCli;

class ServiceProvider extends \DeliciousBrains\WPMDB\Pro\ServiceProvider {
	/**
	 * @var MultisiteToolsAddon
	 */
	public $mst_addon;
	/**
	 * @var MultisiteToolsAddonCli
	 */
	public $mst_addon_cli;


	public function __construct() {
		parent::__construct();

		$this->mst_addon = new MultisiteToolsAddon(
			$this->addon,
			$this->properties,
			$this->multisite,
			$this->util,
			$this->migration_state_manager,
			$this->table,
			$this->table_helper,
			$this->form_data,
			$this->template,
			$this->profile_manager
		);

		$this->mst_addon_cli = new MultisiteToolsAddonCli(
			$this->addon,
			$this->properties,
			$this->multisite,
			$this->util,
			$this->migration_state_manager,
			$this->table,
			$this->table_helper,
			$this->form_data,
			$this->template,
			$this->profile_manager,
			$this->cli
		);
	}
}
