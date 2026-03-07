<?php
$sub_menu = "800100";
include_once('./_common.php');

// 권한 체크: Lv.7(스태프) 이상만 접근 가능
if ($member['mb_level'] < 7) alert('현장 스태프 권한이 없습니다.', G5_URL);

// [SaaS 격리] 이 스태프가 배정받은 특정 주차장 정보 가져오기
$mapping = sql_fetch(" SELECT fs_id, target_id FROM rain_festival_manager 
                        WHERE mb_id = '{$member['mb_id']}' AND role_type = 'PARKING_STAFF' ");

// 변수 초기화
$pi_id = isset($mapping['target_id']) ? (int)$mapping['target_id'] : 0;
$fs_id = isset($mapping['fs_id']) ? (int)$mapping['fs_id'] : 0;

// 최고관리자가 아니고 배정된 주차장도 없는 경우
if (!$pi_id && $is_admin != 'super') {
    die("<div style='padding:50px; text-align:center;'><h2>배정된 주차장이 없습니다.</h2><p>관리자 페이지에서 '행사 총괄관리자 지정' 메뉴를 통해<br>해당 계정에 [주차스태프] 권한과 [주차장]을 연결해 주세요.</p><a href='".G5_URL."'>메인으로 돌아가기</a></div>");
}

// 최고관리자(admin)가 테스트용으로 들어왔을 때를 위한 예외 처리
if ($is_admin == 'super' && !$pi_id) {
    // 테스트를 위해 가장 최근 주차장 하나를 임의로 가져옵니다.
    $pi = sql_fetch(" SELECT * FROM rain_park_info ORDER BY pi_id DESC LIMIT 1 ");
    if (!$pi) alert('등록된 주차장이 하나도 없습니다. 주차장을 먼저 등록해 주세요.');
    $pi_id = $pi['pi_id'];
} else {
    // 주차장 실시간 정보 로드
    $pi = sql_fetch(" SELECT * FROM rain_park_info WHERE pi_id = '$pi_id' ");
}

if (!$pi) alert('주차장 정보를 불러올 수 없습니다.');

$total_capa = (int)$pi['pi_capa_general'] + (int)$pi['pi_capa_pregnant'] + (int)$pi['pi_capa_compact'] + (int)$pi['pi_capa_eco'] + (int)$pi['pi_capa_large'];

$g5['title'] = $pi['pi_name'] . " - 실시간 관리";

// 경로 수정: admin.head.sub.php 대신 head.sub.php 또는 admin.head.php 사용
include_once(G5_PATH.'/head.sub.php'); 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: #1a1a1a; color: #fff; font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; }
    .rain-pos-container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .rain-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
    .rain-status-card { background: #2d2d2d; border-radius: 25px; padding: 40px 20px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
    .rain-count { font-size: 100px; font-weight: 900; color: #68d0a7; margin: 20px 0; font-family: 'Oswald', sans-serif; }
    .rain-capa-info { font-size: 20px; color: #aaa; }
    .rain-btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
    .rain-btn { height: 150px; border: none; border-radius: 20px; font-size: 50px; color: #fff; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; outline:none; }
    .rain-btn-plus { background: #ff3061; box-shadow: 0 8px 0 #c71541; }
    .rain-btn-minus { background: #3f51b5; box-shadow: 0 8px 0 #2a3a8c; }
    .rain-btn:active { transform: translateY(4px); box-shadow: 0 4px 0 #c71541; }
    .rain-status-select { width: 100%; height: 60px; margin-top: 25px; background: #333; color: #fff; border: 1px solid #444; border-radius: 10px; font-size: 18px; padding: 0 15px; }
</style>

<div class="rain-pos-container">
    <div class="rain-header">
        <div>
            <span style="color:#ff3061; font-weight:bold;">[현장 스태프]</span>
            <h1 style="margin:5px 0; font-size:22px;"><?php echo get_text($pi['pi_name']); ?></h1>
        </div>
        <a href="<?php echo G5_BBS_URL; ?>/logout.php" style="color:#aaa; font-size:14px; text-decoration:none;"><i class="fa fa-sign-out"></i> 로그아웃</a>
    </div>

    <div class="rain-status-card">
        <div class="rain-capa-info">현재 주차 차량 <span style="color:#eee;">(총 <?php echo $total_capa; ?>면)</span></div>
        <div class="rain-count" id="rain_current_count"><?php echo (int)$pi['pi_current_parked']; ?></div>
        
        <div class="rain-btn-grid">
            <button type="button" class="rain-btn rain-btn-minus" onclick="rainUpdateCount(-1)"><i class="fa fa-minus"></i></button>
            <button type="button" class="rain-btn rain-btn-plus" onclick="rainUpdateCount(1)"><i class="fa fa-plus"></i></button>
        </div>

        <select class="rain-status-select" onchange="rainUpdateStatus(this.value)">
            <option value="운영" <?php echo $pi['pi_status']=='운영'?'selected':''; ?>>✅ 현재 운영 원활</option>
            <option value="혼잡" <?php echo $pi['pi_status']=='혼잡'?'selected':''; ?>>⚠️ 주차 혼잡 (주의)</option>
            <option value="만차" <?php echo $pi['pi_status']=='만차'?'selected':''; ?>>🚫 만차 (입차 통제)</option>
        </select>
    </div>
    
    <p style="text-align:center; color:#555; margin-top:20px; font-size:12px;">Data Protected by Rain System SaaS</p>
</div>

<script src="<?php echo G5_JS_URL; ?>/jquery-1.12.4.min.js"></script>
<script>
function rainUpdateCount(val) {
    $.post('./rain_parking_staff_update.php', {
        action: 'rain_count',
        pi_id: '<?php echo $pi_id; ?>',
        val: val
    }, function(res) {
        if(res.success) {
            $('#rain_current_count').text(res.new_count);
            if (window.navigator.vibrate) window.navigator.vibrate(50);
        } else {
            alert(res.error);
        }
    }, 'json');
}

function rainUpdateStatus(status) {
    $.post('./rain_parking_staff_update.php', {
        action: 'rain_status',
        pi_id: '<?php echo $pi_id; ?>',
        status: status
    }, function(res) {
        if(!res.success) alert(res.error);
    }, 'json');
}
</script>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>