<div class="page page-center" style='margin-top:50px'>
  <div class="container container-tight py-4" style='max-width:600px'>
    <div class="text-center mb-4">

        <?php include(__DIR__."/../common/logo.php"); ?>
        <h2 style="display:inline"><a href="/install/">php-ssl-scan installation</a></h2>
    </div>


    <div class="card card-md">
        <div class="card-body">
            <?php
            # get params
            $_params = $URL->get_params ();

            # default
            if(!isset($_params['route'])||$_params['route']=="dashboard")
            $_params['route'] = "select";

            # valid
            $valid_steps = ['select', 'manual', 'manual-verify', 'automatic', 'automatic-execute', 'finish'];

            # select
            if(in_array($_params['route'], $valid_steps)) {
                include ( dirname(__FILE__).'/'.$_params['route'].'.php');
            }
            else {
                print '<h3 class="text-red mb-3">'._("Installation error").'</h3>';
                print _('Invalid page requested').".";
                print '<div class="my-4">';
                print '<a href="/install/" class="btn btn-primary w-100">'._("Restart installation").'</a>';
                print '</div>';
                die();
            }
            ?>
        </div>
    </div>


  </div>
</div>



  <div class="container container-tight py-4" style="max-width:600px">
    <?php include('steps.php'); ?>
</div>