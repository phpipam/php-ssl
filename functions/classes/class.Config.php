<?php

/**
 *
 * Gets config from database
 *
 */
class Config extends Common
{

	/**
	 * Database holder
	 * @var bool
	 */
	private $Database = false;

	/**
	 * Full config
	 * @var array
	 */
	private $full_config = [];

	/**
	 * Config - reformatted
	 * @var array
	 */
	private $config = [];



	/**
	 * Consrtuctor
	 *
	 * @method __construct
	 * @param  Database_PDO $Database
	 */
	public function __construct(Database_PDO $Database)
	{
		// Save database object
		$this->Database = $Database;
		// get all configs
		$this->get_db_config();
		// compose per-tenant
		$this->compose_config();
	}

	/**
	 * Gets full config from database
	 *
	 * @method get_db_config
	 * @return void
	 */
	public function get_db_config()
	{
		// fetch
		try {
			$config = $this->Database->getObjectsQuery("select * from config order by t_id asc");
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// compose
		$this->full_config = $config;
	}

	/**
	 * All tenants
	 *
	 * @method get_tenants
	 * @return array
	 */
	private function get_tenants()
	{
		// fetch
		try {
			$tenants = $this->Database->getObjectsQuery("select * from tenants");
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// reindex
		$tenants_out = [];
		if (sizeof($tenants) > 0) {
			foreach ($tenants as $t) {
				$tenants_out[$t->id] = $t;
			}
		}
		// compose
		return $tenants_out;
	}

	/**
	 * Reformats config per tenant
	 * @method compose_config
	 * @return array
	 */
	public function compose_config()
	{
		// fetch tenants
		$tenants = $this->get_tenants();
		// create defaults
		foreach ($this->full_config as $c) {
			// per-tenant settings first
			if (!is_numeric($c->t_id)) {
				foreach ($tenants as $t) {
					if (!@array_key_exists($c->name, $this->config[$t->id])) {
						$this->config[$t->id][$c->name] = $c->value;
					}
				}
			}
			// per-tentant
			else {
				$this->config[$c->t_id][$c->name] = $c->value;
			}
		}
	}

	/**
	 * Returns config for tenant
	 * @method get_config
	 * @param  int $tenant_id
	 * @return array
	 */
	public function get_config($tenant_id = NULL)
	{
		return isset($this->config[$tenant_id]) ? $this->config[$tenant_id] : [];
	}

}