<?php
define('_GNUBOARD_', true);
include_once('./_common.php');

// 파트너 권한 체크
include_once(G5_PARTNER_PATH . '/partner_check.php');

header('Content-Type: application/json; charset=utf-8');

$action = isset($_POST['action']) ? clean_xss_tags($_POST['action']) : '';
$result = array('success' => false, 'message' => '');

try {
    switch ($action) {
        // 주문 상태 변경
        case 'update_order_status':
            $ro_id = isset($_POST['ro_id']) ? (int)$_POST['ro_id'] : 0;
            $status = isset($_POST['status']) ? clean_xss_tags($_POST['status']) : '';

            if (!$ro_id || !in_array($status, array('pending', 'cooking', 'completed', 'cancelled'))) {
                throw new Exception('잘못된 요청입니다.');
            }

            // 주문 조회
            $order = sql_fetch("
                SELECT * FROM rain_restaurant_order
                WHERE ro_id = '{$ro_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            if (!$order) {
                throw new Exception('존재하지 않는 주문입니다.');
            }

            // 상태 업데이트
            $update_fields = array('ro_status' => $status);

            if ($status == 'completed' && !$order['ro_complete_time']) {
                $update_fields['ro_complete_time'] = 'NOW()';
            }

            $update_set = array();
            foreach ($update_fields as $field => $value) {
                if ($value === 'NOW()') {
                    $update_set[] = "{$field} = {$value}";
                } else {
                    $update_set[] = "{$field} = '{$value}'";
                }
            }

            sql_query("
                UPDATE rain_restaurant_order
                SET " . implode(', ', $update_set) . "
                WHERE ro_id = '{$ro_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            $result['success'] = true;
            $result['message'] = '주문 상태가 변경되었습니다.';
            break;

        // 상점 영업상태 변경
        case 'toggle_shop_status':
            $rt_id = isset($_POST['rt_id']) ? (int)$_POST['rt_id'] : 0;
            $current_status = isset($_POST['current_status']) ? clean_xss_tags($_POST['current_status']) : '';

            if (!$rt_id || $rt_id != $partner_shop['rt_id']) {
                throw new Exception('잘못된 요청입니다.');
            }

            // 상태 토글
            $new_status = '영업중';
            if ($current_status == '영업중') {
                $new_status = '마감';
            } else if ($current_status == '마감') {
                $new_status = '준비중';
            }

            sql_query("
                UPDATE rain_restaurant_info
                SET rt_status = '{$new_status}'
                WHERE rt_id = '{$rt_id}' AND mb_id = '{$member['mb_id']}'
            ");

            $result['success'] = true;
            $result['message'] = '영업상태가 변경되었습니다.';
            $result['new_status'] = $new_status;
            break;

        default:
            throw new Exception('잘못된 액션입니다.');
    }
} catch (Exception $e) {
    $result['message'] = $e->getMessage();
}

echo json_encode($result);
exit;
?>
