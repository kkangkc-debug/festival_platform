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

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="pi_name">주차장 명<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="pi_name" value="<?php echo get_text($row['pi_name']); ?>" id="pi_name" required class="frm_input required" size="50"></td>
        </tr>
        <tr>
            <th scope="row"><label for="pi_location">위치<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="pi_location" value="<?php echo get_text($row['pi_location']); ?>" id="pi_location" required class="frm_input required" size="80"></td>
        </tr>
        <tr>
            <th scope="row">주차장 유형 (중복선택 가능)</th>
            <td>
                <input type="checkbox" name="pi_type_general" value="1" id="type_gen" <?php echo $row['pi_type_general'] ? 'checked':''; ?>><label for="type_gen"> 일반 주차장</label>&nbsp;
                <input type="checkbox" name="pi_type_barrier" value="1" id="type_bar" <?php echo $row['pi_type_barrier'] ? 'checked':''; ?>><label for="type_bar"> 베리어프리</label>&nbsp;
                <input type="checkbox" name="pi_type_large" value="1" id="type_lar" <?php echo $row['pi_type_large'] ? 'checked':''; ?>><label for="type_lar"> 대형 차량</label>
            </td>
        </tr>
        
        <tr class="capa_gen_wrap">
            <th scope="row">일반 주차 면수</th>
            <td><input type="number" name="pi_capa_general" value="<?php echo $row['pi_capa_general']; ?>" class="frm_input" size="10"> 면</td>
        </tr>
        <tr class="capa_bar_wrap">
            <th scope="row">베리어프리 세부 면수</th>
            <td>
                임산부: <input type="number" name="pi_capa_pregnant" value="<?php echo $row['pi_capa_pregnant']; ?>" class="frm_input calc_bar" size="5"> 면<br>
                경차: <input type="number" name="pi_capa_compact" value="<?php echo $row['pi_capa_compact']; ?>" class="frm_input calc_bar" size="5"> 면<br>
                친환경: <input type="number" name="pi_capa_eco" value="<?php echo $row['pi_capa_eco']; ?>" class="frm_input calc_bar" size="5"> 면<br>
                <strong>총계: <span id="bar_total">0</span> 면</strong>
            </td>
        </tr>
        <tr class="capa_lar_wrap">
            <th scope="row">대형 주차 면수</th>
            <td><input type="number" name="pi_capa_large" value="<?php echo $row['pi_capa_large']; ?>" class="frm_input" size="10"> 면</td>
        </tr>

        <tr>
            <th scope="row"><label for="pi_manager_name">담당자 명<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="pi_manager_name" value="<?php echo get_text($row['pi_manager_name']); ?>" required class="frm_input required"></td>
        </tr>
        <tr>
            <th scope="row"><label for="pi_manager_hp">담당자 연락처<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="pi_manager_hp" value="<?php echo get_text($row['pi_manager_hp']); ?>" required class="frm_input required" placeholder="숫자만 입력"></td>
        </tr>
        <tr>
            <th scope="row">운영상태</th>
            <td>
                <input type="radio" name="pi_status" value="운영" id="st_run" <?php echo $row['pi_status']=='운영'?'checked':''; ?>><label for="st_run"> 운영중</label>
                <input type="radio" name="pi_status" value="점검중" id="st_chk" <?php echo $row['pi_status']=='점검중'?'checked':''; ?>><label for="st_chk"> 점검중</label>
                <input type="radio" name="pi_status" value="폐쇄" id="st_cls" <?php echo $row['pi_status']=='폐쇄'?'checked':''; ?>><label for="st_cls"> 폐쇄</label>
            </td>
        </tr>
        <tr>
            <th scope="row">사용자 노출</th>
            <td>
                <input type="radio" name="pi_is_show" value="1" id="show_y" <?php echo $row['pi_is_show']==1?'checked':''; ?>><label for="show_y"> 노출</label>
                <input type="radio" name="pi_is_show" value="0" id="show_n" <?php echo $row['pi_is_show']==0?'checked':''; ?>><label for="show_n"> 미노출</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="pi_memo">비고 (메모)</label></th>
            <td><textarea name="pi_memo" id="pi_memo" class="frm_input" style="height:100px;"><?php echo get_text($row['pi_memo']); ?></textarea></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./parking_list.php" class="btn btn_02">목록</a>
    <input type="submit" value="<?php echo $w=='u'?'수정완료':'등록'; ?>" class="btn_submit btn" accesskey="s">
</div>
</form>

<script>
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