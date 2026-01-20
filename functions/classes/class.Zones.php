<?php

/**
 * Class to work with zones
 */
class Zones extends Common {

	/**
	 * Database holder
	 * @var bool
	 */
	private $Database = false;

	/**
	 * User object
	 * @var bool
	 */
	private $user = false;



	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO $Database
	 * @param  string $user
	 */
	public function __construct (Database_PDO $Database, $user = "") {
		// Save database object
		$this->Database = $Database;
		// user
		if(is_object($user)) {
			$this->user = $user;
		}
	}

	/**
	 * Get available agents for tenant
	 * @method get_agents
	 * @param  int $tenant_id
	 * @return [type]
	 */
	public function get_tenant_agents ($tenant_id = 0) {
		try {
			$agents = $this->Database->getObjectsQuery("select * from agents where id = 1 or t_id = ?", $tenant_id);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		return $agents;
	}

	/**
	 * Returns all zones for admin and tenant zones for non-admin
	 * @method get_all
	 * @return array
	 */
	public function get_all () {
		// fetch
		try {
			if($this->user->admin=="1") {
				$zones = $this->Database->getObjectsQuery("select *,t.name as tenant_name,z.name as name,z.id as id, z.description as description,z.t_id as t_id, a.name as agname from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id order by z.name asc");
			}
			else {
				$zones = $this->Database->getObjectsQuery("select *,t.name as tenant_name,z.name as name,z.id as id, z.description as description,z.t_id as t_id, a.name as agname from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and z.t_id = ? order by z.name asc", $this->user->t_id);
			}
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		// reindex
		if(sizeof($zones)>0) {
			$zones_new = [];
			foreach ($zones as $z) {
				$zones_new[$z->id] = $z;
			}
			$zones = $zones_new;
		}
		// return
		return $zones;
	}

	/**
	 * Returns all hosts inside zone
	 * @method get_zone_hosts
	 * @param  int $zone_id
	 * @return arraya
	 */
	public function get_zone_hosts ($zone_id) {
		// fetch
		try {
			$hosts = $this->Database->getObjectsQuery("select *,h.id as id,z.name as zone_name, z.t_id as t_id from zones as z, hosts as h, tenants as t where h.z_id = z.id and z.t_id = t.id and z.id = ? order by h.hostname asc", $zone_id);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		// return
		return $hosts;
	}

	/**
	 * Search hosts
	 * @method search_zone_hosts
	 * @param  string $search_string
	 * @return array
	 */
	public function search_zone_hosts ($search_string = "") {
		// fetch
		try {
			if($this->user->admin=="1") {
				$hosts = $this->Database->getObjectsQuery("select *,h.id as id,z.name as zone_name from zones as z, hosts as h, tenants as t
				                                          	where h.z_id = z.id and z.t_id = t.id
				                                          	and (h.hostname like '%".$search_string."%' or h.ip like '%".$search_string."%')
				                                          	order by h.hostname asc");
			}
			else {
				$hosts = $this->Database->getObjectsQuery("select *,h.id as id,z.name as zone_name from zones as z, hosts as h, tenants as t
				                                          	where h.z_id = z.id and z.t_id = t.id and z.href = ?
				                                          	and (h.hostname like '%".$search_string."%' or h.ip like '%".$search_string."%')
				                                          	order by h.hostname asc", [$this->user->t_id]);
			}
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		// return
		return $hosts;
	}

	/**
	 * Returns zone details
	 * @method get_zone
	 * @param  string $href
	 * @param  string $zone_name
	 * @return array
	 */
	public function get_zone ($href = "", $zone_name = "") {
		if(is_numeric($zone_name)) {
			return $this->Database->getObjectQuery("select *,t.name as tenant_name,z.name as name,z.description as z_description,a.name as agname, z.id as id, z.t_id as t_id from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and t.href = ? and z.id = ?  order by z.name asc", [$href, $zone_name]);
		}
		else {
			return $this->Database->getObjectQuery("select *,t.name as tenant_name,z.name as name,z.description as z_description,a.name as agname, z.id as id, z.t_id as t_id from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and t.href = ? and z.name = ? order by z.name asc", [$href, $zone_name]);
		}
	}

	/**
	 * Makes sure added hostname is inside domain !
	 * @method is_host_inside_domain
	 * @param  string $hostname
	 * @param  string $domainname
	 * @return bool
	 */
	public function is_host_inside_domain ($hostname = "", $domainname = "") {
		$dn_arr = array_reverse(explode(".", $domainname));
		$hn_arr = array_reverse(explode(".", $hostname));
		// check
		foreach ($dn_arr as $index=>$var) {
			if ($hn_arr[$index]!==$var) {
				return false;
			}
		}
		// all good
		return true;
	}

	/**
	 * Count how many certificates are present is some zone
	 * @method count_zone_certs
	 * @param  int $zone_id
	 * @return int
	 */
	public function count_zone_certs ($zone_id = 0) {
		// fetch
		try {
			$cnt = $this->Database->getObjectQuery("select count(distinct(c_id)) as cnt from hosts where z_id = ?", [$zone_id]);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		// return
		return $cnt->cnt;
	}

	/**
	 * Checks when last chekc for host occured in zone
	 * @method get_last_check
	 * @param  int $zone_id
	 * @return datetitme
	 */
	public function get_last_check ($zone_id = 0) {
		// fetch
		try {
			$last = $this->Database->getObjectQuery("select MAX(last_check) as last from hosts where z_id = ?", [$zone_id]);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		// return
		return $last->last;
	}

	/**
	 * Returns host details by id
	 * @method get_host
	 * @param  string $host_id
	 * @return object
	 */
	public function get_host ($host_id = "") {
		// fetch
		try {
			$hosts = $this->Database->getObject("hosts", $host_id);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		// return
		return $hosts;
	}
}