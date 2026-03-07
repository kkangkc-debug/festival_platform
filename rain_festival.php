<?php
include_once('./common.php'); // 루트의 common.php 호출

$fs_id = isset($_GET['fs_id']) ? (int)$_GET['fs_id'] : 0;
if (!$fs_id) die("올바른 행사 번호를 입력해주세요.");

// 1. 행사 기본 정보 가져오기 (SaaS 격리)
$fs = sql_fetch(" SELECT * FROM rain_festival WHERE fs_id = '$fs_id' ");
if (!$fs['fs_id']) die("존재하지 않거나 종료된 행사입니다.");

// 2. 실시간 주차 현황 집계 (전체 주차장 합산)
$park = sql_fetch(" SELECT 
                    SUM(pi_capa_general + pi_capa_pregnant + pi_capa_compact + pi_capa_eco + pi_capa_large) as total_capa,
                    SUM(pi_current_parked) as total_parked
                    FROM rain_park_info WHERE fs_id = '$fs_id' ");
$park_rate = ($park['total_capa'] > 0) ? round(($park['total_parked'] / $park['total_capa']) * 100) : 0;

// 3. 운영 중인 콘텐츠 수 확인
$rt_cnt = sql_fetch(" SELECT count(*) as cnt FROM rain_restaurant_info WHERE fs_id = '$fs_id' AND rt_is_show = 1 ")['cnt'];
$bt_cnt = sql_fetch(" SELECT count(*) as cnt FROM rain_booth_info WHERE fs_id = '$fs_id' AND bt_is_show = 1 ")['cnt'];

$g5['title'] = get_text($fs['fs_name']);
include_once(G5_PATH.'/head.sub.php');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: #f0f2f5; font-family: 'Pretendard', sans-serif; margin:0; padding:0; color: #333; }
    .festival-container { max-width: 500px; margin: 0 auto; background: #fff; min-height: 100vh; }
    
    /* 상단 비주얼 영역 */
    .hero-banner { background: linear-gradient(135deg, #3f51b5, #2196f3); color: #fff; padding: 40px 20px; text-align: center; }
    .hero-banner h1 { font-size: 26px; margin: 10px 0; font-weight: 900; }
    .hero-date { font-size: 14px; opacity: 0.9; background: rgba(0,0,0,0.1); display: inline-block; padding: 4px 12px; border-radius: 20px; }

    /* 실시간 주차 카드 */
    .status-card { margin: -20px 20px 20px; background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .parking-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .parking-bar-bg { background: #eee; height: 10px; border-radius: 5px; overflow: hidden; }
    .parking-bar-fill { height: 100%; transition: 0.5s; border-radius: 5px; }
    
    /* 메인 메뉴 그리드 */
    .menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 20px; }
    .menu-item { background: #fff; border: 1px solid #eee; border-radius: 15px; padding: 25px 15px; text-align: center; text-decoration: none; color: #333; transition: 0.2s; }
    .menu-item:active { transform: scale(0.95); background: #f9f9f9; }
    .menu-item i { font-size: 30px; margin-bottom: 12px; display: block; }
    .item-rt { color: #ff3061; } /* 음식점 */
    .item-bt { color: #2CC185; } /* 체험부스 */
    .item-cp { color: #ffa700; } /* 쿠폰 */
    .item-st { color: #3f51b5; } /* 주차장 상세 */

    .count-badge { font-size: 11px; background: #eee; padding: 2px 6px; border-radius: 5px; color: #666; margin-top: 5px; display: inline-block; }
</style>

<div class="festival-container">
    <div class="hero-banner">
        <div class="hero-date"><i class="fa fa-calendar-check"></i> <?php echo $fs['fs_start_date']; ?> ~ <?php echo $fs['fs_end_date']; ?></div>
        <h1><?php echo get_text($fs['fs_name']); ?></h1>
        <p style="opacity: 0.8;">즐거운 축제의 모든 정보를 한눈에!</p>
    </div>

    <div class="status-card">
        <div class="parking-title">
            <span style="font-weight:bold;"><i class="fa fa-car" style="color:#3f51b5;"></i> 실시간 주차 현황</span>
            <span style="font-size:13px; color:#888;"><?php echo $park['total_parked']; ?> / <?php echo $park['total_capa']; ?>면</span>
        </div>
        <div class="parking-bar-bg">
            <?php 
                $bar_color = ($park_rate >= 90) ? '#ff3061' : (($park_rate >= 70) ? '#ffa700' : '#68d0a7');
            ?>
            <div class="parking-bar-fill" style="width: <?php echo $park_rate; ?>%; background: <?php echo $bar_color; ?>;"></div>
        </div>
        <div style="text-align:right; font-size:12px; color:<?php echo $bar_color; ?>; margin-top:5px; font-weight:bold;">
            현재 주차율 <?php echo $park_rate; ?>% (<?php echo ($park_rate >= 90) ? '만차임박' : (($park_rate >= 70) ? '혼잡' : '여유'); ?>)
        </div>
    </div>

    <div class="menu-grid">
        <a href="./rain_shop_list.php?fs_id=<?php echo $fs_id; ?>" class="menu-item">
            <i class="fa fa-utensils item-rt"></i>
            <strong>맛집 주문하기</strong>
            <span class="count-badge"><?php echo $rt_cnt; ?>개 운영중</span>
        </a>

        <a href="./rain_booth_status.php?fs_id=<?php echo $fs_id; ?>" class="menu-item">
            <i class="fa fa-star item-bt"></i>
            <strong>체험부스 현황</strong>
            <span class="count-badge"><?php echo $bt_cnt; ?>개 운영중</span>
        </a>

        <a href="./rain_coupon_page.php?fs_id=<?php echo $fs_id; ?>" class="menu-item">
            <i class="fa fa-ticket-alt item-cp"></i>
            <strong>내 쿠폰함</strong>
            <span class="count-badge">이벤트 진행중</span>
        </a>

        <a href="./rain_parking_info.php?fs_id=<?php echo $fs_id; ?>" class="menu-item">
            <i class="fa fa-map-marked-alt item-st"></i>
            <strong>주차장 안내</strong>
            <span class="count-badge">위치 및 경로</span>
        </a>
    </div>

    <div style="padding: 20px; text-align: center; border-top: 1px solid #eee; margin-top: 20px;">
        <img src="<?php echo G5_URL; ?>/img/logo.png" style="height: 25px; opacity: 0.5; filter: grayscale(1);">
        <p style="font-size: 11px; color: #aaa; margin-top: 10px;">본 서비스는 Rain System SaaS로 운영됩니다.</p>
    </div>
</div>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>