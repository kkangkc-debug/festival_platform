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
    <p><strong>[상세 화면 안내]</strong></p>
    <ul style="margin-top:10px; line-height:1.8em;">
        <li><strong>관리 정보:</strong> 최초 등록 및 최종 수정에 대한 일시와 관리자 ID를 확인할 수 있습니다.</li>
        <li><strong>노출 정책:</strong> 주차장 등록 시 선택 및 입력한 항목(주차 용량 등)만 조건부로 표출됩니다.</li>
        <li><strong>수정 권한:</strong> 본 화면에서는 정보 수정이 불가하며, 모든 항목은 읽기 전용으로 표시됩니다.</li>
        <li><strong>화면 이동:</strong> 하단의 [수정하기] 버튼 클릭 시 수정 화면(ADM-PARK-004)으로, [목록] 버튼 클릭 시 목록 화면(ADM-PARK-001)으로 이동합니다.</li>
    </ul>
</div>

<form name="fview" id="fview">
<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup><col class="grid_4"><col></colgroup>
        <tbody>
        
        <tr>
            <td colspan="2" style="padding:0; border-bottom:0;">
                <div style="background:#f4f6f9; border:1px solid #d5dce4; padding:20px; margin-bottom:20px; display:flex; justify-content:space-between; border-radius:5px;">
                    <div style="width:48%;">
                        <p style="margin-bottom:10px; font-size:1.05em;"><span style="display:inline-block; width:100px; color:#555;">등록일</span> <strong style="color:#000;"><?php echo $row['pi_datetime']; ?></strong></p>
                        <p style="font-size:1.05em;"><span style="display:inline-block; width:100px; color:#555;">최종 수정일</span> <strong style="color:#000;"><?php echo $row['pi_mod_datetime']; ?></strong></p>
                    </div>
                    <div style="width:48%;">
                        <p style="margin-bottom:10px; font-size:1.05em;"><span style="display:inline-block; width:100px; color:#555;">등록자</span> <strong style="color:#000;"><?php echo $row['mb_id']; ?></strong></p>
                        <p style="font-size:1.05em;"><span style="display:inline-block; width:100px; color:#555;">최종 수정자</span> <strong style="color:#000;"><?php echo $row['pi_mod_id']; ?></strong></p>
                    </div>
                </div>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기본 정보</td></tr>
        <tr>
            <th scope="row">주차장 명</th>
            <td style="font-size:1.05em; font-weight:bold;"><?php echo get_text($row['pi_name']); ?></td>
        </tr>
        <tr>
            <th scope="row">위치</th>
            <td style="font-size:1.05em;"><?php echo get_text($row['pi_location']); ?></td>
        </tr>

        <tr><td colspan="2" class="h2_frm">주차장 유형 설정</td></tr>
        <tr>
            <th scope="row">선택된 주차장 유형</th>
            <td>
                <?php
                // 활성화 여부에 따른 라벨 스타일 (기획서 UI 반영)
                function getTypeLabel($isActive, $label) {
                    if ($isActive) {
                        return '<span style="display:inline-block; background:#3f51b5; color:#fff; padding:6px 15px; border-radius:5px; margin-right:8px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.1);">✓ '.$label.'</span>';
                    } else {
                        return '<span style="display:inline-block; background:#eee; color:#999; padding:6px 15px; border-radius:5px; margin-right:8px;">'.$label.'</span>';
                    }
                }
                echo getTypeLabel($row['pi_type_general'], '일반 주차장');
                echo getTypeLabel($row['pi_type_barrier'], '베리어프리');
                echo getTypeLabel($row['pi_type_large'], '대형 차량');
                ?>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">주차 용량</td></tr>
        <?php if($row['pi_type_general']) { ?>
        <tr>
            <th scope="row">일반 주차 면수</th>
            <td><strong style="font-size:1.1em; color:#333;"><?php echo number_format($row['pi_capa_general']); ?></strong> 면</td>
        </tr>
        <?php } ?>

        <?php if($row['pi_type_barrier']) { ?>
        <tr>
            <th scope="row">베리어프리 세부 면수</th>
            <td>
                <div style="background:#f9f9f9; padding:15px; border:1px solid #eaeaea; border-radius:5px; max-width:300px;">
                    <ul style="list-style:none; padding:0; margin:0; line-height:2.2em;">
                        <li><span style="display:inline-block; width:120px; color:#555;">└ 임산부</span> <strong style="font-size:1.05em;"><?php echo number_format($row['pi_capa_pregnant']); ?></strong> 면</li>
                        <li><span style="display:inline-block; width:120px; color:#555;">└ 경차</span> <strong style="font-size:1.05em;"><?php echo number_format($row['pi_capa_compact']); ?></strong> 면</li>
                        <li><span style="display:inline-block; width:120px; color:#555;">└ 친환경</span> <strong style="font-size:1.05em;"><?php echo number_format($row['pi_capa_eco']); ?></strong> 면</li>
                    </ul>
                    <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #ccc; font-size:1.05em;">
                        <span style="display:inline-block; width:120px; color:#3f51b5; font-weight:bold;">베리어프리 총계</span> 
                        <strong style="color:#3f51b5; font-size:1.2em;"><?php echo number_format($row['pi_capa_pregnant']+$row['pi_capa_compact']+$row['pi_capa_eco']); ?></strong> 면
                    </div>
                </div>
            </td>
        </tr>
        <?php } ?>

        <?php if($row['pi_type_large']) { ?>
        <tr>
            <th scope="row">대형 주차 면수</th>
            <td><strong style="font-size:1.1em; color:#333;"><?php echo number_format($row['pi_capa_large']); ?></strong> 면</td>
        </tr>
        <?php } ?>

        <tr><td colspan="2" class="h2_frm">현장 관리</td></tr>
        <tr>
            <th scope="row">담당자 명</th>
            <td style="font-size:1.05em;"><?php echo get_text($row['pi_manager_name']); ?></td>
        </tr>
        <tr>
            <th scope="row">담당자 연락처</th>
            <td style="font-size:1.05em;"><?php echo get_text($row['pi_manager_hp']); ?></td>
        </tr>

        <tr><td colspan="2" class="h2_frm">운영 설정</td></tr>
        <tr>
            <th scope="row">운영상태</th>
            <td>
                <span style="display:inline-block; background:#e9ebf9; color:#3f51b5; border:1px solid #3f51b5; padding:4px 15px; border-radius:15px; font-weight:bold;">
                    <?php echo $row['pi_status']; ?>
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row">앱 노출 여부</th>
            <td>
                <?php if($row['pi_is_show']) { ?>
                    <span style="display:inline-block; background:#e8fae8; color:#2CC185; border:1px solid #2CC185; padding:4px 15px; border-radius:15px; font-weight:bold;">노출</span>
                <?php } else { ?>
                    <span style="display:inline-block; background:#f2f2f2; color:#888; border:1px solid #ccc; padding:4px 15px; border-radius:15px; font-weight:bold;">미노출</span>
                <?php } ?>
            </td>
        </tr>

        <tr><td colspan="2" class="h2_frm">기타</td></tr>
        <tr>
            <th scope="row">비고</th>
            <td>
                <div style="background:#fafafa; border:1px solid #ddd; padding:15px; min-height:80px; border-radius:5px; line-height:1.6em; color:#444;">
                    <?php echo $row['pi_memo'] ? nl2br(get_text($row['pi_memo'])) : '<span style="color:#aaa;">등록된 메모가 없습니다.</span>'; ?>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
</form>

<div class="btn_fixed_top">
    <a href="./parking_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
    <a href="./parking_form.php?w=u&amp;pi_id=<?php echo $pi_id; ?>&amp;<?php echo $qstr; ?>" class="btn btn_01">수정하기</a>
</div>

<?php include_once('./admin.tail.php'); ?>