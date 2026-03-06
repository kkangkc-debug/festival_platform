<?php
$sub_menu = "800920"; // 시스템 관리 하위 메뉴 코드
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

// --- [수정] AJAX 삭제 처리 로직 ---
if (isset($_POST['action']) && $_POST['action'] === 'ajax_delete') {
    // 1. 기존에 쌓여있던 모든 출력물(경고문, 공백 등)을 지워 JSON 파싱 에러를 원천 차단합니다.
    while (ob_get_level()) { ob_end_clean(); }
    
    header('Content-Type: application/json; charset=utf-8');
    
    // 2. 권한 체크
    if ($is_admin !== 'super' && !auth_check_menu($auth, $sub_menu, 'd', true)) {
        echo json_encode(array('error' => '삭제 권한이 없습니다.'));
        exit;
    }

    // 3. 단일 로그 삭제
    if (isset($_POST['del_id']) && $_POST['del_id']) {
        $del_id = (int)$_POST['del_id'];
        sql_query(" delete from rain_admin_action_log where log_id = '{$del_id}' ", false);
        echo json_encode(array('success' => true));
        exit;
    }

    // 4. 로그 전체 삭제
    if (isset($_POST['del_all']) && $_POST['del_all'] == 1) {
        sql_query(" truncate table rain_admin_action_log ", false);
        echo json_encode(array('success' => true));
        exit;
    }
    
    echo json_encode(array('error' => '잘못된 요청입니다.'));
    exit;
}
// -----------------------------------

// 그누보드 달력 UI용 스크립트 로드
add_javascript('<script src="'.G5_JS_URL.'/jquery-ui.min.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/font-awesome/css/font-awesome.min.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/jquery-ui.css">', 0);

$g5['title'] = '로그 관리';
include_once('./admin.head.php');

// 파라미터 수신 및 정제
$fr_date = isset($_GET['fr_date']) ? clean_xss_tags($_GET['fr_date']) : '';
$to_date = isset($_GET['to_date']) ? clean_xss_tags($_GET['to_date']) : '';
$s_type = isset($_GET['s_type']) ? clean_xss_tags($_GET['s_type']) : '';
$s_result = isset($_GET['s_result']) ? clean_xss_tags($_GET['s_result']) : '';
$stx = isset($_GET['stx']) ? clean_xss_tags($_GET['stx']) : '';

// 쿼리 조립
$sql_search = " WHERE 1=1 ";
if ($fr_date && $to_date) {
    $sql_search .= " and log_datetime between '{$fr_date} 00:00:00' and '{$to_date} 23:59:59' ";
}
if ($s_type) {
    $sql_search .= " and log_type = '{$s_type}' ";
}
if ($s_result) {
    $sql_search .= " and log_result = '{$s_result}' ";
}
if ($stx) {
    $sql_search .= " and mb_id like '%{$stx}%' ";
}

$sql_common = " from rain_admin_action_log " . $sql_search;

// 페이징
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'] ?? 0;

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select * $sql_common order by log_id desc limit $from_record, $rows ";
$result = sql_query($sql);

$qstr = "fr_date={$fr_date}&amp;to_date={$to_date}&amp;s_type={$s_type}&amp;s_result={$s_result}&amp;stx={$stx}";
?>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get" style="display:flex; gap:15px; align-items:center; background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eaeaea; margin-bottom:20px;">
    
    <div style="display:flex; align-items:center; gap:5px;">
        <span style="font-weight:bold; color:#333; margin-right:5px;">기간 설정</span>
        <input type="text" name="fr_date" value="<?php echo $fr_date; ?>" id="fr_date" class="frm_input" size="11" maxlength="10" placeholder="시작일" autocomplete="off" style="text-align:center;">
        <span>~</span>
        <input type="text" name="to_date" value="<?php echo $to_date; ?>" id="to_date" class="frm_input" size="11" maxlength="10" placeholder="종료일" autocomplete="off" style="text-align:center;">
    </div>

    <div style="display:flex; align-items:center; gap:5px;">
        <span style="font-weight:bold; color:#333; margin-right:5px;">로그유형</span>
        <select name="s_type" style="height:35px; border-color:#d5d5d5;">
            <option value="">전체</option>
            <option value="등록" <?php echo get_selected($s_type, '등록'); ?>>등록</option>
            <option value="수정" <?php echo get_selected($s_type, '수정'); ?>>수정</option>
            <option value="삭제" <?php echo get_selected($s_type, '삭제'); ?>>삭제</option>
            <option value="읽기" <?php echo get_selected($s_type, '읽기'); ?>>읽기</option>
        </select>
    </div>

    <div style="display:flex; align-items:center; gap:5px;">
        <span style="font-weight:bold; color:#333; margin-right:5px;">결과</span>
        <select name="s_result" style="height:35px; border-color:#d5d5d5;">
            <option value="">전체</option>
            <option value="성공" <?php echo get_selected($s_result, '성공'); ?>>성공</option>
            <option value="실패" <?php echo get_selected($s_result, '실패'); ?>>실패</option>
        </select>
    </div>

    <div style="display:flex; align-items:center; gap:5px; margin-left:auto;">
        <label for="stx" class="sound_only">아이디 검색</label>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="아이디 검색" style="height:35px;">
        <button type="submit" class="btn_submit" style="height:35px; padding:0 20px; background:#3f51b5; color:#fff; border:none; border-radius:3px;">검색</button>
        <a href="./admin_log_list.php" class="btn btn_02" style="height:35px; line-height:35px; border-radius:3px; padding:0 15px; background:#fff; color:#333; border:1px solid #ccc;">초기화</a>
    </div>
