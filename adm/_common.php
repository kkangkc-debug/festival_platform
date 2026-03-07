<?php
define('G5_IS_ADMIN', true);
require_once '../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';

if (isset($token)) {
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
}

run_event('admin_common');

// =========================================================================
// 커스텀: 관리자 페이지 행동 로그 기록 (기획서 맞춤형 고도화 + 10초 중복 방지)
// =========================================================================
if ($is_admin == 'super' || isset($member['mb_id'])) {
    
    // AJAX 요청은 로그에서 제외 (화면 이동 및 실제 액션만 기록)
    $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    
    if (!$is_ajax) {
        $log_mb_id = $member['mb_id'];
        $log_ip = $_SERVER['REMOTE_ADDR'];
        $log_url = $_SERVER['REQUEST_URI'];
        $log_method = $_SERVER['REQUEST_METHOD'];
        $script_name = basename($_SERVER['SCRIPT_NAME']);
        
        // 1. 메뉴명 자동 판별
        $log_menu = '기타 관리';
        if (strpos($script_name, 'parking') !== false) $log_menu = '주차장 관리';
        else if (strpos($script_name, 'checkin') !== false) $log_menu = '체크인존 관리';
        else if (strpos($script_name, 'booth') !== false) $log_menu = '체험부스 관리';
        else if (strpos($script_name, 'coupon') !== false) $log_menu = '쿠폰 관리';
        else if (strpos($script_name, 'stamp') !== false) $log_menu = '스탬프 관리';
        else if (strpos($script_name, 'restaurant') !== false) $log_menu = '음식점 관리';
        else if (strpos($script_name, 'member') !== false) $log_menu = '회원 관리';
        else if (strpos($script_name, 'config') !== false) $log_menu = '환경 설정';
        else if (strpos($script_name, 'log_list') !== false) $log_menu = '시스템 관리(로그)';

        // 2. 로그 유형(행위) 자동 판별
        $log_type = '읽기';
        if ($log_method === 'POST') {
            $w = isset($_POST['w']) ? $_POST['w'] : '';
            if ($w == '') $log_type = '등록';
            else if ($w == 'u') $log_type = '수정';
            else if ($w == 'd') $log_type = '삭제';
            else $log_type = '수정'; // 기타 POST는 수정으로 간주
        } else {
            $w = isset($_GET['w']) ? $_GET['w'] : '';
            if ($w == 'd') $log_type = '삭제';
            // 액션 버튼(선택수정, 선택삭제) 처리
            if (isset($_GET['act_button']) || isset($_POST['act_button'])) {
                $act = isset($_GET['act_button']) ? $_GET['act_button'] : $_POST['act_button'];
                if (strpos($act, '삭제') !== false) $log_type = '삭제';
                else if (strpos($act, '수정') !== false) $log_type = '수정';
            }
        }

        // [추가된 로직] 10초 이내 동일 URL '읽기' 동작 중복 방지 (세션 활용)
        $insert_log = true;
        if ($log_type === '읽기') {
            $session_key = 'ss_admin_log_' . md5($log_url); // URL별 고유 세션키 생성
            $last_time = get_session($session_key);
            
            // 마지막 기록 시간이 존재하고, 현재 시간과 차이가 10초 이내라면 기록 안 함
            if ($last_time && (G5_SERVER_TIME - $last_time) < 10) {
                $insert_log = false; 
            } else {
                // 10초가 지났거나 처음 접근이면 현재 시간으로 세션 갱신
                set_session($session_key, G5_SERVER_TIME); 
            }
        }

        // DB에 기록 실행
        if ($insert_log) {
            // 3. 민감 정보 마스킹 및 JSON 압축
            $post_data = $_POST;
            if (isset($post_data['mb_password'])) $post_data['mb_password'] = '***masked***';
            $log_query = json_encode(array('GET'=>$_GET, 'POST'=>$post_data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            // 결과 처리
            $log_result = '성공';

            $sql_log = " INSERT INTO rain_admin_action_log
                         SET mb_id = '{$log_mb_id}',
                             log_ip = '{$log_ip}',
                             log_url = '{$log_url}',
                             log_method = '{$log_method}',
                             log_type = '{$log_type}',
                             log_menu = '{$log_menu}',
                             log_result = '{$log_result}',
                             log_query = '".addslashes($log_query)."',
                             log_datetime = '".G5_TIME_YMDHIS."' ";
            sql_query($sql_log, false);
        }
    }
}
// =========================================================================



// =========================================================================
// [SaaS 코어] 관리자 권한별 행사(Festival) 식별 및 격리 로직
// =========================================================================
if ($is_admin != 'super' && $member['mb_level'] == 8) {
    // 1. 레벨 8(행사 총괄관리자)일 경우, 소속된 행사를 찾습니다.
    $my_fs = sql_fetch(" SELECT fs_id FROM rain_festival_manager WHERE mb_id = '{$member['mb_id']}' AND role_type = '총괄관리자' ");
    
    if ($my_fs['fs_id']) {
        // 내 행사 ID를 전역 상수(MY_FS_ID)로 등록하여 모든 페이지에서 사용 가능하게 함
        define('MY_FS_ID', $my_fs['fs_id']); 
    } else {
        alert('배정된 행사 권한이 없습니다. 최고관리자에게 문의하세요.', G5_URL);
    }
} else if ($is_admin == 'super') {
    // 2. 최고관리자일 경우, 모든 데이터를 볼 수 있도록 기본값 0 부여 
    // (추후 콤보박스 등으로 특정 행사만 골라보는 기능을 위해 세션으로 확장 가능)
    define('MY_FS_ID', 0); 
} else {
    // 일반 스태프 등 기타 권한 (추후 작업)
    define('MY_FS_ID', -1); 
}
// =========================================================================