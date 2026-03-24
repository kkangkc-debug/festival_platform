<?php
include_once('./_common.php');

$g5['title'] = '메뉴 관리';
include_once('./partner_head.php');

$rt_id = $partner_shop['rt_id'];

// 액션 처리
$act = isset($_GET['act']) ? clean_xss_tags($_GET['act']) : '';
$rm_id = isset($_GET['rm_id']) ? (int)$_GET['rm_id'] : 0;

if ($act == 'delete' && $rm_id) {
    // 메뉴 삭제
    $menu = sql_fetch(" SELECT * FROM rain_restaurant_menu WHERE rm_id = '{$rm_id}' AND rt_id = '{$rt_id}' ");
    if ($menu) {
        sql_query(" DELETE FROM rain_restaurant_menu WHERE rm_id = '{$rm_id}' AND rt_id = '{$rt_id}' ");
        alert('메뉴가 삭제되었습니다.', './menu.php');
    } else {
        alert('존재하지 않는 메뉴입니다.', './menu.php');
    }
    exit;
}

// 카테고리 목록 조회aaaaa
$categories = sql_query("
    SELECT DISTINCT rm_category
    FROM rain_restaurant_menu
    WHERE rt_id = '{$rt_id}'
    AND rm_category IS NOT NULL
    AND rm_category != ''
    ORDER BY rm_category
");

// 메뉴 목록 조회 (카테고리별 정렬)
$sql = "
    SELECT *
    FROM rain_restaurant_menu
    WHERE rt_id = '{$rt_id}'
    ORDER BY rm_category ASC, rm_sort_order ASC, rm_id DESC
";
$result = sql_query($sql);

// 카테고리별로 그룹화
$menus_by_category = array();
$all_menus = array();
while ($menu = sql_fetch_array($result)) {
    $category = $menu['rm_category'] ?: '기본';
    if (!isset($menus_by_category[$category])) {
        $menus_by_category[$category] = array();
    }
    $menus_by_category[$category][] = $menu;
    $all_menus[] = $menu;
}
?>

<h2 class="page_title">🍽️ 메뉴 관리</h2>

<!-- 메뉴 통계 -->
<div style="display: flex; gap: 10px; margin-bottom: 15px;">
    <div class="card" style="flex: 1; text-align: center; padding: 12px;">
        <div style="font-size: 20px; font-weight: 700; color: #667eea;">
            <?php echo count($all_menus); ?>
        </div>
        <div style="font-size: 11px; color: #888;">전체 메뉴</div>
    </div>
    <div class="card" style="flex: 1; text-align: center; padding: 12px;">
        <div style="font-size: 20px; font-weight: 700; color: #28a745;">
            <?php echo count(array_filter($all_menus, function($m) { return $m['rm_is_active'] && !$m['rm_sold_out']; })); ?>
        </div>
        <div style="font-size: 11px; color: #888;">판매중</div>
    </div>
    <div class="card" style="flex: 1; text-align: center; padding: 12px;">
        <div style="font-size: 20px; font-weight: 700; color: #dc3545;">
            <?php echo count(array_filter($all_menus, function($m) { return $m['rm_sold_out']; })); ?>
        </div>
        <div style="font-size: 11px; color: #888;">품절</div>
    </div>
</div>

<!-- 메뉴 등록 버튼 -->
<button onclick="showMenuForm()" class="btn btn_primary btn_full" style="margin-bottom: 15px;">
    <i class="fa fa-plus"></i> 새 메뉴 등록
</button>

<!-- 메뉴 등록/수정 폼 (숨김) -->
<div id="menu_form_container" style="display: none; margin-bottom: 15px;">
    <div class="card">
        <div class="card_title" id="form_title">메뉴 등록</div>
        <form id="menu_form" method="post" action="./menu_update.php" enctype="multipart/form-data">
            <input type="hidden" name="act" value="<?php echo $rm_id ? 'update' : 'insert'; ?>">
            <input type="hidden" name="rm_id" value="<?php echo $rm_id; ?>">

            <div class="frm_group">
                <label class="frm_label">메뉴명 *</label>
                <input type="text" name="rm_name" class="frm_input" required placeholder="예: 불고기 버거">
            </div>

            <div class="frm_group">
                <label class="frm_label">카테고리</label>
                <input type="text" name="rm_category" class="frm_input" list="category_list" placeholder="예: 버거, 음료, 사이드">
                <datalist id="category_list">
                    <?php
                    if ($categories) {
                        sql_data_seek($categories, 0);
                        while ($cat = sql_fetch_array($categories)) {
                            echo '<option value="' . get_text($cat['rm_category']) . '">';
                        }
                    }
                    ?>
                </datalist>
            </div>

            <div class="frm_group">
                <label class="frm_label">가격 (원) *</label>
                <input type="number" name="rm_price" class="frm_input" required placeholder="예: 8000">
            </div>

            <div class="frm_group">
                <label class="frm_label">설명</label>
                <textarea name="rm_description" class="frm_input" rows="2" placeholder="메뉴에 대한 간단한 설명"></textarea>
            </div>

            <div class="frm_group">
                <label class="frm_label">메뉴 이미지</label>
                <input type="file" name="rm_image" class="frm_input" accept="image/*">
                <div style="font-size: 11px; color: #888; margin-top: 3px;">권장: 500x500px (JPG, PNG)</div>
            </div>

            <div class="frm_group">
                <label class="frm_label">정렬 순서</label>
                <input type="number" name="rm_sort_order" class="frm_input" value="0">
                <div style="font-size: 11px; color: #888; margin-top: 3px;">낮을수록 먼저 표시</div>
            </div>

            <div class="frm_group">
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="rm_is_active" value="1" checked>
                    <span>활성화 (앱에 표시)</span>
                </label>
            </div>

            <div style="display: flex; gap: 8px; margin-top: 15px;">
                <button type="submit" class="btn btn_primary btn_full">
                    <i class="fa fa-save"></i> 저장
                </button>
                <button type="button" onclick="hideMenuForm()" class="btn btn_outline btn_full">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 메뉴 목록 -->
<?php if (!empty($menus_by_category)) { ?>
    <?php foreach ($menus_by_category as $category => $menus) { ?>
        <div class="card">
            <div class="card_title">
                <i class="fa fa-folder"></i> <?php echo get_text($category); ?>
                <span style="font-size: 12px; color: #888; font-weight: normal;">
                    (<?php echo count($menus); ?>개)
                </span>
            </div>

            <?php foreach ($menus as $menu) {
                $image_url = $menu['rm_image'] ? G5_DATA_URL . '/menu/' . $menu['rm_image'] : '';
            ?>
            <div style="border-bottom: 1px solid #eee; padding: 12px 0;" id="menu_<?php echo $menu['rm_id']; ?>">
                <div style="display: flex; gap: 12px;">
                    <!-- 메뉴 이미지 -->
                    <div style="width: 80px; height: 80px; flex-shrink: 0;">
                        <?php if ($image_url) { ?>
                        <img src="<?php echo $image_url; ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                        <?php } else { ?>
                        <div style="width: 100%; height: 100%; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-cutlery" style="color: #ccc; font-size: 24px;"></i>
                        </div>
                        <?php } ?>
                    </div>

                    <!-- 메뉴 정보 -->
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 5px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 15px; margin-bottom: 3px;">
                                    <?php echo get_text($menu['rm_name']); ?>
                                </div>
                                <?php if ($menu['rm_description']) { ?>
                                <div style="font-size: 12px; color: #888;">
                                    <?php echo get_text($menu['rm_description']); ?>
                                </div>
                                <?php } ?>
                            </div>
                            <div style="font-weight: 700; color: #667eea; font-size: 16px;">
                                <?php echo number_format($menu['rm_price']); ?>원
                            </div>
                        </div>

                        <!-- 상태 배지 -->
                        <div style="margin-bottom: 8px;">
                            <?php if (!$menu['rm_is_active']) { ?>
                                <span class="badge badge_danger">비활성</span>
                            <?php } ?>
                            <?php if ($menu['rm_sold_out']) { ?>
                                <span class="badge badge_warning">품절</span>
                            <?php } ?>
                            <?php if ($menu['rm_is_active'] && !$menu['rm_sold_out']) { ?>
                                <span class="badge badge_success">판매중</span>
                            <?php } ?>
                        </div>

                        <!-- 버튼 -->
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php if ($menu['rm_sold_out']) { ?>
                                <button onclick="toggleSoldOut(<?php echo $menu['rm_id']; ?>, 0)" class="btn btn_success btn_sm">
                                    <i class="fa fa-check"></i> 판매재개
                                </button>
                            <?php } else { ?>
                                <button onclick="toggleSoldOut(<?php echo $menu['rm_id']; ?>, 1)" class="btn btn_warning btn_sm">
                                    <i class="fa fa-ban"></i> 품절표시
                                </button>
                            <?php } ?>

                            <?php if ($menu['rm_is_active']) { ?>
                                <button onclick="toggleActive(<?php echo $menu['rm_id']; ?>, 0)" class="btn btn_outline btn_sm">
                                    <i class="fa fa-eye-slash"></i> 숨김
                                </button>
                            <?php } else { ?>
                                <button onclick="toggleActive(<?php echo $menu['rm_id']; ?>, 1)" class="btn btn_primary btn_sm">
                                    <i class="fa fa-eye"></i> 표시
                                </button>
                            <?php } ?>

                            <button onclick="editMenu(<?php echo $menu['rm_id']; ?>)" class="btn btn_secondary btn_sm">
                                <i class="fa fa-edit"></i> 수정
                            </button>
                            <button onclick="deleteMenu(<?php echo $menu['rm_id']; ?>)" class="btn btn_danger btn_sm">
                                <i class="fa fa-trash"></i> 삭제
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
<?php } else { ?>
    <div class="empty_state">
        <i class="fa fa-cutlery"></i>
        <p>등록된 메뉴가 없습니다</p>
        <button onclick="showMenuForm()" class="btn btn_primary btn_sm" style="margin-top: 10px;">
            첫 메뉴 등록하기
        </button>
    </div>
<?php } ?>

<script>
function showMenuForm() {
    $('#menu_form_container').slideDown();
    $('#form_title').text('메뉴 등록');
    $('input[name="act"]').val('insert');
    $('input[name="rm_id"]').val('0');
    $('#menu_form')[0].reset();
}

function hideMenuForm() {
    $('#menu_form_container').slideUp();
}

function editMenu(rm_id) {
    // 메뉴 수정 폼 표시 (AJAX로 메뉴 데이터 가져오기)
    partnerAjax('./menu_ajax.php', { action: 'get_menu', rm_id: rm_id }, function(res) {
        $('#menu_form_container').slideDown();
        $('#form_title').text('메뉴 수정');
        $('input[name="act"]').val('update');
        $('input[name="rm_id"]').val(res.data.rm_id);
        $('input[name="rm_name"]').val(res.data.rm_name);
        $('input[name="rm_category"]').val(res.data.rm_category);
        $('input[name="rm_price"]').val(res.data.rm_price);
        $('textarea[name="rm_description"]').val(res.data.rm_description);
        $('input[name="rm_sort_order"]').val(res.data.rm_sort_order);
        $('input[name="rm_is_active"]').prop('checked', res.data.rm_is_active == 1);

        // 스크롤 이동
        $('html, body').animate({ scrollTop: $('#menu_form_container').offset().top - 100 }, 300);
    });
}

function deleteMenu(rm_id) {
    partnerConfirm('정말 삭제하시겠습니까?', function() {
        location.href = './menu.php?act=delete&rm_id=' + rm_id;
    });
}

function toggleSoldOut(rm_id, status) {
    partnerAjax('./menu_ajax.php', {
        action: 'toggle_sold_out',
        rm_id: rm_id,
        status: status
    }, function(res) {
        partnerAlert(status == 1 ? '품절로 표시했습니다.' : '판매를 재개했습니다.', 'success');
        setTimeout(function() { partnerReload(); }, 500);
    });
}

function toggleActive(rm_id, status) {
    partnerAjax('./menu_ajax.php', {
        action: 'toggle_active',
        rm_id: rm_id,
        status: status
    }, function(res) {
        partnerAlert(status == 1 ? '활성화했습니다.' : '비활성화했습니다.', 'success');
        setTimeout(function() { partnerReload(); }, 500);
    });
}
</script>

<?php
include_once('./partner_tail.php');
?>
