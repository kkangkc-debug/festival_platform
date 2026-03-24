<?php
include_once('./_common.php');

// 파트너 권한 체크
include_once(G5_PARTNER_PATH . '/partner_check.php');

$act = isset($_POST['act']) ? clean_xss_tags($_POST['act']) : '';
$rm_id = isset($_POST['rm_id']) ? (int)$_POST['rm_id'] : 0;

if ($act == 'insert' || $act == 'update') {
    // 메뉴 등록/수정

    // 기본 필드
    $rm_name = isset($_POST['rm_name']) ? clean_xss_tags($_POST['rm_name']) : '';
    $rm_category = isset($_POST['rm_category']) ? clean_xss_tags($_POST['rm_category']) : '';
    $rm_price = isset($_POST['rm_price']) ? (int)$_POST['rm_price'] : 0;
    $rm_description = isset($_POST['rm_description']) ? clean_xss_tags($_POST['rm_description']) : '';
    $rm_sort_order = isset($_POST['rm_sort_order']) ? (int)$_POST['rm_sort_order'] : 0;
    $rm_is_active = isset($_POST['rm_is_active']) ? 1 : 0;

    if (!$rm_name) {
        alert('메뉴명을 입력해주세요.');
    }

    if ($rm_price < 0) {
        alert('가격은 0원 이상이어야 합니다.');
    }

    // 파일 업로드 처리
    $rm_image = '';
    if (isset($_FILES['rm_image']) && $_FILES['rm_image']['name']) {
        // 업로드 디렉토리 생성
        $upload_dir = G5_DATA_PATH . '/menu';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, G5_DIR_PERMISSION);
            @chmod($upload_dir, G5_DIR_PERMISSION);
        }

        // 파일 업로드
        $file = $_FILES['rm_image'];
        $ext = strtolower(substr(strrchr($file['name'], '.'), 1));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

        if (!in_array($ext, $allowed_ext)) {
            alert('이미지 파일(JPG, PNG, GIF)만 업로드 가능합니다.');
        }

        // 파일명 생성
        $new_filename = 'menu_' . $partner_shop['rt_id'] . '_' . time() . '.' . $ext;
        $upload_file = $upload_dir . '/' . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_file)) {
            @chmod($upload_file, G5_FILE_PERMISSION);
            $rm_image = $new_filename;

            // 기존 이미지 삭제 (수정의 경우)
            if ($act == 'update' && $rm_id) {
                $old_menu = sql_fetch(" SELECT rm_image FROM rain_restaurant_menu WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}' ");
                if ($old_menu && $old_menu['rm_image']) {
                    $old_file = $upload_dir . '/' . $old_menu['rm_image'];
                    if (file_exists($old_file)) {
                        @unlink($old_file);
                    }
                }
            }
        } else {
            alert('파일 업로드에 실패했습니다.');
        }
    }

    if ($act == 'insert') {
        // 메뉴 등록
        $sql = "
            INSERT INTO rain_restaurant_menu SET
                rt_id = '{$partner_shop['rt_id']}',
                fs_id = '" . MY_FS_ID . "',
                rm_name = '{$rm_name}',
                rm_category = '{$rm_category}',
                rm_price = '{$rm_price}',
                rm_description = '{$rm_description}',
                rm_image = '{$rm_image}',
                rm_sort_order = '{$rm_sort_order}',
                rm_is_active = '{$rm_is_active}',
                rm_sold_out = 0,
                rm_reg_date = NOW()
        ";
        sql_query($sql);

        alert('메뉴가 등록되었습니다.', './menu.php');

    } else {
        // 메뉴 수정
        if (!$rm_id) {
            alert('잘못된 접근입니다.');
        }

        // 메뉴 조회
        $menu = sql_fetch(" SELECT * FROM rain_restaurant_menu WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}' ");
        if (!$menu) {
            alert('존재하지 않는 메뉴입니다.');
        }

        // 업데이트 필드 구성
        $update_fields = array(
            "rm_name = '{$rm_name}'",
            "rm_category = '{$rm_category}'",
            "rm_price = '{$rm_price}'",
            "rm_description = '{$rm_description}'",
            "rm_sort_order = '{$rm_sort_order}'",
            "rm_is_active = '{$rm_is_active}'"
        );

        if ($rm_image) {
            $update_fields[] = "rm_image = '{$rm_image}'";
        }

        sql_query("
            UPDATE rain_restaurant_menu
            SET " . implode(', ', $update_fields) . "
            WHERE rm_id = '{$rm_id}' AND rt_id = '{$partner_shop['rt_id']}'
        ");

        alert('메뉴가 수정되었습니다.', './menu.php');
    }

} else {
    alert('잘못된 접근입니다.', './menu.php');
}
?>
