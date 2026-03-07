<?php
$sub_menu = "800300";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = isset($_GET['w']) ? clean_xss_tags($_GET['w']) : '';
$bt_id = isset($_GET['bt_id']) ? (int)$_GET['bt_id'] : 0;

if ($w == 'u') {
    $html_title = '체험부스 수정';
    $sql = " select * from rain_booth_info where bt_id = '{$bt_id}' ";
    $row = sql_fetch($sql);
    if (!$row['bt_id']) alert('존재하지 않는 부스입니다.');

    // [보안 추가] 행사관리자(Lv.8)인 경우 타 행사의 부스 데이터 '열람 자체'를 원천 차단
    if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
        if ($row['fs_id'] != MY_FS_ID) alert('접근 권한이 없는 체험부스입니다.');
    }

} else {
    $html_title = '체험부스 등록';
    $row = array(
        'fs_id' => defined('MY_FS_ID') ? MY_FS_ID : 0,
        'bt_name' => '',
        'bt_location' => '',
        'bt_manager_name' => '',
        'bt_manager_hp' => '',
        'bt_status' => '운영',
        'bt_is_show' => 1,
        'bt_memo' => ''
    );
}

$g5['title'] = $html_title;
include_once('./admin.head.php');
?>

<form name="fboothform" id="fboothform" action="./rain_booth_form_update.php" method="post" autocomplete="off">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="bt_id" value="<?php echo $bt_id; ?>">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<?php if ($is_admin != 'super') { ?>
<input type="hidden" name="fs_id" value="<?php echo defined('MY_FS_ID') ? MY_FS_ID : 0; ?>">
<?php } ?>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>

        <tr>
            <th scope="row"><label for="fs_id" style="color:#ff3061;">[SaaS] 행사 소속</label></th>
            <td>
                <?php if ($is_admin == 'super') { ?>
                    <select name="fs_id" id="fs_id" class="frm_input">
                        <option value="0">-- 소속 없음 (미지정) --</option>
                        <?php
                        $fs_res = sql_query("SELECT fs_id, fs_name FROM rain_festival ORDER BY fs_id DESC");
                        while ($fs = sql_fetch_array($fs_res)) {
                            $selected = ($fs['fs_id'] == $row['fs_id']) ? 'selected' : '';
                            echo '<option value="'.$fs['fs_id'].'" '.$selected.'>'.get_text($fs['fs_name']).'</option>';
                        }
                        ?>
                    </select>
                    <span class="frm_info">최고관리자 전용. 부스가 소속될 행사를 지정합니다.</span>
                <?php } else { 
                    // [UX 추가] 행사관리자(Lv.8): 수정 불가, 배정된 행사 이름만 강제 노출
                    $my_fs_name = '미지정';
                    if (defined('MY_FS_ID') && MY_FS_ID > 0) {
                        $my_fs = sql_fetch(" SELECT fs_name FROM rain_festival WHERE fs_id = '".MY_FS_ID."' ");
                        $my_fs_name = get_text($my_fs['fs_name']);
                    }
                ?>
                    <strong style="font-size:1.1em; color:#000;"><?php echo $my_fs_name; ?></strong>
                    <span class="frm_info" style="color:#ff3061; margin-left:10px;">※ 관리자님에게 배정된 행사로 자동 고정되며 변경할 수 없습니다.</span>
                <?php } ?>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기본 정보</td></tr>
        <tr>
            <th scope="row"><label for="bt_name">부스 명<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bt_name" value="<?php echo get_text($row['bt_name']); ?>" id="bt_name" required class="frm_input required" size="50" placeholder="예: 페이스페인팅 체험존">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bt_location">부스 위치/구역<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bt_location" value="<?php echo get_text($row['bt_location']); ?>" id="bt_location" required class="frm_input required" size="60" placeholder="예: A구역 3번 텐트">
            </td>
        </tr>
        
        <tr><td colspan="2" class="h2_frm">운영 정보</td></tr>
        <tr>
            <th scope="row"><label for="bt_manager_name">담당자 명</label></th>
            <td><input type="text" name="bt_manager_name" value="<?php echo get_text($row['bt_manager_name']); ?>" id="bt_manager_name" class="frm_input" placeholder="현장 담당자 이름"></td>
        </tr>
        <tr>
            <th scope="row"><label for="bt_manager_hp">담당자 연락처</label></th>
            <td><input type="text" name="bt_manager_hp" value="<?php echo get_text($row['bt_manager_hp']); ?>" id="bt_manager_hp" class="frm_input" placeholder="010-0000-0000"></td>
        </tr>
        <tr>
            <th scope="row">운영 상태</th>
            <td>
                <input type="radio" name="bt_status" value="운영" id="st_run" <?php echo $row['bt_status']=='운영'?'checked':''; ?>><label for="st_run"> 운영</label>&nbsp;
                <input type="radio" name="bt_status" value="점검" id="st_chk" <?php echo $row['bt_status']=='점검'?'checked':''; ?>><label for="st_chk"> 점검 (일시중단)</label>&nbsp;
                <input type="radio" name="bt_status" value="마감" id="st_cls" <?php echo $row['bt_status']=='마감'?'checked':''; ?>><label for="st_cls"> 마감</label>
            </td>
        </tr>
        <tr>
            <th scope="row">앱 노출</th>
            <td>
                <input type="radio" name="bt_is_show" value="1" id="show_y" <?php echo $row['bt_is_show']==1?'checked':''; ?>><label for="show_y"> 노출</label>&nbsp;
                <input type="radio" name="bt_is_show" value="0" id="show_n" <?php echo $row['bt_is_show']==0?'checked':''; ?>><label for="show_n"> 미노출</label>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기타</td></tr>
        <tr>
            <th scope="row"><label for="bt_memo">비고 (메모)</label></th>
            <td>
                <textarea name="bt_memo" id="bt_memo" class="frm_input" style="height:100px;" placeholder="추가 사항을 입력하세요."><?php echo get_text($row['bt_memo']); ?></textarea>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_booth_list.php" class="btn btn_02">목록/취소</a>
    <input type="submit" value="<?php echo $w=='u'?'수정완료':'등록'; ?>" class="btn_submit btn" accesskey="s">
</div>
</form>

<?php include_once('./admin.tail.php'); ?>