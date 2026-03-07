<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

// 권한 확인
if ($member['mb_level'] < 7) {
    echo json_encode(['success' => false, 'error' => '권한이 없습니다.']);
    exit;
}

$action = isset($_POST['action']) ? clean_xss_tags($_POST['action']) : '';
$bt_id  = isset($_POST['bt_id']) ? (int)$_POST['bt_id'] : 0;

if (!$bt_id || !$action) {
    echo json_encode(['success' => false, 'error' => '잘못된 요청입니다.']);
    exit;
}

// 1. 체험 인원 +1 증가 (방문자 누적 통계용)
if ($action == 'add_visitor') {
    $sql = " UPDATE rain_booth_info 
             SET bt_today_visitors = bt_today_visitors + 1 
             WHERE bt_id = '{$bt_id}' ";
    $result = sql_query($sql);
    
    if ($result) {
        $row = sql_fetch(" SELECT bt_today_visitors FROM rain_booth_info WHERE bt_id = '{$bt_id}' ");
        echo json_encode(['success' => true, 'new_count' => number_format($row['bt_today_visitors'])]);
    } else {
        echo json_encode(['success' => false, 'error' => '업데이트 실패']);
    }
    exit;
}

// 2. 부스 상태 (운영/점검/마감) 변경
if ($action == 'update_status') {
    $status = isset($_POST['status']) ? clean_xss_tags($_POST['status']) : '운영';
    $sql = " UPDATE rain_booth_info 
             SET bt_status = '{$status}' 
             WHERE bt_id = '{$bt_id}' ";
    if(sql_query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => '상태 변경 실패']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => '알 수 없는 오류']);
?>