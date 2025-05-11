<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

check_permission('admin');
echo json_encode(['staff_id' => generate_staff_id()]);