<?php

#
# Rollback change
#


# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (true, true, true);
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);


// execute change
try {
	// fetch log
	$log = $Log->get_log_by_id ($_GET['log_id'], $user);
	// make sure old object is present
	$old_object = json_decode($log->json_object_old, true);

	// validate log_id
	if($Common->validate_int($_GET['log_id'])===false)
	throw new Exception ("Invalid log id");
	// validate log and tenant
	if ($log==NULL)
	throw new Exception ("Invalid log id");
	// invalid content
	if(json_last_error()!==0)
	throw new Exception ("Cannot obtain old object");
	// make sure action can be rolled back
	if($log->is_revertable!=="1")
	throw new Exception ("Action is not revertable");


	// edit is ok
	// how do we deal with deletes - updateObject is not ok ?
	// maybe count number of hits also so we know if anything was affected ?


	// revert multiple
	if(is_array($old_object)) {
		foreach ($old_object as $table_name => $objects) {
			foreach ($objects as $o) {
				if($log->action=="delete" || $log->action=="truncate") {
					$Database->insertObject ($table_name, $o);
				}
				else {
					$Database->updateObject ($table_name, $o);
				}
			}
		}
	}
	else {
		throw new Exception ("Invalid object structure");
	}

	// ok
	$content[] = $Result->show("success", _("Rollback complete").".", false, false, true, false);
	// Log
	if($log->action=="delete")
	$Log->write ($log->object, $log->object_id, $log->object_t_id, $user->id, "rollback", true, "Change [".$log->id."] rolled back", null, json_encode($old_object));
	else
	$Log->write ($log->object, $log->object_id, $log->object_t_id, $user->id, "rollback", true, "Change [".$log->id."] rolled back", json_encode($new_object), json_encode($old_object));

} catch (Exception $e) {
	$content[] = $Result->show("danger", _($e->getMessage()).".", false, false, true, false);
}


// modal
$Modal->modal_print ("Rollback", implode("\n", $content), "", "", true, $header_class);