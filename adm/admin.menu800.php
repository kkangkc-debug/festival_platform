<?php
if (!defined('_GNUBOARD_')) exit;

$menu["menu800"] = array(
    array('800000', '현장 자산 관리', G5_ADMIN_URL . '/rain_parking_list.php', 'rain_field'),
    
    array('800100', '주차장 관리', G5_ADMIN_URL . '/rain_parking_list.php', 'parking_list'),
    
    array('800200', '체크인존 관리', G5_ADMIN_URL . '/rain_checkin_list.php', 'checkin_list'),
    array('800300', '체험부스 관리', G5_ADMIN_URL . '/rain_booth_list.php', 'booth_list'),
    array('800400', '쿠폰 관리', G5_ADMIN_URL . '/rain_coupon_list.php', 'coupon_list'),
    array('800500', '스탬프 관리', G5_ADMIN_URL . '/rain_stamp_list.php', 'stamp_list'),
    array('800600', '음식점 관리', G5_ADMIN_URL . '/rain_restaurant_list.php', 'restaurant_list'),
    array('800700', '채팅', G5_ADMIN_URL . '/chat_list.php', 'chat_list'),
    array('800800', '1:1 문의', G5_ADMIN_URL . '/inquiry_list.php', 'inquiry_list'),

    // [중요] 기존 번호 체계를 유지하며 '현장 스태프 배정' 메뉴를 추가합니다.
    array('800850', '현장 스태프 배정', G5_ADMIN_URL . '/rain_staff_list.php', 'staff_assign'), 

    array('800900', '통계 관리', G5_ADMIN_URL . '/stats_list.php', 'stats_list'),
    array('800910', '결제 관리', G5_ADMIN_URL . '/payment_list.php', 'payment_list'),
    array('800920', '시스템 관리(로그)', G5_ADMIN_URL . '/rain_admin_log_list.php', 'system_config') 
);
?>