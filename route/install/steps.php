<?php

// set step
switch ($_params['route']) {
    case "manual"    : $step = 3; break;
    case "automatic" : $step = 1; break;

    default: $step = 0;
}
?>


<div>
<ul class="steps steps-green steps-counter" style='padding:20px 0px;margin:0px;border:none'>
    <li class="step-item <?php if($step==0) print "active"; ?>">Select type</li>
    <li class="step-item <?php if($step==1) print "active";  ?>">Parameters</li>
    <li class="step-item <?php if($step==2) print "active";  ?>">Install</li>
    <li class="step-item <?php if($step==3) print "active";  ?>">Result</li>
</ul>
</div>