<?php
$sub_menu = "900100";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

// 그누보드 달력 UI용 스크립트 로드
add_javascript('<script src="'.G5_JS_URL.'/jquery-ui.min.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/font-awesome/css/font-awesome.min.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/jquery-ui.css">', 0);

$w = isset($_GET['w']) ? clean_xss_tags($_GET['w']) : '';
$fs_id = isset($_GET['fs_id']) ? (int)$_GET['fs_id'] : 0;

if ($w == 'u') {
    $html_title = '행사 정보 수정';
    $sql = " select * from rain_festival where fs_id = '{$fs_id}' ";
    $row = sql_fetch($sql);
    if (!$row['fs_id']) alert('존재하지 않는 행사입니다.');
} else {
    $html_title = '새 행사 개설';
    $row = array(
        'fs_name' => '',
        'fs_start_date' => G5_TIME_YMD,
        'fs_end_date' => G5_TIME_YMD,
        'fs_status' => '준비중'
    );
}

$g5['title'] = $html_title;
include_once('./admin.head.php');
?>

<form name="ffestivalform" id="ffestivalform" action="./rain_festival_form_update.php" method="post" autocomplete="off">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="fs_id" value="<?php echo $fs_id; ?>">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row"><label for="fs_name">행사명<strong class="sound_only">필수</strong></label></th>
        <td><input type="text" name="fs_name" value="<?php echo get_text($row['fs_name']); ?>" id="fs_name" required class="frm_input required" size="50" placeholder="예: 2026 순천만 축제"></td>
    </tr>
    <tr>
        <th scope="row"><label for="fs_start_date">행사 기간<strong class="sound_only">필수</strong></label></th>
        <td>
            <input type="text" name="fs_start_date" value="<?php echo $row['fs_start_date']; ?>" id="fs_start_date" required class="frm_input required" size="11" maxlength="10">
            ~
            <input type="text" name="fs_end_date" value="<?php echo $row['fs_end_date']; ?>" id="fs_end_date" required class="frm_input required" size="11" maxlength="10">
        </td>
    </tr>
    <tr>
        <th scope="row">운영상태</th>
        <td>
            <input type="radio" name="fs_status" value="준비중" id="st_prep" <?php echo $row['fs_status']=='준비중'?'checked':''; ?>><label for="st_prep"> 준비중</label>&nbsp;
            <input type="radio" name="fs_status" value="진행중" id="st_run" <?php echo $row['fs_status']=='진행중'?'checked':''; ?>><label for="st_run"> 진행중</label>&nbsp;
            <input type="radio" name="fs_status" value="종료" id="st_end" <?php echo $row['fs_status']=='종료'?'checked':''; ?>><label for="st_end"> 종료</label>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_festival_list.php?<?php echo $qstr; ?>" class="btn btn_02">취소(목록)</a>
    <input type="submit" value="<?php echo $w=='u'?'수정완료':'개설완료'; ?>" class="btn_submit btn" accesskey="s">
</div>
</form>

<script>
$(function(){
    // 날짜 선택기(Datepicker) 스크립트
    $("#fs_start_date, #fs_end_date").datepicker({
        changeMonth: true, 
        changeYear: true, 
        dateFormat: "yy-mm-dd", 
        showButtonPanel: true, 
        yearRange: "c-99:c+99"
    });
});
</script>

<?php include_once('./admin.tail.php'); ?>