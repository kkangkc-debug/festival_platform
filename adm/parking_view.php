<?php
$sub_menu = "800100";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$pi_id = isset($_GET['pi_id']) ? (int)$_GET['pi_id'] : 0;
$sql = " select * from rain_park_info where pi_id = '{$pi_id}' ";
$row = sql_fetch($sql);
if (!$row['pi_id']) alert('존재하지 않는 자료입니다.');

$g5['title'] = '주차장 상세 정보';
include_once('./admin.head.php');
?>

<div class="local_desc01 local_desc">
    <strong>관리 정보</strong><br>
    - 최초 등록 : <?php echo $row['pi_datetime']; ?> (<?php echo $row['mb_id']; ?>)<br>
    - 최종 수정 : <?php echo $row['pi_mod_datetime']; ?> (<?php echo $row['pi_mod_id']; ?>)
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr>
            <th scope="row">주차장 명</th>
            <td><?php echo get_text($row['pi_name']); ?></td>
        </tr>
        <tr>
            <th scope="row">위치</th>
            <td><?php echo get_text($row['pi_location']); ?></td>
        </tr>
        <tr>
            <th scope="row">일반 주차 면수</th>
            <td><?php echo $row['pi_type_general'] ? number_format($row['pi_capa_general']).' 면' : '미사용'; ?></td>
        </tr>
        <tr>
            <th scope="row">베리어프리 면수</th>
            <td>
                <?php if($row['pi_type_barrier']) { ?>
                    임산부: <?php echo number_format($row['pi_capa_pregnant']); ?> 면 / 
                    경차: <?php echo number_format($row['pi_capa_compact']); ?> 면 / 
                    친환경: <?php echo number_format($row['pi_capa_eco']); ?> 면<br>
                    <strong>총계: <?php echo number_format($row['pi_capa_pregnant']+$row['pi_capa_compact']+$row['pi_capa_eco']); ?> 면</strong>
                <?php } else { echo "미사용"; } ?>
            </td>
        </tr>
        <tr>
            <th scope="row">대형 주차 면수</th>
            <td><?php echo $row['pi_type_large'] ? number_format($row['pi_capa_large']).' 면' : '미사용'; ?></td>
        </tr>
        <tr>
            <th scope="row">담당자 정보</th>
            <td>이름: <?php echo get_text($row['pi_manager_name']); ?> / 연락처: <?php echo get_text($row['pi_manager_hp']); ?></td>
        </tr>
        <tr>
            <th scope="row">운영 설정</th>
            <td>상태: <?php echo $row['pi_status']; ?> / 앱 노출: <?php echo $row['pi_is_show'] ? '노출':'미노출'; ?></td>
        </tr>
        <tr>
            <th scope="row">비고</th>
            <td><?php echo nl2br(get_text($row['pi_memo'])); ?></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./parking_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
    <a href="./parking_form.php?w=u&amp;pi_id=<?php echo $pi_id; ?>&amp;<?php echo $qstr; ?>" class="btn btn_01">수정하기</a>
</div>

<?php include_once('./admin.tail.php'); ?>