<?php
include_once('./_common.php');

// JSON 응답 설정
header('Content-Type: application/json; charset=utf-8');

if ($member['mb_level'] < 7) {
    echo json_encode(array('error'=>'권한이 없습니다.'));
    exit;
}

$action = $_POST['action'];
$pi_id = (int)$_POST['pi_id'];

// [보안] 본인에게 배정된 주차장인지 확인
if ($is_admin != 'super') {
    $chk = sql_fetch(" SELECT fm_id FROM rain_festival_manager WHERE mb_id = '{$member['mb_id']}' AND target_id = '$pi_id' AND role_type = 'PARKING_STAFF' ");
    if (!$chk['fm_id']) {
        echo json_encode(array('error'=>'본인이 담당하는 주차장이 아닙니다.'));
        exit;
    }
}

// 1. 대수 업데이트 로직
if ($action == 'rain_count') {
    $val = (int)$_POST['val'];
    
    // 원자적(Atomic) 업데이트: 동시 접속 시 수치 꼬임 방지 및 0 이하 방지
    sql_query(" UPDATE rain_park_info 
                SET pi_current_parked = GREATEST(0, pi_current_parked + $val) 
                WHERE pi_id = '$pi_id' ");
                
    $res = sql_fetch(" SELECT pi_current_parked FROM rain_park_info WHERE pi_id = '$pi_id' ");
    echo json_encode(array('success'=>true, 'new_count'=>$res['pi_current_parked']));
    exit;
}

// 2. 운영 상태 업데이트 로직
if ($action == 'rain_status') {
    $status = clean_xss_tags($_POST['status']);
    sql_query(" UPDATE rain_park_info SET pi_status = '$status' WHERE pi_id = '$pi_id' ");
    echo json_encode(array('success'=>true));
    exit;
}