<?php
include_once('./common.php'); // 루트의 common.php 호출

$rt_id = isset($_GET['rt_id']) ? (int)$_GET['rt_id'] : 0;
if (!$rt_id) die("잘못된 접근입니다. 부스 QR 코드를 확인해주세요.");

// 상점 정보 및 현재 대기 상황 가져오기 (기획서 ADM-FOOD-001 기준)
$rt = sql_fetch(" SELECT * FROM rain_restaurant_info WHERE rt_id = '$rt_id' ");
if (!$rt['rt_id']) die("존재하지 않는 상점입니다.");

// 예상 대기시간 계산 (기획서 8-3 로직: 미처리 주문수 기반)
$wait_cnt = sql_fetch(" SELECT count(*) as cnt FROM rain_restaurant_order WHERE rt_id = '$rt_id' AND od_status IN ('대기중', '준비중') ")['cnt'];
$est_time = $wait_cnt * 5; // 예시: 건당 5분

$g5['title'] = $rt['rt_name'];
include_once(G5_PATH.'/head.sub.php');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: #f8f9fa; font-family: 'Pretendard', sans-serif; margin:0; padding:0; }
    .order-container { max-width: 500px; margin: 0 auto; background: #fff; min-height: 100vh; padding-bottom: 100px; }
    .booth-header { padding: 30px 20px; background: #3f51b5; color: #fff; }
    .booth-status { display: flex; gap: 10px; margin-top: 15px; }
    .status-badge { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 13px; }
    
    .menu-section { padding: 20px; }
    .menu-item { display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #eee; }
    .menu-img { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; background: #f0f0f0; }
    .menu-info { flex: 1; }
    .menu-name { font-weight: bold; font-size: 16px; margin-bottom: 5px; }
    .menu-price { color: #ff3061; font-weight: bold; }
    .qty-ctrl { display: flex; align-items: center; gap: 10px; }
    .btn-qty { border: 1px solid #ddd; background: #fff; width: 25px; height: 25px; border-radius: 5px; }

    .bottom-bar { position: fixed; bottom: 0; max-width: 500px; width: 100%; background: #fff; padding: 20px; border-top: 1px solid #ddd; box-sizing: border-box; }
    .btn-order { background: #3f51b5; color: #fff; width: 100%; border: none; padding: 15px; border-radius: 10px; font-size: 18px; font-weight: bold; cursor: pointer; }
</style>

<div class="order-container">
    <div class="booth-header">
        <div style="font-size: 14px; opacity: 0.8;"><?php echo $rt['rt_location']; ?></div>
        <h1 style="margin: 5px 0; font-size: 24px;"><?php echo get_text($rt['rt_name']); ?></h1>
        <div class="booth-status">
            <span class="status-badge"><i class="fa fa-clock"></i> 대기 <?php echo $est_time; ?>분</span>
            <span class="status-badge"><i class="fa fa-utensils"></i> <?php echo $rt['rt_type']; ?></span>
        </div>
    </div>

    <div class="menu-section">
        <h3 style="margin-bottom: 20px;">메뉴 선택</h3>
        <?php
        $m_res = sql_query(" SELECT * FROM rain_restaurant_menu WHERE rt_id = '$rt_id' AND me_status = '판매중' ORDER BY me_order ASC ");
        for($i=0; $m=sql_fetch_array($m_res); $i++) {
            $m_img = $m['me_img'] ? G5_DATA_URL.'/menu/'.$m['me_img'] : G5_URL.'/img/no_img.png';
        ?>
        <div class="menu-item">
            <img src="<?php echo $m_img; ?>" class="menu-img">
            <div class="menu-info">
                <div class="menu-name"><?php echo get_text($m['me_name']); ?></div>
                <div style="font-size: 13px; color: #888; margin-bottom: 8px;"><?php echo get_text($m['me_desc']); ?></div>
                <div class="menu-price"><?php echo number_format($m['me_price']); ?>원</div>
            </div>
            <div class="qty-ctrl">
                <input type="number" name="qty[<?php echo $m['me_id']; ?>]" value="0" min="0" class="frm_input" style="width:50px; text-align:center;">
            </div>
        </div>
        <?php } ?>
    </div>

    <form action="./rain_shop_order_update.php" method="post">
        <input type="hidden" name="rt_id" value="<?php echo $rt_id; ?>">
        <div class="bottom-bar">
            <button type="submit" class="btn-order">비회원으로 주문하기</button>
        </div>
    </form>
</div>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>