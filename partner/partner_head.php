<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

// 파트너 페이지 전용 헤더 (모바일 최적화)
$g5_debug['php']['begin_time'] = $begin_time = get_microtime();

// 공통 라이브러리
require_once G5_PATH . '/head.sub.php';

// 파트너 권한 체크
include_once(G5_PARTNER_PATH . '/partner_check.php');

// 현재 페이지 메뉴 설정
$page_menu = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?php echo $g5['title']; ?></title>
<link rel="stylesheet" href="<?php echo G5_CSS_URL; ?>/default.css">
<link rel="stylesheet" href="<?php echo G5_JS_URL; ?>/font-awesome/css/font-awesome.min.css">
<!--[if lt IE 9]>
<script src="<?php echo G5_JS_URL; ?>/html5shiv.min.js"></script>
<![endif]-->
<script>
var g5_url = "<?php echo G5_URL; ?>";
var g5_bbs_url = "<?php echo G5_BBS_URL; ?>";
var g5_is_member = "<?php echo isset($member['mb_id']) ? 1 : 0; ?>";
var g5_is_admin = "<?php echo isset($is_admin) ? $is_admin : ''; ?>";
var g5_bo_table = "<?php echo isset($bo_table) ? $bo_table : ''; ?>";
var g5_sca = "<?php echo isset($sca) ? $sca : ''; ?>";
var g5_editor = "<?php echo (isset($w) && $w == 'u') ? '1' : ''; ?>";
var g5_cookie_domain = "<?php echo G5_COOKIE_DOMAIN; ?>";
</script>
<style>
/* 파트너 전용 모바일 스타일 */
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; font-size: 14px; }
.partner_wrap { max-width: 600px; margin: 0 auto; background: #fff; min-height: 100vh; }

/* 헤더 */
.partner_header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 15px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.partner_header h1 { margin: 0; font-size: 18px; font-weight: 600; }
.partner_shop_name { font-size: 13px; opacity: 0.9; margin-top: 5px; }

/* 하단 네비게이션 */
.partner_nav { position: fixed; bottom: 0; left: 0; right: 0; max-width: 600px; margin: 0 auto; background: #fff; border-top: 1px solid #eee; display: flex; justify-content: space-around; padding: 8px 0; z-index: 100; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); }
.partner_nav a { flex: 1; text-align: center; color: #888; font-size: 11px; text-decoration: none; padding: 5px 0; }
.partner_nav a.active { color: #667eea; }
.partner_nav a i { display: block; font-size: 20px; margin-bottom: 3px; }
.partner_nav a span { display: block; }

/* 컨텐츠 영역 */
.partner_content { padding: 15px; padding-bottom: 80px; }
.page_title { font-size: 20px; font-weight: 600; margin: 0 0 15px 0; color: #333; }

/* 카드 스타일 */
.card { background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.card_title { font-size: 16px; font-weight: 600; margin: 0 0 10px 0; color: #333; }

/* 통계 카드 */
.stat_card { display: flex; align-items: center; justify-content: space-between; }
.stat_value { font-size: 24px; font-weight: 700; color: #667eea; }
.stat_label { font-size: 12px; color: #888; }
.stat_icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

/* 버튼 */
.btn { display: inline-block; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; text-align: center; border: none; cursor: pointer; transition: all 0.2s; }
.btn_primary { background: #667eea; color: #fff; }
.btn_success { background: #28a745; color: #fff; }
.btn_warning { background: #ffc107; color: #333; }
.btn_danger { background: #dc3545; color: #fff; }
.btn_secondary { background: #6c757d; color: #fff; }
.btn_outline { background: #fff; border: 1px solid #ddd; color: #333; }
.btn_full { width: 100%; display: block; margin-bottom: 10px; }
.btn_sm { padding: 5px 12px; font-size: 12px; }

/* 테이블 */
.tbl_wrap { overflow-x: auto; }
.tbl_wrap table { width: 100%; border-collapse: collapse; font-size: 13px; }
.tbl_wrap th { background: #f8f9fa; padding: 12px 10px; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #667eea; }
.tbl_wrap td { padding: 12px 10px; border-bottom: 1px solid #eee; }
.tbl_wrap tr:last-child td { border-bottom: none; }

/* 배지 */
.badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge_primary { background: #e7eeff; color: #667eea; }
.badge_success { background: #d4edda; color: #28a745; }
.badge_warning { background: #fff3cd; color: #856404; }
.badge_danger { background: #f8d7da; color: #dc3545; }

/* 상태별 배지 */
.status_received { background: #e3f2fd; color: #1976d2; }
.status_cooking { background: #fff3e0; color: #f57c00; }
.status_completed { background: #e8f5e9; color: #388e3c; }
.status_cancelled { background: #ffebee; color: #d32f2f; }

/* 폼 */
.frm_input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
.frm_label { display: block; font-weight: 600; margin-bottom: 5px; color: #555; }
.frm_group { margin-bottom: 15px; }

/* 알림 */
.alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; }
.alert_info { background: #e3f2fd; color: #1976d2; }
.alert_success { background: #e8f5e9; color: #388e3c; }
.alert_warning { background: #fff3e0; color: #f57c00; }
.alert_danger { background: #ffebee; color: #d32f2f; }

/* 빈 상태 */
.empty_state { text-align: center; padding: 40px 20px; color: #888; }
.empty_state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
.empty_state p { margin: 0; font-size: 14px; }

/* 로딩 */
.loading { text-align: center; padding: 20px; color: #888; }

/* 반응형 */
@media (max-width: 600px) {
    .partner_content { padding: 12px; }
    .card { padding: 12px; }
    .stat_value { font-size: 20px; }
}
</style>
</head>
<body>

<div class="partner_wrap">
    <!-- 헤더 -->
    <header class="partner_header">
        <h1><?php echo $g5['title']; ?></h1>
        <div class="partner_shop_name">
            <i class="fa fa-store"></i> <?php echo get_text($partner_shop['rt_name']); ?>
            <?php if ($partner_shop['rt_status'] == '영업중') { ?>
                <span class="badge badge_success" style="margin-left: 5px;">영업중</span>
            <?php } else if ($partner_shop['rt_status'] == '준비중') { ?>
                <span class="badge badge_warning" style="margin-left: 5px;">준비중</span>
            <?php } else { ?>
                <span class="badge badge_danger" style="margin-left: 5px;">마감</span>
            <?php } ?>
        </div>
    </header>

    <!-- 컨텐츠 -->
    <div class="partner_content">
