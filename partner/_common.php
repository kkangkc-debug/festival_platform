<?php
define('G5_IS_PARTNER', true);
require_once '../common.php';

// 파트너 경로가 설정되지 않았다면 설정
if (!defined('G5_PARTNER_URL')) {
    define('G5_PARTNER_URL', G5_URL . '/partner');
}
if (!defined('G5_PARTNER_PATH')) {
    define('G5_PARTNER_PATH', G5_PATH . '/partner');
}
?>
