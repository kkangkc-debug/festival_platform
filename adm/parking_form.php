<?php
$sub_menu = "800100";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = isset($_GET['w']) ? clean_xss_tags($_GET['w']) : '';
$pi_id = isset($_GET['pi_id']) ? (int)$_GET['pi_id'] : 0;

if ($w == 'u') {
    $html_title = '주차장 수정';
    $sql = " select * from rain_park_info where pi_id = '{$pi_id}' ";
    $row = sql_fetch($sql);
    if (!$row['pi_id']) alert('존재하지 않는 자료입니다.');
} else {
    $html_title = '주차장 등록';
    // [오류 수정] Undefined array key 방지를 위해 폼에서 호출하는 모든 초기값을 명시함
    $row = array(
        'pi_name' => '', 
        'pi_location' => '', 
        'pi_type_general' => 0, 
        'pi_type_barrier' => 0, 
        'pi_type_large' => 0, 
        'pi_capa_general' => '', 
        'pi_capa_pregnant' => '', 
        'pi_capa_compact' => '', 
        'pi_capa_eco' => '', 
        'pi_capa_large' => '', 
        'pi_manager_name' => '', 
        'pi_manager_hp' => '', 
        'pi_status' => '운영', 
        'pi_is_show' => 1, 
        'pi_memo' => ''
    );
}

$g5['title'] = $html_title;
include_once('./admin.head.php');
?>

