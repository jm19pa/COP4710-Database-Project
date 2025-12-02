<?php
// Redirect /customer or /customer/ to the static customer HTML page
header('Location: /customer/index.html', true, 302);
exit;