</form>

<div class="local_ov01 local_ov" style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
    <span class="btn_ov01"><span class="ov_txt">총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>건 </span></span>
    
    <button type="button" onclick="deleteAllLogs();" class="btn_submit" style="background:#ff3061; padding:5px 15px; border-radius:3px; color:#fff; border:none; cursor:pointer;">로그 전체 삭제</button>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
        <tr>
            <th scope="col" style="width:80px;">번호</th>
            <th scope="col" style="width:150px;">아이디</th>
            <th scope="col" style="width:100px;">로그유형</th>
            <th scope="col">메뉴</th>
            <th scope="col" style="width:180px;">일시</th>
            <th scope="col" style="width:150px;">IP 주소</th>
            <th scope="col" style="width:100px;">결과</th>
            <th scope="col" style="width:80px;">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i%2);
            
            if ($row['log_result'] == '성공') {
                $result_badge = '<span style="display:inline-block; border:1px solid #2CC185; color:#2CC185; padding:2px 15px; border-radius:15px; font-weight:bold; font-size:0.9em;">성공</span>';
            } else {
                $result_badge = '<span style="display:inline-block; border:1px solid #fe528f; color:#fe528f; padding:2px 15px; border-radius:15px; font-weight:bold; font-size:0.9em;">실패</span>';
            }
            
            $menu_display = $row['log_menu'] . ' &gt; ' . $row['log_type'];
        ?>
        <tr class="<?php echo $bg; ?>">
            <td style="text-align:center;"><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <td style="text-align:center;"><?php echo get_text($row['mb_id']); ?></td>
            <td style="text-align:center;"><?php echo $row['log_type']; ?></td>
            <td style="text-align:center; color:#555;"><?php echo $menu_display; ?></td>
            <td style="text-align:center; color:#888;"><?php echo $row['log_datetime']; ?></td>
            <td style="text-align:center; color:#888;"><?php echo $row['log_ip']; ?></td>
            <td style="text-align:center;"><?php echo $result_badge; ?></td>
            <td style="text-align:center;">
                <button type="button" onclick="deleteLog(<?php echo $row['log_id']; ?>);" class="btn btn_02" style="padding:3px 8px; font-size:0.9em; height:auto; line-height:1.5; border:none; cursor:pointer;">삭제</button>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="8" class="empty_table">로그 내역이 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<script>
$(function(){
    $("#fr_date, #to_date").datepicker({
        changeMonth: true, 
        changeYear: true, 
        dateFormat: "yy-mm-dd", 
        showButtonPanel: true, 
        yearRange: "c-99:c+99"
    });
});

var g5_token = "<?php echo get_admin_token(); ?>";

function deleteLog(logId) {
    if (!confirm('해당 로그를 삭제하시겠습니까?')) return false;

    $.ajax({
        url: './admin_log_list.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'ajax_delete',
            del_id: logId,
            token: g5_token
        },
        success: function(res) {
            if(res.success) {
                // [수정] 줄을 단순히 숨기지 않고 페이지를 새로고침하여 빈자리를 다음 게시물로 채웁니다.
                location.reload(); 
            } else {
                alert(res.error || '삭제 중 오류가 발생했습니다.');
            }
        },
        error: function(xhr, status, error) {
            alert('서버 통신 오류가 발생했습니다.\n응답 오류: ' + error);
            console.log(xhr.responseText);
        }
    });
}

function deleteAllLogs() {
    if (!confirm('모든 로그를 정말 삭제하시겠습니까?\n삭제된 데이터는 복구할 수 없습니다.')) return false;

    $.ajax({
        url: './admin_log_list.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'ajax_delete',
            del_all: 1,
            token: g5_token
        },
        success: function(res) {
            if(res.success) {
                alert('모든 로그가 삭제되었습니다.');
                location.reload(); 
            } else {
                alert(res.error || '삭제 중 오류가 발생했습니다.');
            }
        },
        error: function(xhr, status, error) {
            alert('서버 통신 오류가 발생했습니다.\n응답 오류: ' + error);
            console.log(xhr.responseText);
        }
    });
}
</script>

<?php include_once('./admin.tail.php'); ?>