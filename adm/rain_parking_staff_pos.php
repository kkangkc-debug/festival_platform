<?php
$sub_menu = "800100";
include_once('./_common.php');

// 권한 체크: Lv.7(스태프) 이상만 접근 가능
if ($member['mb_level'] < 7) alert('현장 스태프 권한이 없습니다.', G5_URL);

// =========================================================================
// [권한 분기 및 SaaS 격리 로직 개선]
// =========================================================================
$pi_id_get = isset($_GET['pi_id']) ? (int)$_GET['pi_id'] : 0;
$pi_id = 0;

if ($is_admin == 'super') {
    // 1. 최고관리자: URL로 넘어온 주차장이 있으면 열고, 없으면 가장 최근 주차장
    $pi_id = $pi_id_get ? $pi_id_get : (int)sql_fetch(" SELECT pi_id FROM rain_park_info ORDER BY pi_id DESC LIMIT 1 ")['pi_id'];

} else if ($member['mb_level'] >= 8 && defined('MY_FS_ID')) {
    // 2. 행사관리자(Lv.8 이상): 스태프 '배정(Mapping)' 절차 없이도, 자신의 행사에 속한 주차장이면 바로 접근 허용!
    $chk = sql_fetch(" SELECT pi_id FROM rain_park_info WHERE pi_id = '{$pi_id_get}' AND fs_id = '".MY_FS_ID."' ");
    if (isset($chk['pi_id']) && $chk['pi_id']) {
        $pi_id = $chk['pi_id'];
    }

} else {
    // 3. 일반 스태프(Lv.7): 관리자가 명시적으로 '배정'해준 주차장만 열람 가능
    $mapping = sql_fetch(" SELECT target_id FROM rain_festival_manager 
                           WHERE mb_id = '{$member['mb_id']}' AND role_type = 'PARKING_STAFF' ");
    $pi_id = isset($mapping['target_id']) ? (int)$mapping['target_id'] : 0;
}

// 최종적으로 접근 가능한 주차장 ID가 없는 경우 차단
if (!$pi_id) {
    die("<div style='padding:50px; text-align:center; color:#fff; background:#1a1a1a; height:100vh;'>
            <h2>접근 권한이 없거나 배정된 주차장이 없습니다.</h2>
            <p>1. 일반 스태프인 경우: 총괄 관리자에게 주차장 배정을 요청하세요.<br>
               2. 총괄 관리자인 경우: 올바른 주차장 링크를 클릭했는지 확인하세요.</p>
            <a href='".G5_URL."' style='color:#ff3061;'>메인으로 돌아가기</a>
         </div>");
}

// 주차장 실시간 정보 로드
$pi = sql_fetch(" SELECT * FROM rain_park_info WHERE pi_id = '$pi_id' ");
if (!$pi) alert('주차장 정보를 불러올 수 없습니다.');

// 용량 및 현재 주차 대수 계산 로직
$capa_gen = (int)$pi['pi_capa_general'];
$capa_bar = (int)$pi['pi_capa_pregnant'] + (int)$pi['pi_capa_compact'] + (int)$pi['pi_capa_eco'];
$capa_lar = (int)$pi['pi_capa_large'];
$total_capa = $capa_gen + $capa_bar + $capa_lar;

// (DB에 유형별 컬럼이 없을 경우를 대비한 안전 장치)
$parked_gen = isset($pi['pi_parked_general']) ? (int)$pi['pi_parked_general'] : (int)$pi['pi_current_parked'];
$parked_bar = isset($pi['pi_parked_barrier']) ? (int)$pi['pi_parked_barrier'] : 0;
$parked_lar = isset($pi['pi_parked_large']) ? (int)$pi['pi_parked_large'] : 0;
$total_parked = isset($pi['pi_parked_general']) ? ($parked_gen + $parked_bar + $parked_lar) : (int)$pi['pi_current_parked'];

$g5['title'] = $pi['pi_name'] . " - 실시간 관리";
include_once(G5_PATH.'/head.sub.php'); 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: #1a1a1a; color: #fff; font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; }
    .rain-pos-container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .rain-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
    
    /* 총 현황 박스 */
    .total-box { background: #2d2d2d; border-radius: 15px; padding: 20px; text-align: center; margin-bottom: 20px; border: 1px solid #444; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
    .total-text { font-size: 16px; color: #aaa; margin-bottom: 5px; }
    .total-number { font-size: 50px; font-weight: 900; color: #68d0a7; font-family: 'Oswald', sans-serif; }
    
    /* 유형별 카드 */
    .rain-type-card { background: #222; border-radius: 15px; padding: 20px; margin-bottom: 15px; border: 1px solid #333; }
    .type-header { display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: bold; margin-bottom: 15px; }
    .type-capa { font-size: 14px; color: #aaa; font-weight: normal; }
    .type-controls { display: flex; justify-content: space-between; align-items: center; background: #1a1a1a; border-radius: 10px; padding: 10px; }
    
    /* 컨트롤 버튼 */
    .btn-ctrl { width: 70px; height: 70px; border: none; border-radius: 15px; font-size: 30px; color: #fff; cursor: pointer; transition: 0.1s; display: flex; align-items: center; justify-content: center; outline:none; }
    .btn-minus { background: #3f51b5; box-shadow: 0 5px 0 #2a3a8c; }
    .btn-plus { background: #ff3061; box-shadow: 0 5px 0 #c71541; }
    .btn-ctrl:active { transform: translateY(5px); box-shadow: none; }
    .type-count { font-size: 40px; font-weight: 900; color: #ffeb3b; font-family: 'Oswald', sans-serif; width: 100px; text-align: center; }
    
    .rain-status-select { width: 100%; height: 60px; margin-top: 10px; background: #333; color: #fff; border: 1px solid #444; border-radius: 10px; font-size: 18px; padding: 0 15px; font-weight:bold; }
</style>

<div class="rain-pos-container">
    <div class="rain-header">
        <div>
            <span style="color:#ff3061; font-weight:bold;">[현장 스태프]</span>
            <h1 style="margin:5px 0; font-size:22px;"><?php echo get_text($pi['pi_name']); ?></h1>
        </div>
        <a href="<?php echo G5_BBS_URL; ?>/logout.php" style="color:#aaa; font-size:14px; text-decoration:none;"><i class="fa fa-sign-out"></i> 로그아웃</a>
    </div>

    <div class="total-box">
        <div class="total-text">총 주차 현황 (전체 <?php echo $total_capa; ?>면)</div>
        <div class="total-number" id="total_current"><?php echo $total_parked; ?></div>
    </div>

    <?php if($pi['pi_type_general']) { ?>
    <div class="rain-type-card">
        <div class="type-header">
            <span style="color:#fff;"><i class="fa fa-car"></i> 일반 주차</span>
            <span class="type-capa">총 <?php echo $capa_gen; ?>면</span>
        </div>
        <div class="type-controls">
            <button type="button" class="btn-ctrl btn-minus" onclick="rainUpdateType('general', -1)"><i class="fa fa-minus"></i></button>
            <div class="type-count" id="cnt_general"><?php echo $parked_gen; ?></div>
            <button type="button" class="btn-ctrl btn-plus" onclick="rainUpdateType('general', 1)"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <?php } ?>

    <?php if($pi['pi_type_barrier']) { ?>
    <div class="rain-type-card">
        <div class="type-header">
            <span style="color:#2CC185;"><i class="fa fa-wheelchair"></i> 베리어프리</span>
            <span class="type-capa">총 <?php echo $capa_bar; ?>면</span>
        </div>
        <div class="type-controls">
            <button type="button" class="btn-ctrl btn-minus" onclick="rainUpdateType('barrier', -1)"><i class="fa fa-minus"></i></button>
            <div class="type-count" id="cnt_barrier"><?php echo $parked_bar; ?></div>
            <button type="button" class="btn-ctrl btn-plus" onclick="rainUpdateType('barrier', 1)"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <?php } ?>

    <?php if($pi['pi_type_large']) { ?>
    <div class="rain-type-card">
        <div class="type-header">
            <span style="color:#ffa700;"><i class="fa fa-bus"></i> 대형 차량</span>
            <span class="type-capa">총 <?php echo $capa_lar; ?>면</span>
        </div>
        <div class="type-controls">
            <button type="button" class="btn-ctrl btn-minus" onclick="rainUpdateType('large', -1)"><i class="fa fa-minus"></i></button>
            <div class="type-count" id="cnt_large"><?php echo $parked_lar; ?></div>
            <button type="button" class="btn-ctrl btn-plus" onclick="rainUpdateType('large', 1)"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <?php } ?>

    <select class="rain-status-select" onchange="rainUpdateStatus(this.value)">
        <option value="운영" <?php echo $pi['pi_status']=='운영'?'selected':''; ?>>✅ 현재 운영 원활</option>
        <option value="혼잡" <?php echo $pi['pi_status']=='혼잡'?'selected':''; ?>>⚠️ 주차 혼잡 (주의)</option>
        <option value="만차" <?php echo $pi['pi_status']=='만차'?'selected':''; ?>>🚫 만차 (입차 통제)</option>
    </select>
    
    <p style="text-align:center; color:#555; margin-top:20px; font-size:12px;">Data Protected by Rain System SaaS</p>
</div>

<script src="<?php echo G5_JS_URL; ?>/jquery-1.12.4.min.js"></script>
<script>
// [수정] 유형별 카운트 업데이트 로직
function rainUpdateType(type, val) {
    $.post('./rain_parking_staff_update.php', {
        action: 'rain_count_type', // 액션명 변경
        pi_id: '<?php echo $pi_id; ?>',
        type: type,
        val: val
    }, function(res) {
        if(res.success) {
            // 해당 유형의 카운트 갱신
            $('#cnt_' + type).text(res.type_count);
            // 최상단 총합 카운트 갱신
            $('#total_current').text(res.total_count);
            // 모바일 햅틱 진동 피드백
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