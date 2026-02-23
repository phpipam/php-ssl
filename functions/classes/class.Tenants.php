<?php

/**
 * Class to work with tenants
 */
class Tenants extends Common
{

	/**
	 * Database holder
	 * @var bool
	 */
	private $Database = false;



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
	}

	/**
	 * Gets tenants from database and reorders
	 * @method get_all
	 * @return array
	 */
	public function get_all()
	{
		try {
			$tenants = $this->Database->getObjectsQuery("select * from tenants order by `order` asc,name asc");
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// reindex
		if (sizeof($tenants) > 0) {
			$tenants_new = [];
			foreach ($tenants as $t) {
				$tenants_new[$t->id] = $t;
			}
			$tenants = $tenants_new;
		}
		// return
		return $tenants;
	}

	/**
	 * Returns tenant from URI href
	 * @method get_tenant_by_href
	 * @param  string $href
	 * @return object|bool
	 */
	public function get_tenant_by_href($href = "")
	{
		try {
			if (is_numeric($href)) {
				return $this->Database->getObjectQuery("select * from tenants where id = ? ", [$href]);
			}
			else {
				return $this->Database->getObjectQuery("select * from tenants where href = ? ", [$href]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
			return false;
		}
	}
}