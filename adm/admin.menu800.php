<?php
if (!defined('_GNUBOARD_')) exit;

// 800000: 1Depth 메뉴명 (전체를 아우르는 명칭으로 유지)
// 800100~ : 2Depth 세부 메뉴명
$menu["menu800"] = array(
    array('800000', '현장 자산 관리', G5_ADMIN_URL . '/rain_parking_list.php', 'rain_field'),
    
    // 주차장 관련 (800100 계열)
    array('800100', '주차장 관리', G5_ADMIN_URL . '/rain_parking_list.php', 'parking_list'),
    // [신규 추가] 주차 알바/스태프용 현장 POS 화면
    array('800110', '주차 현장 POS (rain)', G5_ADMIN_URL . '/rain_parking_staff_pos.php', 'parking_pos'), 
    
    // 체크인존 관련 (800200 계열)
    array('800200', '체크인존 관리', G5_ADMIN_URL . '/rain_checkin_list.php', 'checkin_list'),
    
    // 이하 기존 메뉴 유지 (파일명에 rain_ 접두사 적용 추천)
    array('800300', '체험부스 관리', G5_ADMIN_URL . '/rain_booth_list.php', 'booth_list'),
    array('800400', '쿠폰 관리', G5_ADMIN_URL . '/rain_coupon_list.php', 'coupon_list'),
    array('800500', '스탬프 관리', G5_ADMIN_URL . '/rain_stamp_list.php', 'stamp_list'),
    array('800600', '음식점 관리', G5_ADMIN_URL . '/rain_restaurant_list.php', 'restaurant_list'),
    array('800700', '채팅', G5_ADMIN_URL . '/chat_list.php', 'chat_list'),
    array('800800', '1:1 문의', G5_ADMIN_URL . '/inquiry_list.php', 'inquiry_list'),
    array('800900', '통계 관리', G5_ADMIN_URL . '/stats_list.php', 'stats_list'),
    
    array('800910', '결제 관리', G5_ADMIN_URL . '/payment_list.php', 'payment_list'),
    array('800920', '시스템 관리(로그)', G5_ADMIN_URL . '/rain_admin_log_list.php', 'system_config') 
);
?>