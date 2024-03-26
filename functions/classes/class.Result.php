<?php

/**
 *
 */

class Result {

	/**
	 * Array of errors
	 * @var array
	 */
	public $errors = [];

	/**
	 * Return array of errors
	 * @method get_errors
	 * @return array
	 */
	public function get_errors () {
		return $this->errors;
	}

	/**
	 * Return last error recorder
	 * @method get_last_error
	 * @return string
	 */
	public function get_last_error () {
		return end($this->errors);
	}

	/**
	 * Save error
	 * @method save_error
	 * @param  string $error
	 * @return void
	 */
	public function save_error ($error = "") {
		$this->errors[] = $error;
	}

	/**
	 * Stop executing
	 * @method die
	 * @return void
	 */
	public function result_die ($modal = false, $modal2 = false) {
		if(php_sapi_name()=="cli") {
			die($this->get_last_error ()."\n");
		}
		else {
			print "<div class='alert alert-danger'>".$this->get_last_error ()."</div>";
			if($modal && !$modal2) {
				print "</div>";
				print "<div class='modal-footer' style='margin-top:20px;'>";
				print "<button type='button' class='btn btn-sm btn-default btn-secondary' data-bs-dismiss='modal'>"._("Close window")."</button>";
				print "</div>";
			}
			die();
		}
	}

	/**
	 * Print error
	 * @method result_warning
	 * @return [type]
	 */
	public function result_warning () {
		if(php_sapi_name()=="cli") {
			print $this->get_last_error ()."\n";
		}
		else {
			print("<div class='alert alert-warning'>".$this->get_last_error ()."</div>");
		}
	}

	/**
	 * Show result
	 *
	 * @access public
	 * @param string $class (default: "muted")				result class - danger, success, warning, info
	 * @param string $text (default: "No value provided")	text to display
	 * @param bool $die (default: false)					controls stop of php execution
	 * @param bool $popup (default: false)					print result as popup
	 * @param bool $inline (default: false)					return, not print
	 * @param bool $popup2 (default: false)					close for JS for popup2
	 * @return void
	 */
	public function show($class="muted", $text="No value provided", $die="", $popup=false, $inline = false, $popup2 = false) {

		# danger
		if($class=="danger") {}

		# set die
		if(is_bool($die)) {
			$this->die = $die;
		}
		# default for danger class
		else {
			if ($class=="danger") { $this->die = true; }
		}

		# API - throw exception
		if($this->exit_method == "exception")  {
			# ok, just return success
			if ($class=="success") 		{ return true; }
			else						{ return $this->throw_exception ($text); }
		}
		else {
			# cli or GUI
			if (php_sapi_name()=="cli") { print $this->show_cli_message ($text); }
			else {
				# return or print
				if ($inline) 			{ return $this->show_message ($class, $text, $popup, $popup2); }
				else					{ print  $this->show_message ($class, $text, $popup, $popup2); }
			}

			# die
			if($this->die===true)	{die(); }
		}
	}


	/**
	 * Alias for show method for backwards compatibility
	 *
	 * @access public
	 * @param string $text (default: "No value provided")
	 * @param bool $die (default: false)
	 * @return void
	 */
	public function show_cli ($text="No value provided", $die=false) {
		$this->show(false, $text, $die, false, false, false);
	}

	/**
	 * Shows result for cli functions
	 *
	 * @access public
	 * @param string $text (default: "No value provided")
	 * @return void
	 */
	public function show_cli_message ($text="No value provided") {
		// array - join
		if (is_array($text) && sizeof($text)>0) {
			// 1 element
			if(sizeof( $text )==1) {
				$text = $text[0];
			}
			// multiple - format
			else {
    			$out = array();
				foreach( $text as $l ) { $out[] = "\t* $l"; }
				// join
				$text = implode("\n", $out);
			}
		}
		# print
		return $text."\n";
	}

	/**
	 * Show GUI result
	 *
	 * @access public
	 * @param mixed $class
	 * @param mixed $text
	 * @param mixed $popup
	 * @param mixed $popup2
	 * @return void
	 */
	public function show_message ($class, $text, $popup, $popup2) {
    	// to array if object
    	if (is_object($text))   { $text = (array) $text; }
		// format if array
		if(is_array($text)) {
			// single value
			if(sizeof( $text )==1) {
				$out = $text;
			}
			// multiple values
			else {
				$out[] = "<ul>";
				foreach( $text as $l ) { $out[] = "<li>$l</li>"; }
				$out[] = "</ul>";
			}
			// join
			$text = implode("\n", $out);
		}

		# print popup or normal
		if($popup===false) {
			return "<div class='alert alert-".$class."'>".$text."</div>";
		}
		else {
			// set close class for JS
			$pclass = $popup2===false ? "hidePopups" : "hidePopup2 reload-window";
			// change danger to error for popup
			$htext = $class==="danger" ? "error" : $class;

            $out = array();
			$out[] = '<div class="modal-header"><h2 class="modal-title">'._(ucwords($htext)).'</h2></div>';
			$out[] = '<div class="modal-body">';
			$out[] = '<div class="alert alert-'.$class.'">'.$text.'</div>';
			$out[] = '</div>';
			$out[] = '<div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-secondary '.$pclass.'" data-bs-dismiss="modal">'._("Close").'</button></div>';

			// return
			return implode("\n", $out);
		}
	}
}
