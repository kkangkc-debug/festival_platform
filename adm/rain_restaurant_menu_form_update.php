<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$rt_id = isset($_POST['rt_id']) ? (int)$_POST['rt_id'] : 0;
$me_id = isset($_POST['me_id']) ? (int)$_POST['me_id'] : 0;

if (!$rt_id) alert('잘못된 접근입니다.');

// 상점 권한 체크
$rt = sql_fetch(" SELECT fs_id FROM rain_restaurant_info WHERE rt_id = '{$rt_id}' ");
if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
    if ($rt['fs_id'] != MY_FS_ID) alert('권한이 없습니다.');
}

$me_name = clean_xss_tags($_POST['me_name']);
$me_price = (int)$_POST['me_price'];
$me_desc = clean_xss_tags($_POST['me_desc']);
$me_status = clean_xss_tags($_POST['me_status']);
$me_order = (int)$_POST['me_order'];

// 이미지 업로드 폴더 준비
$menu_dir = G5_DATA_PATH.'/menu';
if(!is_dir($menu_dir)) {
    @mkdir($menu_dir, G5_DIR_PERMISSION);
    @chmod($menu_dir, G5_DIR_PERMISSION);
}

// 1. 기존 이미지 삭제 처리
$upload_file = '';
if ($w == 'u') {
    $me = sql_fetch("SELECT me_img FROM rain_restaurant_menu WHERE me_id = '$me_id'");
    $upload_file = $me['me_img'];
    if (isset($_POST['del_img']) && $_POST['del_img'] == 1) {
        @unlink($menu_dir.'/'.$upload_file);
        $upload_file = '';
    }
}

// 2. 신규 이미지 업로드 처리
if (isset($_FILES['me_img']['name']) && $_FILES['me_img']['name']) {
    $ext = strtolower(pathinfo($_FILES['me_img']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'webp'))) {
        $filename = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['me_img']['tmp_name'], $menu_dir.'/'.$filename);
        
        // 기존 파일 있으면 삭제
        if ($w == 'u' && $upload_file && file_exists($menu_dir.'/'.$upload_file)) {
            @unlink($menu_dir.'/'.$upload_file);
        }
        $upload_file = $filename;
    }
}

// 3. DB 저장
$sql_common = " rt_id = '{$rt_id}',
                me_name = '{$me_name}',
                me_price = '{$me_price}',
                me_desc = '{$me_desc}',
                me_img = '{$upload_file}',
                me_status = '{$me_status}',
                me_order = '{$me_order}' ";

if ($w == '') {
    $sql = " INSERT INTO rain_restaurant_menu SET $sql_common ";
    sql_query($sql);
    alert('메뉴가 등록되었습니다.', "./rain_restaurant_menu.php?rt_id={$rt_id}");
    
} else if ($w == 'u') {
    $sql = " UPDATE rain_restaurant_menu SET $sql_common WHERE me_id = '{$me_id}' ";
    sql_query($sql);
    alert('메뉴가 수정되었습니다.', "./rain_restaurant_menu.php?rt_id={$rt_id}");
}
?>