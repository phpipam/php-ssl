<?php

# DEPRECATED — kept so existing crontab entries keep working.
#
# The cron entry point moved to cron/cron.php. Update your crontab to:
#   */5 * * * * /usr/bin/php /path/to/php-ssl/cron/cron.php
#
# This shim will be removed in a future release.

require __DIR__ . "/cron/cron.php";
