<?php
$sub_menu = "900200";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

// --- [처리 로직] 관리자 추가 ---
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    check_admin_token();
    
    $fs_id = (int)$_POST['fs_id'];
    $mb_id = clean_xss_tags(trim($_POST['mb_id']));
    
    if (!$fs_id) alert('행사를 선택해 주세요.');
    if (!$mb_id) alert('회원 아이디를 입력해 주세요.');

    // 1. 회원 존재 여부 확인
    $mb = get_member($mb_id);
    if (!$mb['mb_id']) {
        alert('존재하지 않는 회원 아이디입니다. [회원관리] 메뉴에서 먼저 아이디를 생성해 주세요.');
    }
    
    // 2. 이미 등록된 관리자인지 중복 확인
    $chk = sql_fetch(" select fm_id from rain_festival_manager where fs_id = '{$fs_id}' and mb_id = '{$mb_id}' and role_type = '총괄관리자' ");
    if ($chk['fm_id']) {
        alert('해당 회원은 이미 이 행사의 총괄관리자로 지정되어 있습니다.');
    }

    // 3. 매핑 테이블에 기록 (SaaS 코어 로직)
    $sql = " INSERT INTO rain_festival_manager 
             SET mb_id = '{$mb_id}', 
                 fs_id = '{$fs_id}', 
                 role_type = '총괄관리자' ";
    sql_query($sql);

    // 4. 회원 레벨 자동 조정 (레벨 8: 행사 총괄 관리자)
    if ($mb['mb_level'] < 8) {
        sql_query(" UPDATE {$g5['member_table']} SET mb_level = 8 WHERE mb_id = '{$mb_id}' ");
    }

    // 5. [신규 추가] 그누보드 관리자 메뉴 접근 권한(g5_auth) 자동 부여
    // 행사관리자에게 필요한 800번대(현장관리) 메뉴들의 읽기/쓰기/삭제(r,w,d) 권한을 일괄 부여합니다.
    $auth_menus = array('800000','800100','800200','800300','800400','800500','800600','800700','800800','800900','800910');
    foreach($auth_menus as $menu_code) {
        $chk_au = sql_fetch("select count(*) as cnt from {$g5['auth_table']} where mb_id = '{$mb_id}' and au_menu = '{$menu_code}'");
        if($chk_au['cnt'] == 0) {
            sql_query(" insert into {$g5['auth_table']} set mb_id='{$mb_id}', au_menu='{$menu_code}', au_auth='r,w,d' ");
        }
    }

    goto_url('./festival_manager_list.php');
}

// --- [처리 로직] 관리자 지정 해제 ---
if (isset($_GET['w']) && $_GET['w'] == 'd') {
    $fm_id = (int)$_GET['fm_id'];
    
    // 삭제 전 회원 ID 확인
    $row = sql_fetch(" select mb_id from rain_festival_manager where fm_id = '{$fm_id}' ");
    if ($row['mb_id']) {
        // 매핑 테이블에서 삭제
        sql_query(" DELETE FROM rain_festival_manager WHERE fm_id = '{$fm_id}' ");
        
        // [신규 추가] 행사 관리 권한(800번대 메뉴) 모두 회수 (로그인 차단 목적)
        sql_query(" DELETE FROM {$g5['auth_table']} WHERE mb_id = '{$row['mb_id']}' AND au_menu LIKE '800%' ");
    }
    goto_url('./festival_manager_list.php');
}

$g5['title'] = '행사별 총괄관리자 지정';
include_once('./admin.head.php');

// 개설된 행사 목록 (Select Box 용)
$fs_result = sql_query(" select fs_id, fs_name from rain_festival order by fs_id desc ");

