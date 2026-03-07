<?php
if (!defined('_GNUBOARD_')) exit;

// 최고관리자에게만 노출
if ($is_admin == 'super') {
    $menu["menu900"] = array(
        array('900000', '행사 마스터 관리', G5_ADMIN_URL . '/rain_festival_list.php', 'sass'),
        array('900100', '행사 개설/목록', G5_ADMIN_URL . '/rain_festival_list.php', 'sass_list'),
        array('900200', '행사별 관리자 지정', G5_ADMIN_URL . '/rain_festival_manager_list.php', 'sass_manager')
    );
}
?>