<?php
$sub_menu = "800310";
include_once('./_common.php');

// 권한 체크 (Lv.7 스태프 이상)
if ($member['mb_level'] < 7) alert('현장 스태프 권한이 없습니다.', G5_URL);

// [SaaS 격리] 로그인한 스태프에게 배정된 부스 찾기
$mapping = sql_fetch(" SELECT fs_id, target_id FROM rain_festival_manager 
                        WHERE mb_id = '{$member['mb_id']}' AND role_type = 'BOOTH_STAFF' ");

$bt_id = isset($mapping['target_id']) ? (int)$mapping['target_id'] : 0;

if (!$bt_id && $is_admin != 'super') {
    die("<div style='padding:50px; text-align:center; color:#fff; background:#1a1a1a; height:100vh;'><h2>배정된 부스가 없습니다.</h2><p>관리자에게 부스 배정을 요청하세요.</p></div>");
}

// 부스 정보 로드 (최고관리자는 테스트용으로 최근 부스 1개 로드)
if ($is_admin == 'super' && !$bt_id) {
    $booth = sql_fetch(" SELECT * FROM rain_booth_info ORDER BY bt_id DESC LIMIT 1 ");
    $bt_id = $booth['bt_id'] ?? 0;
} else {
    $booth = sql_fetch(" SELECT * FROM rain_booth_info WHERE bt_id = '$bt_id' ");
}

if (!$booth) die("<div style='padding:50px; text-align:center;'>부스 정보를 찾을 수 없습니다.</div>");

$g5['title'] = $booth['bt_name'] . " - 부스 운영 POS";
include_once(G5_PATH.'/head.sub.php'); 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: #1a1a1a; color: #fff; font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; }
    .rain-pos-container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .rain-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
    .rain-status-card { background: #2d2d2d; border-radius: 25px; padding: 40px 20px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
    .rain-count-title { font-size: 20px; color: #aaa; }
    .rain-count { font-size: 90px; font-weight: 900; color: #ffeb3b; margin: 10px 0; font-family: 'Oswald', sans-serif; }
    
    .rain-btn-complete { width: 100%; height: 120px; background: #68d0a7; border: none; border-radius: 20px; font-size: 32px; font-weight: bold; color: #111; cursor: pointer; box-shadow: 0 8px 0 #4eab86; transition: 0.1s; margin-top: 20px; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .rain-btn-complete:active { transform: translateY(8px); box-shadow: none; background: #5bc097; }
    .rain-btn-complete span { font-size: 16px; font-weight: normal; margin-top: 5px; opacity: 0.8; }

    .rain-status-select { width: 100%; height: 60px; margin-top: 25px; background: #333; color: #fff; border: 1px solid #444; border-radius: 10px; font-size: 18px; padding: 0 15px; font-weight:bold; }
    .reward-badge { display:inline-block; padding:5px 10px; background:#444; border-radius:20px; font-size:12px; margin-bottom:15px; color:#ddd; }
</style>

<div class="rain-pos-container">
    <div class="rain-header">
        <div>
            <span style="color:#ffeb3b; font-weight:bold;">[부스 스태프]</span>
            <h1 style="margin:5px 0; font-size:22px;"><?php echo get_text($booth['bt_name']); ?></h1>
        </div>
        <a href="<?php echo G5_BBS_URL; ?>/logout.php" style="color:#aaa; font-size:14px; text-decoration:none;"><i class="fa fa-sign-out"></i> 로그아웃</a>
    </div>

    <div class="rain-status-card">
        <?php if($booth['bt_reward_type'] != 'none') { ?>
            <div class="reward-badge">
                <i class="fa fa-gift"></i> 보상: <?php echo ($booth['bt_reward_type']=='point') ? '포인트 지급 ('.$booth['bt_reward_amount'].'P)' : '스탬프 지급'; ?>
            </div>
        <?php } ?>

        <div class="rain-count-title">오늘 체험 완료 인원</div>
        <div class="rain-count" id="rain_visitor_count"><?php echo number_format($booth['bt_today_visitors']); ?></div>
        
        <button type="button" class="rain-btn-complete" onclick="rainAddVisitor()">
            <i class="fa fa-check-circle"></i> 체험 완료 처리 (+1명)
            <span>터치 시 즉시 인원이 누적됩니다.</span>
        </button>

        <select class="rain-status-select" onchange="rainUpdateStatus(this.value)">
            <option value="운영" <?php echo $booth['bt_status']=='운영'?'selected':''; ?>>🟢 현재 운영 중</option>
            <option value="점검" <?php echo $booth['bt_status']=='점검'?'selected':''; ?>>🟡 점검 중 (대기 바람)</option>
            <option value="마감" <?php echo $booth['bt_status']=='마감'?'selected':''; ?>>🔴 금일 접수 마감</option>
        </select>
    </div>
</div>

<script src="<?php echo G5_JS_URL; ?>/jquery-1.12.4.min.js"></script>
<script>
// 체험 인원 +1 증가 통신
function rainAddVisitor() {
    $.post('./rain_booth_staff_update.php', {
        action: 'add_visitor',
        bt_id: '<?php echo $bt_id; ?>'
    }, function(res) {
        if(res.success) {
            $('#rain_visitor_count').text(res.new_count);
            // 햅틱 진동 피드백 (모바일)
            if (window.navigator.vibrate) window.navigator.vibrate([100, 50, 100]);
        } else {
            alert(res.error);
        }
    }, 'json');
}

// 부스 상태 변경 통신
function rainUpdateStatus(status) {
    $.post('./rain_booth_staff_update.php', {
        action: 'update_status',
        bt_id: '<?php echo $bt_id; ?>',
        status: status
    }, function(res) {
        if(!res.success) alert(res.error);
    }, 'json');
}
</script>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>