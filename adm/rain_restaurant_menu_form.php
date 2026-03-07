<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$rt_id = isset($_GET['rt_id']) ? (int)$_GET['rt_id'] : 0;
$me_id = isset($_GET['me_id']) ? (int)$_GET['me_id'] : 0;
$w = isset($_GET['w']) ? clean_xss_tags($_GET['w']) : '';

if (!$rt_id) alert('잘못된 접근입니다.');

// 상점 권한 체크
$sql = " SELECT * FROM rain_restaurant_info WHERE rt_id = '{$rt_id}' ";
$rt = sql_fetch($sql);
if (!$rt['rt_id']) alert('존재하지 않는 상점입니다.', './rain_restaurant_list.php');
if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
    if ($rt['fs_id'] != MY_FS_ID) alert('접근 권한이 없는 상점입니다.', './rain_restaurant_list.php');
}

if ($w == 'u') {
    $html_title = '메뉴 수정';
    $me = sql_fetch(" SELECT * FROM rain_restaurant_menu WHERE me_id = '{$me_id}' AND rt_id = '{$rt_id}' ");
    if (!$me['me_id']) alert('존재하지 않는 메뉴입니다.');
} else {
    $html_title = '메뉴 추가';
    // [수정된 부분] 신규 등록 시 변수들이 비어있어 발생하는 Warning 방지를 위해 초기값 설정
    $me = array(
        'me_name' => '',
        'me_price' => 0,
        'me_desc' => '',
        'me_img' => '',
        'me_status' => '판매중',
        'me_order' => 0
    );
}

$g5['title'] = get_text($rt['rt_name']) . ' - ' . $html_title;
include_once('./admin.head.php');
?>

<form name="fmenuform" id="fmenuform" action="./rain_restaurant_menu_form_update.php" method="post" enctype="multipart/form-data" autocomplete="off">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="rt_id" value="<?php echo $rt_id; ?>">
<input type="hidden" name="me_id" value="<?php echo $me_id; ?>">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        
        <tr>
            <th scope="row"><label for="me_name">메뉴명<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="me_name" value="<?php echo get_text($me['me_name']); ?>" id="me_name" required class="frm_input required" size="50" placeholder="예: 매콤 달콤 떡볶이"></td>
        </tr>
        <tr>
            <th scope="row"><label for="me_price">판매 가격<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="number" name="me_price" value="<?php echo $me['me_price']; ?>" id="me_price" required class="frm_input required" size="20" placeholder="0"> 원
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="me_desc">메뉴 설명</label></th>
            <td><textarea name="me_desc" id="me_desc" class="frm_input" style="height:80px;" placeholder="메뉴에 대한 간단한 설명을 적어주세요."><?php echo get_text($me['me_desc']); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="me_img">메뉴 이미지</label></th>
            <td>
                <input type="file" name="me_img" id="me_img" class="frm_input" accept="image/*">
                <?php if($w == 'u' && isset($me['me_img']) && $me['me_img']) { ?>
                    <div style="margin-top:10px;">
                        <img src="<?php echo G5_DATA_URL.'/menu/'.$me['me_img']; ?>" style="max-height:100px; border-radius:5px; border:1px solid #ccc; vertical-align:middle;">
                        <label style="margin-left:10px; color:#ff3061;"><input type="checkbox" name="del_img" value="1"> 기존 이미지 삭제</label>
                    </div>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row">판매 상태</th>
            <td>
                <input type="radio" name="me_status" value="판매중" id="st_sale" <?php echo $me['me_status']=='판매중'?'checked':''; ?>><label for="st_sale"> 판매중</label>&nbsp;
                <input type="radio" name="me_status" value="품절" id="st_sold" <?php echo $me['me_status']=='품절'?'checked':''; ?>><label for="st_sold"> 품절</label>&nbsp;
                <input type="radio" name="me_status" value="숨김" id="st_hide" <?php echo $me['me_status']=='숨김'?'checked':''; ?>><label for="st_hide"> 숨김 (메뉴판 미노출)</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="me_order">정렬 순서</label></th>
            <td>
                <input type="number" name="me_order" value="<?php echo $me['me_order']; ?>" id="me_order" class="frm_input" size="10">
                <span class="frm_info">숫자가 작을수록 메뉴판 상단에 노출됩니다. (기본값 0)</span>
            </td>
        </tr>
        
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_restaurant_menu.php?rt_id=<?php echo $rt_id; ?>" class="btn btn_02">취소 / 목록</a>
    <input type="submit" value="<?php echo $w=='u'?'수정완료':'등록'; ?>" class="btn_submit btn" accesskey="s">
</div>
</form>

<?php include_once('./admin.tail.php'); ?>