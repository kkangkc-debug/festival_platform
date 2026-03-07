<?php
// [수정1] 관리자 폴더의 _common.php가 아닌, 최상위 common.php를 호출하여 관리자 강제 차단 우회
include_once('../common.php');

header('Content-Type: application/json; charset=utf-8');

// 로그인 여부 확인
if ($is_guest) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

$action = isset($_POST['action']) ? clean_xss_tags($_POST['action']) : '';
$pi_id  = isset($_POST['pi_id']) ? (int)$_POST['pi_id'] : 0;

if (!$pi_id || !$action) {
    echo json_encode(['success' => false, 'error' => '잘못된 요청입니다.']);
    exit;
}

// =========================================================================
// [수정2] 레벨(Lv.7) 강제 제한 대신, 실제 배정(Mapping) 여부로 권한 검증!
// =========================================================================
$has_auth = false;

if ($is_admin == 'super') {
    // 1. 최고관리자 패스
    $has_auth = true;
} else if ($member['mb_level'] >= 8 && defined('MY_FS_ID')) {
    // 2. 행사관리자(Lv.8) - 본인 행사의 주차장인지 확인 후 패스
    $chk = sql_fetch(" SELECT pi_id FROM rain_park_info WHERE pi_id = '{$pi_id}' AND fs_id = '".MY_FS_ID."' ");
    if (isset($chk['pi_id']) && $chk['pi_id']) $has_auth = true;
} else {
    // 3. 일반 스태프 (레벨 무관, DB에 해당 주차장 번호로 매핑되어 있는지 확인)
    $mapping = sql_fetch(" SELECT target_id FROM rain_festival_manager 
                           WHERE mb_id = '{$member['mb_id']}' AND role_type = 'PARKING_STAFF' AND target_id = '{$pi_id}' ");
    if (isset($mapping['target_id']) && $mapping['target_id']) $has_auth = true;
}

// 권한이 없으면 여기서 차단
if (!$has_auth) {
    echo json_encode(['success' => false, 'error' => '해당 주차장을 제어할 권한이 없습니다.']);
    exit;
}
// =========================================================================


// -------------------------------------------------------------------------
// 1. [구버전 호환] 전체 주차 카운트 단순 증감
// -------------------------------------------------------------------------
if ($action == 'rain_count') {
    $val = (int)$_POST['val'];
    
    $sql = " UPDATE rain_park_info 
             SET pi_current_parked = GREATEST(0, pi_current_parked + {$val}) 
             WHERE pi_id = '{$pi_id}' ";
    $result = sql_query($sql);
    
    if ($result) {
        $row = sql_fetch(" SELECT pi_current_parked FROM rain_park_info WHERE pi_id = '{$pi_id}' ");
        echo json_encode(['success' => true, 'new_count' => (int)$row['pi_current_parked']]);
    } else {
        echo json_encode(['success' => false, 'error' => '업데이트 실패']);
    }
    exit;
}

// -------------------------------------------------------------------------
// 2. [신규 추가] 유형별 주차 카운트 업데이트 로직
// -------------------------------------------------------------------------
if ($action == 'rain_count_type') {
    $type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : '';
    $val = (int)$_POST['val'];
    
    $col = '';
    if ($type == 'general') $col = 'pi_parked_general';
    else if ($type == 'barrier') $col = 'pi_parked_barrier';
    else if ($type == 'large') $col = 'pi_parked_large';

    if ($col) {
        // 1) 해당 유형 카운트 증감 (음수 방지)
        $sql = " UPDATE rain_park_info SET {$col} = GREATEST(0, {$col} + {$val}) WHERE pi_id = '{$pi_id}' ";
        sql_query($sql);
        
        // 2) 전체 주차 카운트(pi_current_parked)를 세 가지 유형의 합산으로 자동 동기화
        $sql2 = " UPDATE rain_park_info 
                  SET pi_current_parked = pi_parked_general + pi_parked_barrier + pi_parked_large 
                  WHERE pi_id = '{$pi_id}' ";
        sql_query($sql2);
        
        // 3) 화면에 뿌려줄 갱신된 결과 가져오기
        $row = sql_fetch(" SELECT {$col}, pi_current_parked FROM rain_park_info WHERE pi_id = '{$pi_id}' ");
        
        echo json_encode([
            'success' => true, 
            'type_count' => (int)$row[$col], 
            'total_count' => (int)$row['pi_current_parked']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => '올바르지 않은 주차 유형입니다.']);
    }
    exit;
}

// -------------------------------------------------------------------------
// 3. 주차장 상태 (운영/혼잡/만차) 업데이트
// -------------------------------------------------------------------------
if ($action == 'rain_status') {
    $status = isset($_POST['status']) ? clean_xss_tags($_POST['status']) : '운영';
    $sql = " UPDATE rain_park_info 
             SET pi_status = '{$status}' 
             WHERE pi_id = '{$pi_id}' ";
    
    if(sql_query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => '상태 변경 실패']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => '알 수 없는 액션입니다.']);
exit;
?>