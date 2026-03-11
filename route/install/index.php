<div class="page page-center" style='margin-top:50px'>
  <div class="container container-tight py-4" style='max-width:600px'>
    <div class="text-center mb-4">

        <?php if($_SESSION['theme']!="dark") { ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
          <path d="M30.4 7.6C29.7 4.7 27.3 2.3 24.4 1.6 18.8 0.7 13.2 0.7 7.6 1.6 4.7 2.3 2.3 4.7 1.6 7.6 0.7 13.2 0.7 18.8 1.6 24.4 2.3 27.3 4.7 29.7 7.6 30.4c5.6 0.9 11.2 0.9 16.8 0C27.3 29.7 29.7 27.3 30.4 24.4c0.9-5.6 0.9-11.2 0-16.8z" fill="#066fd1"/>
          <g transform="translate(4.7, 4.7) scale(0.94)">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M4 8v-2a2 2 0 0 1 2 -2h2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M4 16v2a2 2 0 0 0 2 2h2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M16 4h2a2 2 0 0 1 2 2v2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M16 20h2a2 2 0 0 0 2 -2v-2" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M8 10a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-4a2 2 0 0 1 -2 -2l0 -4" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          </g>
        </svg>
        <?php } else { ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
          <path d="M30.4 7.6C29.7 4.7 27.3 2.3 24.4 1.6 18.8 0.7 13.2 0.7 7.6 1.6 4.7 2.3 2.3 4.7 1.6 7.6 0.7 13.2 0.7 18.8 1.6 24.4 2.3 27.3 4.7 29.7 7.6 30.4c5.6 0.9 11.2 0.9 16.8 0C27.3 29.7 29.7 27.3 30.4 24.4c0.9-5.6 0.9-11.2 0-16.8z" fill="white"/>
          <g transform="translate(4.7, 4.7) scale(0.94)">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M4 8v-2a2 2 0 0 1 2 -2h2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M4 16v2a2 2 0 0 0 2 2h2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M16 4h2a2 2 0 0 1 2 2v2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M16 20h2a2 2 0 0 0 2 -2v-2" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M8 10a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-4a2 2 0 0 1 -2 -2l0 -4" stroke="rgb(17, 24, 39)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          </g>
        </svg>
        <?php } ?>
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
            $valid_steps = ['select', 'manual', 'automatic', 'automatic-execute', 'finish'];

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