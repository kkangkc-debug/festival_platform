<?php
include_once('./common.php');

$fs_id = isset($_GET['fs_id']) ? (int)$_GET['fs_id'] : 0;
if (!$fs_id) die("올바른 행사 번호를 입력해주세요.");

// 1. 행사 기본 정보 가져오기 (SaaS 격리)
$fs = sql_fetch(" SELECT * FROM rain_festival WHERE fs_id = '$fs_id' ");
if (!$fs['fs_id']) die("존재하지 않거나 종료된 행사입니다.");

$g5['title'] = get_text($fs['fs_name']) . ' - 맛집 주문하기';
include_once(G5_PATH.'/head.sub.php');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: #f0f2f5; font-family: 'Pretendard', sans-serif; margin:0; padding:0; color: #333; }
    .shop-container { max-width: 500px; margin: 0 auto; background: #f8f9fa; min-height: 100vh; }

    /* 헤더 영역 */
    .shop-header {
        background: linear-gradient(135deg, #ff3061, #ff5e62);
        color: #fff;
        padding: 20px;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 10px rgba(255,48,97,0.3);
    }
    .shop-header h1 {
        font-size: 20px;
        margin: 0;
        font-weight: 900;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .shop-header .back-btn {
        color: #fff;
        text-decoration: none;
        font-size: 18px;
    }

    /* 필터 영역 */
    .filter-bar {
        background: #fff;
        padding: 15px 20px;
        display: flex;
        gap: 10px;
        overflow-x: auto;
        border-bottom: 1px solid #eee;
    }
    .filter-btn {
        padding: 8px 16px;
        border: 1px solid #ddd;
        border-radius: 20px;
        background: #fff;
        font-size: 13px;
        white-space: nowrap;
        cursor: pointer;
        transition: 0.2s;
    }
    .filter-btn.active {
        background: #ff3061;
        color: #fff;
        border-color: #ff3061;
    }

    /* 상점 카드 */
    .shop-list { padding: 15px; }
    .shop-card {
        background: #fff;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: 0.2s;
    }
    .shop-card:active { transform: scale(0.98); }

    .shop-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    .shop-name {
        font-size: 18px;
        font-weight: 800;
        color: #333;
        margin: 0;
    }
    .shop-type {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-block;
    }
    .type-food { background: #fff3f0; color: #ff3061; }
    .type-market { background: #f0fff4; color: #2CC185; }
    .type-shop { background: #f0f7ff; color: #3f51b5; }

    .shop-status {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
    }
    .status-open { background: #e8f5e9; color: #2CC185; }
    .status-prep { background: #fff8e1; color: #ffa700; }
    .status-closed { background: #ffebee; color: #f44336; }

    .shop-location {
        font-size: 13px;
        color: #666;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .shop-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }
    .shop-manager {
        font-size: 12px;
        color: #888;
    }
    .order-btn {
        background: linear-gradient(135deg, #ff3061, #ff5e62);
        color: #fff;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    /* 빈 상태 */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .empty-state i {
        font-size: 60px;
        margin-bottom: 20px;
        opacity: 0.3;
    }
</style>

<div class="shop-container">
    <div class="shop-header">
        <h1>
            <a href="./rain_festival.php?fs_id=<?php echo $fs_id; ?>" class="back-btn">
                <i class="fa fa-arrow-left"></i>
            </a>
            <span>🍴 맛집 주문하기</span>
        </h1>
    </div>

    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterShops('all')">전체</button>
        <button class="filter-btn" onclick="filterShops('푸드트럭')">푸드트럭</button>
        <button class="filter-btn" onclick="filterShops('플리마켓')">플리마켓</button>
        <button class="filter-btn" onclick="filterShops('일반상점')">일반상점</button>
    </div>

    <div class="shop-list" id="shopList">
        <?php
        // 2. 운영 중인 상점 목록 조회 (SaaS 격리)
        $sql = " SELECT * FROM rain_restaurant_info
                 WHERE fs_id = '$fs_id'
                 AND rt_is_show = 1
                 ORDER BY rt_type ASC, rt_name ASC ";
        $result = sql_query($sql);
        $shop_count = sql_num_rows($result);

        if ($shop_count > 0) {
            while ($shop = sql_fetch_array($result)) {
                // 상태별 스타일
                if ($shop['rt_status'] == '영업중') {
                    $status_class = 'status-open';
                    $status_icon = '●';
                } elseif ($shop['rt_status'] == '준비중') {
                    $status_class = 'status-prep';
                    $status_icon = '●';
                } else {
                    $status_class = 'status-closed';
                    $status_icon = '●';
                }

                // 구분별 스타일
                if ($shop['rt_type'] == '푸드트럭') {
                    $type_class = 'type-food';
                    $type_icon = '🚚';
                } elseif ($shop['rt_type'] == '플리마켓') {
                    $type_class = 'type-market';
                    $type_icon = '🎨';
                } else {
                    $type_class = 'type-shop';
                    $type_icon = '🏪';
                }

                // 주문 가능 여부
                $orderable = ($shop['rt_status'] == '영업중');
        ?>
        <div class="shop-card" data-type="<?php echo $shop['rt_type']; ?>">
            <div class="shop-card-header">
                <div>
                    <h2 class="shop-name"><?php echo get_text($shop['rt_name']); ?></h2>
                    <span class="shop-type <?php echo $type_class; ?>">
                        <?php echo $type_icon; ?> <?php echo $shop['rt_type']; ?>
                    </span>
                </div>
                <span class="shop-status <?php echo $status_class; ?>">
                    <?php echo $status_icon; ?> <?php echo $shop['rt_status']; ?>
                </span>
            </div>

            <div class="shop-location">
                <i class="fa fa-map-marker-alt" style="color:#ff3061;"></i>
                <?php echo get_text($shop['rt_location']); ?>
            </div>

            <div class="shop-footer">
                <div class="shop-manager">
                    <?php if ($shop['rt_manager_name']) { ?>
                        <i class="fa fa-user"></i> <?php echo get_text($shop['rt_manager_name']); ?>
                    <?php } ?>
                </div>
                <?php if ($orderable) { ?>
                <a href="./rain_shop_order.php?fs_id=<?php echo $fs_id; ?>&rt_id=<?php echo $shop['rt_id']; ?>" class="order-btn">
                    <i class="fa fa-shopping-cart"></i>
                    주문하기
                </a>
                <?php } else { ?>
                <span style="color:#999; font-size:13px;">
                    <i class="fa fa-clock"></i> <?php echo $shop['rt_status']; ?>
                </span>
                <?php } ?>
            </div>
        </div>
        <?php
            }
        } else {
        ?>
        <div class="empty-state">
            <i class="fa fa-store-slash"></i>
            <p>등록된 상점이 없습니다</p>
            <p style="font-size:12px; margin-top:10px;">축제 관리자에게 문의해주세요</p>
        </div>
        <?php } ?>
    </div>
</div>

<script>
function filterShops(type) {
    const cards = document.querySelectorAll('.shop-card');
    const buttons = document.querySelectorAll('.filter-btn');

    // 버튼 활성화
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    // 카드 필터링
    cards.forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>
