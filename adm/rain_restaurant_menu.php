<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$rt_id = isset($_GET['rt_id']) ? (int)$_GET['rt_id'] : 0;

if (!$rt_id) alert('잘못된 접근입니다.', './rain_restaurant_list.php');

// 상점 기본 정보 가져오기 & SaaS 권한 체크
$sql = " SELECT * FROM rain_restaurant_info WHERE rt_id = '{$rt_id}' ";
$rt = sql_fetch($sql);
if (!$rt['rt_id']) alert('존재하지 않는 상점입니다.', './rain_restaurant_list.php');

if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
    if ($rt['fs_id'] != MY_FS_ID) alert('접근 권한이 없는 상점입니다.', './rain_restaurant_list.php');
}

$g5['title'] = get_text($rt['rt_name']) . ' - 메뉴 관리';
include_once('./admin.head.php');

// 메뉴 목록 가져오기
$sql = " SELECT count(*) as cnt FROM rain_restaurant_menu WHERE rt_id = '{$rt_id}' ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$sql = " SELECT * FROM rain_restaurant_menu WHERE rt_id = '{$rt_id}' ORDER BY me_order ASC, me_id DESC ";
$result = sql_query($sql);
?>

<style>
/* 음식점 공통 탭 스타일 */
.rain_rt_tabs { border-bottom: 2px solid #ddd; margin-bottom: 20px; display: flex; gap: 5px; }
.rain_rt_tabs a { padding: 10px 30px; background: #f5f5f5; color: #555; text-decoration: none; border-radius: 8px 8px 0 0; font-weight: bold; border: 1px solid #ddd; border-bottom: none; }
.rain_rt_tabs a.active { background: #fff; color: #3f51b5; border-top: 2px solid #3f51b5; padding-bottom: 12px; margin-bottom: -2px; }

/* 메뉴 카드 스타일 */
.menu_card { display: flex; align-items: center; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.menu_img { width: 80px; height: 80px; background: #eee; border-radius: 8px; margin-right: 20px; object-fit: cover; }
.menu_info { flex-grow: 1; }
.menu_name { font-size: 1.2em; font-weight: bold; margin-bottom: 5px; }
.menu_price { color: #ff3061; font-weight: bold; font-size: 1.1em; }
.menu_desc { color: #888; font-size: 0.9em; margin-top: 5px; }
.menu_controls { display: flex; gap: 10px; align-items: center; }
</style>

<div class="rain_rt_tabs">
    <a href="./rain_restaurant_form.php?w=u&rt_id=<?php echo $rt_id; ?>">기본 정보</a>
    <a href="./rain_restaurant_menu.php?rt_id=<?php echo $rt_id; ?>" class="active">메뉴 관리</a>
    <a href="./rain_restaurant_order.php?rt_id=<?php echo $rt_id; ?>">주문 내역</a>
    <a href="./rain_restaurant_stat.php?rt_id=<?php echo $rt_id; ?>">통계</a>
</div>

<div class="local_ov01 local_ov" style="display:flex; justify-content:space-between; align-items:center;">
    <span class="btn_ov01"><span class="ov_txt">등록된 메뉴 총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
    <div>
        <a href="./rain_restaurant_list.php" class="btn btn_02">목록으로</a>
        <a href="./rain_restaurant_menu_form.php?rt_id=<?php echo $rt_id; ?>" class="btn btn_01">+ 메뉴 추가</a>
    </div>
</div>

<div class="menu_list_wrap">
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        // 이미지 경로 처리 (추후 파일 업로드 구현 시 연동)
        $img_src = $row['me_img'] ? G5_DATA_URL.'/menu/'.$row['me_img'] : G5_ADMIN_URL.'/img/no_image.gif';
    ?>
    <div class="menu_card">
        <div style="cursor:grab; font-size:20px; color:#ccc; margin-right:15px;" title="순서 변경">↕</div>
        <img src="<?php echo $img_src; ?>" alt="메뉴 이미지" class="menu_img">
        <div class="menu_info">
            <div class="menu_name"><?php echo get_text($row['me_name']); ?></div>
            <div class="menu_price"><?php echo number_format($row['me_price']); ?>원</div>
            <div class="menu_desc"><?php echo get_text($row['me_desc']); ?></div>
        </div>
        <div class="menu_controls">
            <select name="me_status" class="frm_input" style="height:35px;" onchange="updateMenuStatus(<?php echo $row['me_id']; ?>, this.value)">
                <option value="판매중" <?php echo $row['me_status']=='판매중'?'selected':''; ?>>판매중</option>
                <option value="품절" <?php echo $row['me_status']=='품절'?'selected':''; ?>>품절</option>
                <option value="숨김" <?php echo $row['me_status']=='숨김'?'selected':''; ?>>숨김</option>
            </select>
            <a href="./rain_restaurant_menu_form.php?w=u&rt_id=<?php echo $rt_id; ?>&me_id=<?php echo $row['me_id']; ?>" class="btn btn_03" style="height:35px; line-height:35px;">수정</a>
            <a href="#" onclick="deleteMenu(<?php echo $row['me_id']; ?>); return false;" class="btn btn_02" style="height:35px; line-height:35px;">삭제</a>
        </div>
    </div>
    <?php } if ($i == 0) { ?>
        <div style="padding:50px 0; text-align:center; background:#fff; border:1px solid #ddd; border-radius:8px; color:#888;">
            등록된 메뉴가 없습니다. 우측 상단의 '+ 메뉴 추가' 버튼을 눌러 메뉴를 등록해 주세요.
        </div>
    <?php } ?>
</div>

<script>
function updateMenuStatus(me_id, status) {
    // 추후 AJAX 통신으로 상태 즉시 변경 구현 (PDF 요구사항 반영)
    console.log(me_id + " 상태를 " + status + "로 변경 요청");
}

function deleteMenu(me_id) {
    if(confirm("정말 이 메뉴를 삭제하시겠습니까? (기존 주문 내역에는 영향을 주지 않습니다)")) {
        // 추후 삭제 처리 로직
        alert('삭제 기능 준비중입니다.');
    }
}
</script>

<?php include_once('./admin.tail.php'); ?>