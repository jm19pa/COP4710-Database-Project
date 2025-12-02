<?php
// Redirect /restaurant or /restaurant/ to the static restaurant HTML page
header('Location: /restaurant/index.html', true, 302);
exit;
