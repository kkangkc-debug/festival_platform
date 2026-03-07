<?php
$sub_menu = "800000"; 
include_once('./_common.php');

// 권한 체크 (슈퍼관리자거나, 배정된 행사가 있는 레벨 8)
if ($is_admin != 'super' && (!defined('MY_FS_ID') || MY_FS_ID <= 0)) {
    alert('접근 권한이 없습니다.', G5_URL);
}

$g5['title'] = '행사 관리 대시보드';
include_once('./admin.head.php');

// 현재 행사 정보 가져오기
if (MY_FS_ID > 0) {
    $fs = sql_fetch(" SELECT * FROM rain_festival WHERE fs_id = '".MY_FS_ID."' ");
    $festival_name = get_text($fs['fs_name']);
    $festival_date = $fs['fs_start_date'] . ' ~ ' . $fs['fs_end_date'];
} else {
    $festival_name = "전체 행사 통합 보기 (최고관리자)";
    $festival_date = "-";
}
?>

<div style="background:#fff; border:1px solid #e5e5e5; border-radius:8px; padding:40px; text-align:center; margin-bottom:30px; box-shadow:0 2px 10px rgba(0,0,0,0.02);">
    <h2 style="font-size:2em; color:#3f51b5; margin-bottom:15px;"><?php echo $festival_name; ?></h2>
    <p style="font-size:1.1em; color:#666; margin-bottom:5px;">환영합니다, <strong><?php echo $member['mb_name']; ?></strong> 관리자님!</p>
    <?php if(MY_FS_ID > 0) { ?>
        <p style="color:#888;">행사 기간: <?php echo $festival_date; ?></p>
    <?php } ?>
</div>

<div style="display:flex; gap:20px;">
    <div style="flex:1; background:#f9f9f9; padding:20px; border-radius:5px; border:1px solid #ddd;">
        <h3>📌 시작하기 가이드</h3>
        <ul style="margin-top:10px; line-height:1.8em; color:#555;">
            <li>1. 좌측 <strong>[주차장 관리]</strong>에서 주차장을 등록하세요.</li>
            <li>2. 좌측 <strong>[체크인존 관리]</strong>에서 QR 단말기를 연동할 구역을 만드세요.</li>
            <li>3. 좌측 <strong>[상점/부스 관리]</strong>에서 참여 업체를 등록하고 계정을 발급하세요.</li>
        </ul>
    </div>
</div>

<?php include_once('./admin.tail.php'); ?>