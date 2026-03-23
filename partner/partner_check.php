<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

// 파트너(상점주) 전용 권한 체크

// 1. 로그인 확인
if (!$member['mb_id']) {
    alert('로그인 후 이용 가능합니다.', G5_BBS_URL . '/login.php');
}

// 2. 파트너 정보 매핑 확인
$partner = sql_fetch("
    SELECT r.*, f.fs_name
    FROM rain_restaurant_info r
    LEFT JOIN rain_festival f ON r.fs_id = f.fs_id
    WHERE r.mb_id = '{$member['mb_id']}'
    LIMIT 1
");

if (!$partner || !$partner['rt_id']) {
    alert('등록된 상점 정보가 없습니다. 관리자에게 문의해주세요.', G5_URL);
}

// 3. 세션에 파트너 정보 저장
if (!isset($_SESSION['PARTNER_RT_ID'])) {
    $_SESSION['PARTNER_RT_ID'] = $partner['rt_id'];
}

// 4. 상점 ID 가져오기
$rt_id = $_SESSION['PARTNER_RT_ID'];

// 5. 현재 상점 정보 재조회 (최신 정보 유지)
$rt = sql_fetch("
    SELECT r.*, f.fs_name, f.fs_start_date, f.fs_end_date
    FROM rain_restaurant_info r
    LEFT JOIN rain_festival f ON r.fs_id = f.fs_id
    WHERE r.rt_id = '{$rt_id}'
    LIMIT 1
");

if (!$rt) {
    alert('상점 정보를 찾을 수 없습니다.', G5_URL);
}

// 6. SaaS 격리를 위한 fs_id 설정
if (!defined('MY_FS_ID')) {
    define('MY_FS_ID', $rt['fs_id']);
}

// 7. 전역 변수로 상점 정보 할당
$partner_shop = $rt;
?>