<form name="fparkingform" id="fparkingform" action="./parking_form_update.php" method="post" autocomplete="off">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="pi_id" value="<?php echo $pi_id; ?>">
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
            <th scope="row"><label for="pi_name">주차장 명<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="pi_name" value="<?php echo get_text($row['pi_name']); ?>" id="pi_name" required class="frm_input required" size="50" placeholder="예: 제 1주차장(메인)">
                <span class="frm_info">사용자에게 노출되는 주차장 명입니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="pi_location">위치<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="pi_location" value="<?php echo get_text($row['pi_location']); ?>" id="pi_location" required class="frm_input required" size="60" placeholder="상세 주소 입력">
                <button type="button" class="btn_frmline" onclick="execDaumPostcode()">주소 검색</button>
                <span class="frm_info">주차장의 위치를 입력해주세요. (지도에서 검색가능한 주소)</span>
            </td>
        </tr>
        
        <tr><td colspan="2" class="h2_frm">주차장 유형 설정</td></tr>
        <tr>
            <th scope="row">주차장 유형 (중복선택 가능)</th>
            <td>
                <input type="checkbox" name="pi_type_general" value="1" id="type_gen" <?php echo $row['pi_type_general'] ? 'checked':''; ?>><label for="type_gen"> 일반 주차장</label>&nbsp;
                <input type="checkbox" name="pi_type_barrier" value="1" id="type_bar" <?php echo $row['pi_type_barrier'] ? 'checked':''; ?>><label for="type_bar"> 베리어프리</label>&nbsp;
                <input type="checkbox" name="pi_type_large" value="1" id="type_lar" <?php echo $row['pi_type_large'] ? 'checked':''; ?>><label for="type_lar"> 대형 차량</label>
                <span class="frm_info">중복 선택 가능하며, 1개 이상 필수 선택해야 합니다. 선택한 주차장 유형에 따라 하단의 '주차 용량' 입력 항목이 조건부로 표출됩니다.</span>
            </td>
        </tr>
        
        <tr class="capa_gen_wrap">
            <th scope="row">일반 주차 면수</th>
            <td>
                <input type="number" name="pi_capa_general" value="<?php echo $row['pi_capa_general']; ?>" class="frm_input" size="10"> 면
                <span class="frm_info">일반 주차장 선택 시 입력 필수입니다.</span>
            </td>
        </tr>
        <tr class="capa_bar_wrap">
            <th scope="row">베리어프리 세부 면수</th>
            <td>
                임산부: <input type="number" name="pi_capa_pregnant" value="<?php echo $row['pi_capa_pregnant']; ?>" class="frm_input calc_bar" size="5"> 면<br>
                경차: <input type="number" name="pi_capa_compact" value="<?php echo $row['pi_capa_compact']; ?>" class="frm_input calc_bar" size="5"> 면<br>
                친환경: <input type="number" name="pi_capa_eco" value="<?php echo $row['pi_capa_eco']; ?>" class="frm_input calc_bar" size="5"> 면<br>
                <strong style="display:inline-block; margin-top:5px;">베리어프리 총계: <span id="bar_total">0</span> 면</strong>
                <span class="frm_info">베리어프리 선택 시 1개 이상 선택 필수이며, 총계는 자동 계산됩니다.</span>
            </td>
        </tr>
        <tr class="capa_lar_wrap">
            <th scope="row">대형 주차 면수</th>
            <td>
                <input type="number" name="pi_capa_large" value="<?php echo $row['pi_capa_large']; ?>" class="frm_input" size="10"> 면
                <span class="frm_info">대형 차량 선택 시 입력 필수입니다.</span>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">현장 관리</td></tr>
        <tr>
            <th scope="row"><label for="pi_manager_name">담당자 명<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="pi_manager_name" value="<?php echo get_text($row['pi_manager_name']); ?>" required class="frm_input required" placeholder="담당자명 입력">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="pi_manager_hp">담당자 연락처<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="pi_manager_hp" value="<?php echo get_text($row['pi_manager_hp']); ?>" required class="frm_input required" placeholder="예: 010-0000-0000">
                <span class="frm_info">현장 문의 시 사용됩니다. (숫자만 입력 가능)</span>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">운영 설정</td></tr>
        <tr>
            <th scope="row">운영상태</th>
            <td>
                <input type="radio" name="pi_status" value="운영" id="st_run" <?php echo $row['pi_status']=='운영'?'checked':''; ?>><label for="st_run"> 운영중</label>
                <input type="radio" name="pi_status" value="점검중" id="st_chk" <?php echo $row['pi_status']=='점검중'?'checked':''; ?>><label for="st_chk"> 점검중</label>
                <input type="radio" name="pi_status" value="폐쇄" id="st_cls" <?php echo $row['pi_status']=='폐쇄'?'checked':''; ?>><label for="st_cls"> 폐쇄</label>
                <span class="frm_info">정상 운영 중일 경우 '운영중'을 선택합니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row">사용자 노출</th>
            <td>
                <input type="radio" name="pi_is_show" value="1" id="show_y" <?php echo $row['pi_is_show']==1?'checked':''; ?>><label for="show_y"> 노출</label>
                <input type="radio" name="pi_is_show" value="0" id="show_n" <?php echo $row['pi_is_show']==0?'checked':''; ?>><label for="show_n"> 미노출</label>
                <span class="frm_info">사용자 노출이 '미노출' 시 사용자 페이지에서 해당 주차장이 미표출됩니다.</span>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기타</td></tr>
        <tr>
            <th scope="row"><label for="pi_memo">비고 (메모)</label></th>
            <td>
                <textarea name="pi_memo" id="pi_memo" class="frm_input" style="height:100px;" placeholder="추가 사항을 입력하세요."><?php echo get_text($row['pi_memo']); ?></textarea>
                <span class="frm_info">관리자 메모용이며, 사용자 페이지에는 노출되지 않습니다.</span>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./parking_list.php" class="btn btn_02">취소(목록)</a>
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
            document.getElementById("pi_location").value = addr;
            document.getElementById("pi_location").focus();
        }
    }).open();
}

$(function(){
    // 주차 유형 체크 시 용량 폼 토글
    function toggle_capa() {
        if($('#type_gen').is(':checked')) $('.capa_gen_wrap').show(); else $('.capa_gen_wrap').hide();
        if($('#type_bar').is(':checked')) $('.capa_bar_wrap').show(); else $('.capa_bar_wrap').hide();
        if($('#type_lar').is(':checked')) $('.capa_lar_wrap').show(); else $('.capa_lar_wrap').hide();
    }
    $('#type_gen, #type_bar, #type_lar').on('change', toggle_capa);
    toggle_capa(); // 초기 실행

    // 베리어프리 합산
    function calc_barrier() {
        var sum = 0;
        $('.calc_bar').each(function(){
            sum += Number($(this).val()) || 0;
        });
        $('#bar_total').text(sum);
    }
    $('.calc_bar').on('keyup change', calc_barrier);
    calc_barrier(); // 초기 실행
});
</script>

<?php include_once('./admin.tail.php'); ?>