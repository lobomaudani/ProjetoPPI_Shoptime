<?php
// Shared head partial. Set $pageTitle before include when you need a custom title.
if (!isset($pageTitle))
    $pageTitle = 'ShowTime';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles/styles.css" rel="stylesheet">
<link rel="icon" href="images/favicon.ico">
<title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>