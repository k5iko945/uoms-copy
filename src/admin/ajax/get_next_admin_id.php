<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

check_permission('admin');
echo json_encode(['admin_id' => generate_admin_id()]);