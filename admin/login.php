<?php
// Keep old bookmarks working while exposing only the single shared login page.
header('Location: ../index.html', true, 302);
exit;
