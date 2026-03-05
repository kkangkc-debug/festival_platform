<?php
if (!defined('_GNUBOARD_')) exit;

// 800000: 1Depth 메뉴명, 800100~ : 2Depth 세부 메뉴명
$menu["menu800"] = array(
    array('800000', '주차장 관리', '' . G5_ADMIN_URL . '/parking_list.php', 'parking'),
    array('800100', '주차장 관리', '' . G5_ADMIN_URL . '/parking_list.php', 'parking_list'),
    array('800200', '체크인존 관리', '' . G5_ADMIN_URL . '/checkin_list.php', 'checkin_list'),
    array('800300', '체험부스 관리', '' . G5_ADMIN_URL . '/booth_list.php', 'booth_list'),
    array('800400', '쿠폰 관리', '' . G5_ADMIN_URL . '/coupon_list.php', 'coupon_list'),
    array('800500', '스탬프 관리', '' . G5_ADMIN_URL . '/stamp_list.php', 'stamp_list'),
    array('800600', '음식점 관리', '' . G5_ADMIN_URL . '/restaurant_list.php', 'restaurant_list', 1),
    array('800700', '채팅', '' . G5_ADMIN_URL . '/chat_list.php', 'chat_list'),
    array('800800', '1:1 문의', '' . G5_ADMIN_URL . '/inquiry_list.php', 'inquiry_list'),
    array('800900', '통계 관리', '' . G5_ADMIN_URL . '/stats_list.php', 'stats_list', 1),
    array('801000', '결제 관리', '' . G5_ADMIN_URL . '/payment_list.php', 'payment_list'),
    array('801100', '시스템 관리', '' . G5_ADMIN_URL . '/system_config.php', 'system_config', 1)
);
?>