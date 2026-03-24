<?php
include_once('./_common.php');

// 파트너 권한 체크
include_once(G5_PARTNER_PATH . '/partner_check.php');

header('Content-Type: application/json; charset=utf-8');

$action = isset($_POST['action']) ? clean_xss_tags($_POST['action']) : '';
$result = array('success' => false, 'message' => '', 'data' => null);

try {
    switch ($action) {
        // 메뉴 정보 조회
        case 'get_menu':
            $rm_id = isset($_POST['rm_id']) ? (int)$_POST['rm_id'] : 0;

            if (!$rm_id) {
                throw new Exception('잘못된 요청입니다.');
            }

            $menu = sql_fetch("
                SELECT * FROM rain_restaurant_menu
                WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            if (!$menu) {
                throw new Exception('존재하지 않는 메뉴입니다.');
            }

            $result['success'] = true;
            $result['data'] = $menu;
            break;

        // 품절 토글
        case 'toggle_sold_out':
            $rm_id = isset($_POST['rm_id']) ? (int)$_POST['rm_id'] : 0;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

            if (!$rm_id) {
                throw new Exception('잘못된 요청입니다.');
            }

            // 메뉴 조회
            $menu = sql_fetch("
                SELECT * FROM rain_restaurant_menu
                WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            if (!$menu) {
                throw new Exception('존재하지 않는 메뉴입니다.');
            }

            // 품절 상태 업데이트
            sql_query("
                UPDATE rain_restaurant_menu
                SET rm_sold_out = '{$status}'
                WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            $result['success'] = true;
            $result['message'] = $status == 1 ? '품절로 표시했습니다.' : '판매를 재개했습니다.';
            break;

        // 활성화 토글
        case 'toggle_active':
            $rm_id = isset($_POST['rm_id']) ? (int)$_POST['rm_id'] : 0;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

            if (!$rm_id) {
                throw new Exception('잘못된 요청입니다.');
            }

            // 메뉴 조회
            $menu = sql_fetch("
                SELECT * FROM rain_restaurant_menu
                WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            if (!$menu) {
                throw new Exception('존재하지 않는 메뉴입니다.');
            }

            // 활성화 상태 업데이트
            sql_query("
                UPDATE rain_restaurant_menu
                SET rm_is_active = '{$status}'
                WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            $result['success'] = true;
            $result['message'] = $status == 1 ? '활성화했습니다.' : '비활성화했습니다.';
            break;

        // 메뉴 정렬 순서 변경
        case 'update_sort_order':
            $rm_id = isset($_POST['rm_id']) ? (int)$_POST['rm_id'] : 0;
            $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;

            if (!$rm_id) {
                throw new Exception('잘못된 요청입니다.');
            }

            // 메뉴 조회
            $menu = sql_fetch("
                SELECT * FROM rain_restaurant_menu
                WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            if (!$menu) {
                throw new Exception('존재하지 않는 메뉴입니다.');
            }

            // 정렬 순서 업데이트
            sql_query("
                UPDATE rain_restaurant_menu
                SET rm_sort_order = '{$sort_order}'
                WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
            ");

            $result['success'] = true;
            $result['message'] = '정렬 순서가 변경되었습니다.';
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
