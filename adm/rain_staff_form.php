<?php
$sub_menu = "800850"; // 메뉴 코드 동기화 (800850)
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '현장 스태프 배정';
include_once('./admin.head.php');

// SaaS 행사에 속한 주차장 목록 불러오기
$pi_sql = " SELECT pi_id, pi_name FROM rain_park_info WHERE 1=1 ";
if (defined('MY_FS_ID') && MY_FS_ID > 0) $pi_sql .= " AND fs_id = '" . MY_FS_ID . "' ";
$pi_res = sql_query($pi_sql);

// SaaS 행사에 속한 체험부스 목록 불러오기
$bt_sql = " SELECT bt_id, bt_name FROM rain_booth_info WHERE 1=1 ";
if (defined('MY_FS_ID') && MY_FS_ID > 0) $bt_sql .= " AND fs_id = '" . MY_FS_ID . "' ";
$bt_res = sql_query($bt_sql);
?>

<form name="fstaffform" id="fstaffform" action="./rain_staff_form_update.php" method="post" autocomplete="off">
<input type="hidden" name="w" value="">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
<?php if ($is_admin != 'super') { ?>
<input type="hidden" name="fs_id" value="<?php echo defined('MY_FS_ID') ? MY_FS_ID : 0; ?>">
<?php } ?>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>

        <?php if ($is_admin == 'super') { ?>
        <tr>
            <th scope="row"><label for="fs_id" style="color:#ff3061;">[SaaS] 행사 소속</label></th>
            <td>
                <select name="fs_id" id="fs_id" class="frm_input">
                    <?php
                    $fs_res = sql_query("SELECT fs_id, fs_name FROM rain_festival ORDER BY fs_id DESC");
                    while ($fs = sql_fetch_array($fs_res)) {
                        echo '<option value="'.$fs['fs_id'].'">'.get_text($fs['fs_name']).'</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php } ?>

        <tr>
            <th scope="row"><label for="mb_id">스태프 계정 선택<strong class="sound_only">필수</strong></label></th>
            <td>
                <select name="mb_id" id="mb_id" class="frm_input" required>
                    <option value="">-- 배정할 스태프/관리자 아이디 선택 --</option>
                    <?php
                    // [수정] 권한 8(행사관리자) 이하의 회원 모두 노출
                    $mb_sql = " SELECT mb_id, mb_name, mb_level FROM {$g5['member_table']} WHERE mb_leave_date = '' AND mb_level <= 8 ORDER BY mb_datetime DESC ";
                    $mb_res = sql_query($mb_sql);
                    while ($mb = sql_fetch_array($mb_res)) {
                        $level_txt = ($mb['mb_level'] == 8) ? '[행사관리자] ' : '[스태프] ';
                        echo '<option value="'.$mb['mb_id'].'">'.$level_txt.get_text($mb['mb_name']).' ('.$mb['mb_id'].')</option>';
                    }
                    ?>
                </select>
                <span class="frm_info">회원 레벨이 8(행사관리자) 이하인 사용자 목록입니다. 최고관리자는 제외됩니다.</span>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="role_target">배정할 구역 (주차장/부스)<strong class="sound_only">필수</strong></label></th>
            <td>
                <select name="role_target" id="role_target" class="frm_input" required>
                    <option value="">-- 배정할 현장 선택 --</option>
                    
                    <optgroup label="[주차장 스태프로 배정]">
                        <?php while ($pi = sql_fetch_array($pi_res)) { ?>
                            <option value="PARKING_STAFF|<?php echo $pi['pi_id']; ?>">🚗 주차장: <?php echo get_text($pi['pi_name']); ?></option>
                        <?php } ?>
                    </optgroup>

                    <optgroup label="[체험부스 스태프로 배정]">
                        <?php while ($bt = sql_fetch_array($bt_res)) { ?>
                            <option value="BOOTH_STAFF|<?php echo $bt['bt_id']; ?>">🎪 부스: <?php echo get_text($bt['bt_name']); ?></option>
                        <?php } ?>
                    </optgroup>
                </select>
                <span class="frm_info">이곳에서 지정한 구역의 POS 화면만 해당 알바생/관리자가 제어할 수 있습니다.</span>
            </td>
        </tr>

        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_staff_list.php" class="btn btn_02">목록/취소</a>
    <input type="submit" value="배정 완료" class="btn_submit btn" accesskey="s">
</div>
</form>

<?php include_once('./admin.tail.php'); ?>