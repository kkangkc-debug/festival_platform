<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = isset($_GET['w']) ? clean_xss_tags($_GET['w']) : '';
$rt_id = isset($_GET['rt_id']) ? (int)$_GET['rt_id'] : 0;

if ($w == 'u') {
    $html_title = '상점 수정';
    $sql = " select * from rain_restaurant_info where rt_id = '{$rt_id}' ";
    $row = sql_fetch($sql);
    if (!$row['rt_id']) alert('존재하지 않는 상점입니다.');

    // [보안] 행사관리자(Lv.8) 타 행사 데이터 열람 방어
    if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
        if ($row['fs_id'] != MY_FS_ID) alert('접근 권한이 없는 상점입니다.');
    }
} else {
    $html_title = '상점 등록';
    $row = array(
        'fs_id' => defined('MY_FS_ID') ? MY_FS_ID : 0,
        'mb_id' => '',
        'rt_name' => '',
        'rt_type' => '푸드트럭',
        'rt_location' => '',
        'rt_manager_name' => '',
        'rt_manager_hp' => '',
        'rt_status' => '영업중',
        'rt_is_show' => 1,
        'rt_memo' => ''
    );
}

$g5['title'] = $html_title;
include_once('./admin.head.php');
?>

<form name="frestform" id="frestform" action="./rain_restaurant_form_update.php" method="post" autocomplete="off">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="rt_id" value="<?php echo $rt_id; ?>">
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
                        <option value="0">-- 소속 없음 --</option>
                        <?php
                        $fs_res = sql_query("SELECT fs_id, fs_name FROM rain_festival ORDER BY fs_id DESC");
                        while ($fs = sql_fetch_array($fs_res)) {
                            $selected = ($fs['fs_id'] == $row['fs_id']) ? 'selected' : '';
                            echo '<option value="'.$fs['fs_id'].'" '.$selected.'>'.get_text($fs['fs_name']).'</option>';
                        }
                        ?>
                    </select>
                <?php } else { 
                    $my_fs_name = '미지정';
                    if (defined('MY_FS_ID') && MY_FS_ID > 0) {
                        $my_fs = sql_fetch(" SELECT fs_name FROM rain_festival WHERE fs_id = '".MY_FS_ID."' ");
                        $my_fs_name = get_text($my_fs['fs_name']);
                    }
                ?>
                    <strong style="font-size:1.1em; color:#000;"><?php echo $my_fs_name; ?></strong>
                    <span class="frm_info" style="color:#ff3061; margin-left:10px;">※ 배정된 행사로 자동 고정됩니다.</span>
                <?php } ?>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">POS 연동 계정 (매우 중요)</td></tr>
        <tr>
            <th scope="row"><label for="mb_id">상점주인 로그인 아이디</label></th>
            <td>
                <select name="mb_id" id="mb_id" class="frm_input">
                    <option value="">-- 상점주인 계정 선택 (선택 안함) --</option>
                    <?php
                    // 탈퇴안함, 차단안함, 최고관리자 제외 조건으로 회원 목록 호출
                    $mb_sql = " SELECT mb_id, mb_name, mb_level FROM {$g5['member_table']} 
                                WHERE mb_leave_date = '' AND mb_intercept_date = '' AND mb_level < 10 
                                ORDER BY mb_datetime DESC ";
                    $mb_res = sql_query($mb_sql);
                    while ($mb = sql_fetch_array($mb_res)) {
                        $selected = ($mb['mb_id'] == $row['mb_id']) ? 'selected' : '';
                        echo '<option value="'.$mb['mb_id'].'" '.$selected.'>'.get_text($mb['mb_name']).' ('.$mb['mb_id'].') - Lv.'.$mb['mb_level'].'</option>';
                    }
                    ?>
                </select>
                <span class="frm_info">이곳에 지정된 아이디로 회원이 로그인하면, <strong>해당 상점의 전용 쿠폰 스캔 화면(POS)</strong>을 이용할 수 있습니다. (회원가입이 선행되어야 함)</span>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">상점 기본 정보</td></tr>
        <tr>
            <th scope="row"><label for="rt_name">상점 명<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="rt_name" value="<?php echo get_text($row['rt_name']); ?>" id="rt_name" required class="frm_input required" size="50" placeholder="예: 맛있는 닭강정 트럭"></td>
        </tr>
        <tr>
            <th scope="row">상점 구분</th>
            <td>
                <select name="rt_type" id="rt_type" class="frm_input">
                    <option value="푸드트럭" <?php echo $row['rt_type']=='푸드트럭'?'selected':''; ?>>푸드트럭</option>
                    <option value="플리마켓" <?php echo $row['rt_type']=='플리마켓'?'selected':''; ?>>플리마켓</option>
                    <option value="일반상점" <?php echo $row['rt_type']=='일반상점'?'selected':''; ?>>일반상점(인근)</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rt_location">위치/구역<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="rt_location" value="<?php echo get_text($row['rt_location']); ?>" id="rt_location" required class="frm_input required" size="60" placeholder="예: 메인광장 푸드존 B-12"></td>
        </tr>
        
        <tr><td colspan="2" class="h2_frm">운영 정보</td></tr>
        <tr>
            <th scope="row"><label for="rt_manager_name">대표자 명</label></th>
            <td><input type="text" name="rt_manager_name" value="<?php echo get_text($row['rt_manager_name']); ?>" id="rt_manager_name" class="frm_input"></td>
        </tr>
        <tr>
            <th scope="row"><label for="rt_manager_hp">연락처</label></th>
            <td><input type="text" name="rt_manager_hp" value="<?php echo get_text($row['rt_manager_hp']); ?>" id="rt_manager_hp" class="frm_input"></td>
        </tr>
        <tr>
            <th scope="row">영업 상태</th>
            <td>
                <input type="radio" name="rt_status" value="영업중" id="st_run" <?php echo $row['rt_status']=='영업중'?'checked':''; ?>><label for="st_run"> 영업중</label>&nbsp;
                <input type="radio" name="rt_status" value="준비중" id="st_chk" <?php echo $row['rt_status']=='준비중'?'checked':''; ?>><label for="st_chk"> 준비중 (재료소진 등)</label>&nbsp;
                <input type="radio" name="rt_status" value="마감" id="st_cls" <?php echo $row['rt_status']=='마감'?'checked':''; ?>><label for="st_cls"> 마감</label>
            </td>
        </tr>
        <tr>
            <th scope="row">앱 노출 여부</th>
            <td>
                <input type="radio" name="rt_is_show" value="1" id="show_y" <?php echo $row['rt_is_show']==1?'checked':''; ?>><label for="show_y"> 노출</label>&nbsp;
                <input type="radio" name="rt_is_show" value="0" id="show_n" <?php echo $row['rt_is_show']==0?'checked':''; ?>><label for="show_n"> 미노출</label>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기타</td></tr>
        <tr>
            <th scope="row"><label for="rt_memo">비고 (메모)</label></th>
            <td><textarea name="rt_memo" id="rt_memo" class="frm_input" style="height:80px;"><?php echo get_text($row['rt_memo']); ?></textarea></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_restaurant_list.php" class="btn btn_02">목록/취소</a>
    <input type="submit" value="<?php echo $w=='u'?'수정완료':'등록'; ?>" class="btn_submit btn" accesskey="s">
</div>
</form>

<?php include_once('./admin.tail.php'); ?>