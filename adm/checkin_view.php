<?php
$sub_menu = "800200";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$ci_id = isset($_GET['ci_id']) ? (int)$_GET['ci_id'] : 0;
$sql = " select * from rain_checkin_info where ci_id = '{$ci_id}' ";
$row = sql_fetch($sql);
if (!$row['ci_id']) alert('존재하지 않는 자료입니다.');

$g5['title'] = '체크인존 상세';
include_once('./admin.head.php');
?>

<div class="local_desc01 local_desc">
    <strong>관리 정보</strong><br>
    - 최초 등록 : <?php echo $row['ci_datetime']; ?> (<?php echo $row['mb_id']; ?>)<br>
    - 최종 수정 : <?php echo $row['ci_mod_datetime']; ?> (<?php echo $row['ci_mod_id']; ?>)
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr><td colspan="2" class="h2_frm">기본 정보</td></tr>
        <tr>
            <th scope="row">체크인존 명</th>
            <td><?php echo get_text($row['ci_name']); ?></td>
        </tr>
        <tr>
            <th scope="row">위치</th>
            <td><?php echo get_text($row['ci_location']); ?></td>
        </tr>

        <tr><td colspan="2" class="h2_frm">현장 관리</td></tr>
        <tr>
            <th scope="row">담당자 정보</th>
            <td>이름: <?php echo get_text($row['ci_manager_name']); ?> / 연락처: <?php echo get_text($row['ci_manager_hp']); ?></td>
        </tr>

        <tr><td colspan="2" class="h2_frm">연동 설정</td></tr>
        <tr>
            <th scope="row">단말기 정보</th>
            <td>ID: <?php echo get_text($row['ci_device_id']); ?> / 고유번호: <?php echo get_text($row['ci_device_uuid']); ?></td>
        </tr>
        <tr>
            <th scope="row">연동 상태</th>
            <td><?php echo $row['ci_sync_status']; ?> (당일 입장객: <?php echo number_format($row['ci_today_visitors']); ?>명)</td>
        </tr>

        <tr><td colspan="2" class="h2_frm">운영 설정</td></tr>
        <tr>
            <th scope="row">운영 상태</th>
            <td><?php echo $row['ci_status']; ?></td>
        </tr>
        <tr>
            <th scope="row">앱 노출</th>
            <td><?php echo $row['ci_is_show'] ? '노출':'미노출'; ?></td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기타</td></tr>
        <tr>
            <th scope="row">비고</th>
            <td><?php echo nl2br(get_text($row['ci_memo'])); ?></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./checkin_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
    <a href="./checkin_form.php?w=u&amp;ci_id=<?php echo $ci_id; ?>&amp;<?php echo $qstr; ?>" class="btn btn_01">수정하기</a>
</div>

<?php include_once('./admin.tail.php'); ?>