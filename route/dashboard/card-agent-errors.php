<?php

// init agent
$Agent = new Agent ();

// get conf
$config = $Config->get_config($user->t_id);

// get errors
$agent_errors = $Agent->get_agent_connection_errors($Database, $config['agentTimeout'], $user->admin, $user->t_id);

// print if eneded
if (sizeof($agent_errors)>0) {
?>


<div class='col-xs-12'>
<div class='bubble'>
	<div class='bubble-header text-danger'><i class='links fa fa-warning'></i> <?php print _("Agent errors"); ?></div>
	<div class='bubble-content'>
		<div style='padding:5px;'>

			<?php
			foreach ($agent_errors as $e) {
				// last error
				$errtext =  strlen($e->last_error)>1 ? "Error :: <b>".$e->last_error."</b>" : "";
				// print
				print '<div class="alert alert-danger" style="margin-bottom:5px">';
				print _("Agent"). " <b>".$e->name."</b> "._("didnt respond for")." ".$config['agentTimeout']." "._("minutes").". $errtext<hr style='margin:5px 0px'><span class='text-muted'>"._("Last successful response received at")." ".$e->last_success."</span>";
				print '</div>';
			}
			?>

		</div>
	</div>
</div>
</div>
<?php } ?>