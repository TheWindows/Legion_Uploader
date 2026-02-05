<?php
date_default_timezone_set('Asia/Tehran');
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "legion"); 
define("UPLOAD_DIR", __DIR__ . "/uploads/");
define("MAX_UPLOAD_PER_USER", 1073741824);

ini_set('upload_max_filesize', '1024M');
ini_set('post_max_size', '1024M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '1024M');
?>