// 매핑된 관리자 목록 (목록 출력용)
$sql_list = " SELECT a.*, b.fs_name, c.mb_name, c.mb_level 
              FROM rain_festival_manager a
              LEFT JOIN rain_festival b ON a.fs_id = b.fs_id
              LEFT JOIN {$g5['member_table']} c ON a.mb_id = c.mb_id
              WHERE a.role_type = '총괄관리자'
              ORDER BY a.fm_id DESC ";
$result = sql_query($sql_list);
?>

<div class="local_desc01 local_desc">
    <p><strong>[행사별 총괄관리자 운영 가이드]</strong></p>
    <ul style="margin-top:10px; line-height:1.8em;">
        <li>이곳은 특정 행사를 통째로 관리할 <strong>단체/주최자용 아이디를 연결</strong>해주는 곳입니다.</li>
        <li>아이디가 없다면 좌측 메뉴의 <a href="./member_list.php" style="font-weight:bold; color:#3f51b5; text-decoration:underline;">[회원관리]</a>에서 행사 주최자용 아이디를 먼저 생성해 주세요.</li>
        <li>관리자로 지정된 아이디는 <strong>자동으로 권한 레벨 8로 등업되며, 행사 관리 메뉴(800번대) 접근 권한이 자동 부여</strong>됩니다.</li>
    </ul>
</div>

<form name="fmanager" id="fmanager" action="./festival_manager_list.php" method="post" style="margin-bottom:30px;">
<input type="hidden" name="action" value="add">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>총괄 관리자 신규 지정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="fs_id">행사 선택<strong class="sound_only">필수</strong></label></th>
            <td>
                <select name="fs_id" id="fs_id" required class="required" style="height:30px; min-width:250px;">
                    <option value="">-- 개설된 행사를 선택하세요 --</option>
                    <?php while($fs = sql_fetch_array($fs_result)) { ?>
                        <option value="<?php echo $fs['fs_id']; ?>"><?php echo get_text($fs['fs_name']); ?></option>
                    <?php } ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="mb_id">관리자 아이디<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="mb_id" id="mb_id" required class="frm_input required" size="20" placeholder="그누보드 회원 아이디">
                <button type="submit" class="btn_submit" style="margin-left:5px;">관리자 지정하기</button>
            </td>
        </tr>
        </tbody>
    </table>
</div>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>지정된 행사 관리자 목록</caption>
        <thead>
        <tr>
            <th scope="col">행사명</th>
            <th scope="col">관리자 아이디</th>
            <th scope="col">관리자 이름</th>
            <th scope="col">회원 레벨</th>
            <th scope="col">권한 구분</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $list_count = 0;
        while ($row = sql_fetch_array($result)) {
            $bg = 'bg'.($list_count%2);
            $list_count++;
        ?>
        <tr class="<?php echo $bg; ?>">
            <td class="td_left" style="font-weight:bold; color:#3f51b5;"><?php echo get_text($row['fs_name']); ?></td>
            <td class="td_center"><strong><?php echo $row['mb_id']; ?></strong></td>
            <td class="td_center"><?php echo get_text($row['mb_name']); ?></td>
            <td class="td_center">Lv. <?php echo $row['mb_level']; ?></td>
            <td class="td_center"><span style="background:#e8fae8; color:#2CC185; padding:3px 10px; border-radius:15px; font-weight:bold; font-size:0.9em; border:1px solid #2CC185;">총괄 관리자</span></td>
            <td class="td_center">
                <a href="./festival_manager_list.php?w=d&amp;fm_id=<?php echo $row['fm_id']; ?>" onclick="return confirm('이 아이디의 행사 관리 권한을 해제하시겠습니까?\n(회원 아이디 자체가 삭제되지는 않습니다.)');" class="btn btn_02">권한 해제</a>
            </td>
        </tr>
        <?php 
        } 
        if ($list_count == 0) { 
            echo '<tr><td colspan="6" class="empty_table">지정된 행사 관리자가 없습니다.</td></tr>'; 
        } 
        ?>
        </tbody>
    </table>
</div>

<?php include_once('./admin.tail.php'); ?>