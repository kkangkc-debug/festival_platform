<?php
$sub_menu = "800200";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = isset($_GET['w']) ? clean_xss_tags($_GET['w']) : '';
$ci_id = isset($_GET['ci_id']) ? (int)$_GET['ci_id'] : 0;

if ($w == 'u') {
    $html_title = '체크인존 수정';
    $sql = " select * from rain_checkin_info where ci_id = '{$ci_id}' ";
    $row = sql_fetch($sql);
    if (!$row['ci_id']) alert('존재하지 않는 자료입니다.');
} else {
    $html_title = '체크인존 등록';
    $row = array(
        'ci_name' => '',
        'ci_location' => '',
        'ci_manager_name' => '',
        'ci_manager_hp' => '',
        'ci_device_id' => '',
        'ci_device_uuid' => '',
        'ci_status' => '운영',
        'ci_is_show' => 1,
        'ci_memo' => ''
    );
}

$g5['title'] = $html_title;
include_once('./admin.head.php');
?>

<form name="fcheckinform" id="fcheckinform" action="./checkin_form_update.php" method="post" autocomplete="off">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="ci_id" value="<?php echo $ci_id; ?>">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<div class="local_desc01 local_desc">
    <p>아래 정보를 입력해주세요.</p>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr><td colspan="2" class="h2_frm">기본 정보</td></tr>
        <tr>
            <th scope="row"><label for="ci_name">체크인존 명<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="ci_name" value="<?php echo get_text($row['ci_name']); ?>" id="ci_name" required class="frm_input required" size="50" placeholder="예: 정문 검표소 A">
                <span class="frm_info">사용자에게 노출되는 체크인존 명입니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ci_location">위치<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="ci_location" value="<?php echo get_text($row['ci_location']); ?>" id="ci_location" required class="frm_input required" size="60" placeholder="상세 주소 입력">
                <button type="button" class="btn_frmline" onclick="execDaumPostcode()">주소 검색</button>
                <span class="frm_info">체크인존의 위치를 입력해주세요. (지도에서 검색가능한 주소)</span>
            </td>
        </tr>
        
        <tr><td colspan="2" class="h2_frm">현장 관리</td></tr>
        <tr>
            <th scope="row"><label for="ci_manager_name">담당자 명<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="ci_manager_name" value="<?php echo get_text($row['ci_manager_name']); ?>" id="ci_manager_name" required class="frm_input required" placeholder="담당자명 입력">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ci_manager_hp">담당자 연락처<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="ci_manager_hp" value="<?php echo get_text($row['ci_manager_hp']); ?>" id="ci_manager_hp" required class="frm_input required" placeholder="예: 010-0000-0000">
                <span class="frm_info">현장 문의 시 사용됩니다. (숫자만 입력 가능)</span>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">연동 설정</td></tr>
        <tr>
            <th scope="row"><label for="ci_device_id">담당자 ID</label></th>
            <td>
                <input type="text" name="ci_device_id" value="<?php echo get_text($row['ci_device_id']); ?>" id="ci_device_id" class="frm_input" size="30" placeholder="단말기 로그인 계정">
                <span class="frm_info">체크인 단말기 로그인에 사용되는 계정 ID 입니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ci_device_uuid">단말기 고유 번호</label></th>
            <td>
                <input type="text" name="ci_device_uuid" value="<?php echo get_text($row['ci_device_uuid']); ?>" id="ci_device_uuid" class="frm_input" size="50" placeholder="기기번호를 입력하세요">
                <span class="frm_info">체크인존에 설치된 QR코드 확인 기기를 식별하기 위한 고유값입니다.</span>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">운영 설정</td></tr>
        <tr>
            <th scope="row">운영상태</th>
            <td>
                <input type="radio" name="ci_status" value="운영" id="st_run" <?php echo $row['ci_status']=='운영'?'checked':''; ?>><label for="st_run"> 운영</label>&nbsp;
                <input type="radio" name="ci_status" value="장애" id="st_chk" <?php echo $row['ci_status']=='장애'?'checked':''; ?>><label for="st_chk"> 장애</label>&nbsp;
                <input type="radio" name="ci_status" value="마감" id="st_cls" <?php echo $row['ci_status']=='마감'?'checked':''; ?>><label for="st_cls"> 마감</label>
                <span class="frm_info"><strong>운영:</strong> 체험 가능 / <strong>장애:</strong> 운영 일시 중단 / <strong>마감:</strong> 당일 운영 종료</span>
            </td>
        </tr>
        <tr>
            <th scope="row">앱 노출</th>
            <td>
                <input type="radio" name="ci_is_show" value="1" id="show_y" <?php echo $row['ci_is_show']==1?'checked':''; ?>><label for="show_y"> 노출</label>&nbsp;
                <input type="radio" name="ci_is_show" value="0" id="show_n" <?php echo $row['ci_is_show']==0?'checked':''; ?>><label for="show_n"> 미노출</label>
                <span class="frm_info">미노출 시 사용자 페이지에서 해당 체크인존이 표출되지 않습니다.</span>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기타</td></tr>
        <tr>
            <th scope="row"><label for="ci_memo">비고 (메모)</label></th>
            <td>
                <textarea name="ci_memo" id="ci_memo" class="frm_input" style="height:100px;" placeholder="추가 사항을 입력하세요."><?php echo get_text($row['ci_memo']); ?></textarea>
                <span class="frm_info">관리자 메모용이며, 사용자 페이지에는 노출되지 않습니다.</span>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./checkin_list.php" class="btn btn_02">목록/취소</a>
    <input type="submit" value="<?php echo $w=='u'?'수정완료':'등록'; ?>" class="btn_submit btn" accesskey="s">
</div>
</form>

<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
function execDaumPostcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            // 도로명 주소를 가져와서 위치 입력란에 넣습니다.
            var addr = data.roadAddress; 
            if(addr === '') {
                addr = data.jibunAddress;
            }
            document.getElementById("ci_location").value = addr;
            document.getElementById("ci_location").focus();
        }
    }).open();
}
</script>

<?php include_once('./admin.tail.php'); ?>