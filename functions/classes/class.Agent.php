<?php

/**
 * Connect to remote agent and process SSL scan results
 */
class Agent {

	/**
	 * Agent details
	 * @var bool
	 */
	private $agent = false;

	/**
	 * Hostanem to send to agent
	 * @var string
	 */
	private $hostname = "";

	/**
	 * Array od ports to send to agent
	 * @var array
	 */
	private $ports = [];

	/**
	 * Full result if needed for debugging
	 * @var string
	 */
	public $result_full = "";

	/**
	 * Processes result
	 * @var bool
	 */
	private $result = false;

	/**
	 * Number of seconds before timing out. shoul be more than set on API agent * ports
	 * @var int
	 */
	private $timeout = 10;

	/**
	 * Errors when scanning
	 * @var array
	 */
	public $errors = [];



	/**
	 * Constructor
	 * @method __construct
	 * @param  bool|object $agent
	 * @param  string $hostname
	 * @param  array $ports
	 */
	public function __construct ($agent = false) {
		// save agent
		$this->agent = $agent;
	}

	/**
	 * Adds hostname and port to be checked
	 * @method add_host_port
	 * @param  string $hostname
	 * @param  array $ports
	 */
	public function add_host_port ($hostname = "", $ports = []) {
		// save hostname to scan
		$this->hostname = $hostname;
		// save ports
		$this->ports = $ports;
	}

	/**
	 * Executes scan
	 * @method scan
	 * @return [type]
	 */
	public function scan () {
        // Get cURL resource
        $API_conn = curl_init();
        // set url
        $url = str_replace("//", "/", $this->agent->url."/".$this->hostname."/".implode(",", $this->ports)."/");
        // set default curl options and params
        curl_setopt_array($API_conn, array(
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_URL            => $url,
                CURLOPT_HEADER         => false,
                CURLOPT_VERBOSE        => false,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_HTTPHEADER     => array("Content-Type: application/json", "Accept: application/json"),
                CURLOPT_USERAGENT      => 'php-ssl API',
                // ssl ignore invalid certificates
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false
            )
        );

        // send request and save response
        $resp = curl_exec($API_conn);
        // save raw result
        $this->result_full = json_decode($resp);

        // curl error check
        if (curl_errno($API_conn)) {
            // error
            $this->errors[] = curl_errno($API_conn).": ".curl_error($API_conn);
            $this->errors[] = "Received http code ".$this->result['result_code'];
            // false
            return false;
        }
        else {
            // save result and result code
            $result_info = curl_getinfo($API_conn);
            $this->result['result_code'] = $result_info['http_code'];

            // error ?
            if($this->result['result_code']!="200")
            $this->errors[] = "Received http code ".$this->result['result_code'];

            // from json to object, result only
            $this->result = $this->result_full->result;
        }
	}

	/**
	 * Transform requested URL to host and port
	 * @method url_to_hostname
	 * @param  string $url
	 * @return array
	 */
	public function url_to_hostname_port ($url = "") {
		// get hostname and port
		$url_arr = parse_url($url);
		$url_arr['port'] = $url_arr['scheme']=="https"&&!isset($url_arr['port']) ? 443 : $url_arr['port'];		// add default https port
		$url_arr['port'] = $url_arr['scheme']=="http"&&!isset($url_arr['port']) ?  80  : $url_arr['port'];		// add default https port
		// return
		return ["host"=>$url_arr['host'], "port"=>$url_arr['port']];
	}

	/**
	 * Return result
	 * @method get_result
	 * @return [type]
	 */
	public function get_result () {
		return $this->result;
	}

	/**
	 * Test if agent is accessible
	 * @method test_agents
	 * @param  database $Database
	 * @param  string $hostname
	 * @param  int $port
	 * @param  datetime $datetime
	 * @return void
	 */
	public function test_agents ($Database, $hostname, $port, $datetime, $id = 0) {
		try {
			// all agents or single ?
			if($id==0)	{
				$all_agents = $Database->getObjectsQuery("select * from agents where atype = 'remote' ");
			}
			else {
				$all_agents = $Database->getObjectsQuery("select * from agents where atype = 'remote' and id = ?", [$id]);
			}
			// do we have some ?
			if (sizeof($all_agents)>0) {
				foreach ($all_agents as $a) {
			        // Get cURL resource
			        $API_conn = curl_init();
			        // set url
			        $url = str_replace("//", "/", $a->url."/".$hostname."/".$port."/");
			        // set default curl options and params
			        curl_setopt_array($API_conn, array(
			                CURLOPT_CUSTOMREQUEST  => "GET",
			                CURLOPT_RETURNTRANSFER => true,
			                CURLOPT_FOLLOWLOCATION => true,
			                CURLOPT_URL            => $url,
			                CURLOPT_HEADER         => false,
			                CURLOPT_VERBOSE        => false,
			                CURLOPT_TIMEOUT        => $this->timeout,
			                CURLOPT_HTTPHEADER     => array("Content-Type: application/json", "Accept: application/json"),
			                CURLOPT_USERAGENT      => 'php-ssl API',
			                // ssl ignore invalid certificates
			                CURLOPT_SSL_VERIFYHOST => false,
			                CURLOPT_SSL_VERIFYPEER => false
			            )
			        );

			        // send request and save response
			        $resp = curl_exec($API_conn);

			        // db update array
		        	$update = [
							"id"         => $a->id,
							"last_check" => $datetime
		        	];

			        // curl error check
			        if (curl_errno($API_conn)) {
			        	// set error
			        	$update['last_error'] = curl_errno($API_conn).": ".curl_error($API_conn);
			        }
			        // ok, update
			        else {
			        	// check result code
			        	$resp_arr = json_decode($resp, true);

			        	// 200 - ok
			        	if ($resp_arr['code']=="200") {
							$update['last_error']   = NULL;
							$update['last_success'] = $datetime;
						}
						// error
						else {
							$update['last_error'] = "Received http code ".$resp_arr['code']." : ".$resp_arr['result']['error'];
						}
			        }

			        // update db
			        $Database->updateObject("agents", $update);

			        // unset
			        curl_close($API_conn);
				}
			}
		}
		catch (Exception $e) {
			print $e->getMessage()."\n";
		}
	}

	/**
	 * Get agent connection errrors
	 * @method get_errors
	 * @param  bool $Database
	 * @param  int $minutes
	 * @param  bool $is_admin
	 * @param  int $tenant_id
	 * @return [type]
	 */
	public function get_agent_connection_errors ($Database = false, $minutes = 15, $is_admin= false, $tenant_id = 0) {
		// admins - print all
		if($is_admin) {
			$q = "select * from agents where atype = 'remote' and last_success < ?";
			$v = [date("Y-m-d H:i:s", strtotime("-$minutes minutes"))];
		}
		else {
			$q = "select * from agents where atype = 'remote' and last_success < ? and t_id = ?";
			$v = [date("Y-m-d H:i:s", strtotime("-$minutes minutes")), $tenant_id];
		}
		// fetch all
		try {
			$errors_db = $Database->getObjectsQuery($q, $v);
			$errors = [];
			// reindex
			if(sizeof($errors_db)>0) {
				foreach($errors_db as $e) {
					$errors[$e->id] = $e;
				}
			}
		}
		catch (Exception $e) {
			print $e->getMessage();
		}

		// return
		return $errors;
	}

}