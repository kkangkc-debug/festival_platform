<?php
$sub_menu = "800200";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$ci_id = isset($_GET['ci_id']) ? (int)$_GET['ci_id'] : 0;

// [SaaS 핵심] 행사명(fs_name)을 가져오기 위해 rain_festival 테이블과 조인하여 쿼리
$sql = " select c.*, f.fs_name 
           from rain_checkin_info c
           left join rain_festival f on c.fs_id = f.fs_id
          where c.ci_id = '{$ci_id}' ";
$row = sql_fetch($sql);

if (!$row['ci_id']) alert('존재하지 않는 자료입니다.');

// [보안] 행사관리자(Lv.8)가 URL 조작으로 다른 행사 데이터를 보려고 할 때 차단
if (defined('MY_FS_ID') && MY_FS_ID > 0) {
    if ($row['fs_id'] != MY_FS_ID) {
        alert('조회 권한이 없는 행사 데이터입니다.');
    }
}

$g5['title'] = '체크인존 상세 정보';
include_once('./admin.head.php');
?>

<div class="local_desc01 local_desc">
    <strong>시스템 기록</strong><br>
    - 소속 행사 ID : <?php echo $row['fs_id']; ?> (<?php echo $row['fs_name'] ? get_text($row['fs_name']) : '미지정'; ?>)<br>
    - 최초 등록 : <?php echo $row['ci_datetime']; ?> (<?php echo $row['mb_id']; ?>)<br>
    - 최종 수정 : <?php echo $row['ci_mod_datetime'] ? $row['ci_mod_datetime'].' ('.$row['ci_mod_id'].')' : '-'; ?>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        <tr><td colspan="2" class="h2_frm">기본 정보</td></tr>
        <?php if ($is_admin == 'super') { ?>
        <tr>
            <th scope="row">소속 행사</th>
            <td style="color:#ff3061; font-weight:bold;"><?php echo $row['fs_name'] ? get_text($row['fs_name']) : '미지정 (전체공용)'; ?></td>
        </tr>
        <?php } ?>
        <tr>
            <th scope="row">체크인존 명</th>
            <td><strong><?php echo get_text($row['ci_name']); ?></strong></td>
        </tr>
        <tr>
            <th scope="row">위치</th>
            <td><?php echo get_text($row['ci_location']); ?></td>
        </tr>

        <tr><td colspan="2" class="h2_frm">현장 관리</td></tr>
        <tr>
            <th scope="row">담당자 정보</th>
            <td>
                <span style="display:inline-block; min-width:60px;">이름:</span> <?php echo get_text($row['ci_manager_name']); ?><br>
                <span style="display:inline-block; min-width:60px;">연락처:</span> <?php echo get_text($row['ci_manager_hp']); ?>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">연동 및 상태</td></tr>
        <tr>
            <th scope="row">단말기 정보</th>
            <td>
                계정 ID : <?php echo get_text($row['ci_device_id']); ?><br>
                기기 UUID : <?php echo get_text($row['ci_device_uuid']); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">통계 및 상태</th>
            <td>
                <?php
                $status_color = '#2CC185'; // 운영
                if($row['ci_status'] == '장애') $status_color = '#fe528f';
                if($row['ci_status'] == '마감') $status_color = '#888';
                ?>
                운영상태 : <span style="color:<?php echo $status_color; ?>; font-weight:bold;"><?php echo $row['ci_status']; ?></span><br>
                당일 입장객 : <strong style="color:#3f51b5;"><?php echo number_format($row['ci_today_visitors']); ?></strong> 명
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기타 설정</td></tr>
        <tr>
            <th scope="row">앱 노출 여부</th>
            <td>
                <?php if($row['ci_is_show']) { ?>
                    <span style="color:#2CC185; font-weight:bold;">노출 중</span>
                <?php } else { ?>
                    <span style="color:#ccc;">미노출</span>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row">비고 (메모)</th>
            <td style="line-height:1.6em; color:#666;">
                <?php echo $row['ci_memo'] ? nl2br(get_text($row['ci_memo'])) : '<span style="color:#eee;">메모가 없습니다.</span>'; ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_checkin_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록으로</a>
    <a href="./rain_checkin_form.php?w=u&amp;ci_id=<?php echo $ci_id; ?>&amp;<?php echo $qstr; ?>" class="btn btn_01">정보 수정</a>
</div>

<?php include_once('./admin.tail.php'); ?>