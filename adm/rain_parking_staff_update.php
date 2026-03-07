<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

// 권한 확인 (Lv.7 이상)
if ($member['mb_level'] < 7) {
    echo json_encode(['success' => false, 'error' => '권한이 없습니다.']);
    exit;
}

$action = isset($_POST['action']) ? clean_xss_tags($_POST['action']) : '';
$pi_id  = isset($_POST['pi_id']) ? (int)$_POST['pi_id'] : 0;

if (!$pi_id || !$action) {
    echo json_encode(['success' => false, 'error' => '잘못된 요청입니다.']);
    exit;
}

// -------------------------------------------------------------------------
// 1. [구버전 호환] 전체 주차 카운트 단순 증감 (사용 안 할 수도 있음)
// -------------------------------------------------------------------------
if ($action == 'rain_count') {
    $val = (int)$_POST['val'];
    
    // 음수가 되지 않도록 방어 (GREATEST 함수)
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
    // 전달받은 type 값에 따라 실제 DB 컬럼 매칭
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