<?php
$sub_menu = "800400";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = isset($_GET['w']) ? clean_xss_tags($_GET['w']) : '';
$cp_id = isset($_GET['cp_id']) ? (int)$_GET['cp_id'] : 0;

if ($w == 'u') {
    $html_title = '쿠폰 수정';
    $sql = " select * from rain_coupon_info where cp_id = '{$cp_id}' ";
    $row = sql_fetch($sql);
    if (!$row['cp_id']) alert('존재하지 않는 쿠폰입니다.');

    // [보안] 행사관리자(Lv.8) 타 행사 데이터 열람 방어
    if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
        if ($row['fs_id'] != MY_FS_ID) alert('접근 권한이 없는 쿠폰입니다.');
    }
} else {
    $html_title = '쿠폰 등록';
    $row = array(
        'fs_id' => defined('MY_FS_ID') ? MY_FS_ID : 0,
        'cp_name' => '',
        'cp_type' => '금액할인',
        'cp_amount' => 0,
        'cp_start_date' => G5_TIME_YMD,
        'cp_end_date' => G5_TIME_YMD,
        'cp_use_limit' => 0,
        'cp_status' => '발급중',
        'cp_memo' => ''
    );
}

$g5['title'] = $html_title;
include_once('./admin.head.php');
?>

<form name="fcouponform" id="fcouponform" action="./rain_coupon_form_update.php" method="post" autocomplete="off">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="cp_id" value="<?php echo $cp_id; ?>">
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
                        <option value="0">-- 소속 없음 (공용) --</option>
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
                    <span class="frm_info" style="color:#ff3061; margin-left:10px;">※ 관리자님에게 배정된 행사로 자동 고정됩니다.</span>
                <?php } ?>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기본 정보 설정</td></tr>
        <tr>
            <th scope="row"><label for="cp_name">쿠폰 명<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="cp_name" value="<?php echo get_text($row['cp_name']); ?>" id="cp_name" required class="frm_input required" size="50" placeholder="예: 푸드존 2,000원 할인권">
            </td>
        </tr>
        <tr>
            <th scope="row">할인 종류 및 혜택</th>
            <td>
                <select name="cp_type" id="cp_type" class="frm_input">
                    <option value="금액할인" <?php echo $row['cp_type']=='금액할인'?'selected':''; ?>>금액 할인(원)</option>
                    <option value="퍼센트할인" <?php echo $row['cp_type']=='퍼센트할인'?'selected':''; ?>>퍼센트 할인(%)</option>
                    <option value="교환권" <?php echo $row['cp_type']=='교환권'?'selected':''; ?>>상품 교환권</option>
                </select>
                <input type="number" name="cp_amount" value="<?php echo (int)$row['cp_amount']; ?>" id="cp_amount" class="frm_input" size="10" placeholder="숫자 입력">
                <span class="frm_info">교환권일 경우 금액(숫자)은 0으로 두셔도 무방합니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row">유효 기간 (사용 가능 기간)</th>
            <td>
                <input type="date" name="cp_start_date" value="<?php echo $row['cp_start_date']; ?>" class="frm_input" required>
                ~
                <input type="date" name="cp_end_date" value="<?php echo $row['cp_end_date']; ?>" class="frm_input" required>
            </td>
        </tr>
        
        <tr><td colspan="2" class="h2_frm">발급 및 운영 설정</td></tr>
        <tr>
            <th scope="row"><label for="cp_use_limit">총 발급 제한 수량</label></th>
            <td>
                <input type="number" name="cp_use_limit" value="<?php echo (int)$row['cp_use_limit']; ?>" id="cp_use_limit" class="frm_input" size="10"> 장
                <span class="frm_info">0을 입력하면 <strong>수량 무제한</strong>으로 발급됩니다. (선착순 쿠폰일 경우 수량 입력)</span>
            </td>
        </tr>
        <tr>
            <th scope="row">운영 상태</th>
            <td>
                <input type="radio" name="cp_status" value="대기" id="st_wait" <?php echo $row['cp_status']=='대기'?'checked':''; ?>><label for="st_wait"> 대기 (발급전)</label>&nbsp;
                <input type="radio" name="cp_status" value="발급중" id="st_run" <?php echo $row['cp_status']=='발급중'?'checked':''; ?>><label for="st_run"> 발급중 (사용자 노출)</label>&nbsp;
                <input type="radio" name="cp_status" value="마감" id="st_cls" <?php echo $row['cp_status']=='마감'?'checked':''; ?>><label for="st_cls"> 마감 (발급중단)</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cp_memo">관리자 메모</label></th>
            <td>
                <textarea name="cp_memo" id="cp_memo" class="frm_input" style="height:80px;"><?php echo get_text($row['cp_memo']); ?></textarea>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_coupon_list.php" class="btn btn_02">목록/취소</a>
    <input type="submit" value="<?php echo $w=='u'?'수정완료':'등록'; ?>" class="btn_submit btn" accesskey="s">
</div>
</form>

<?php include_once('./admin.tail.php'); ?>