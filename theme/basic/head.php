<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 모바일 테마 분기 (필요 없으면 제거 가능)
if (G5_IS_MOBILE) {
    include_once(G5_THEME_MOBILE_PATH.'/head.php');
    return;
}

// 핵심 설정 파일 및 서브헤더(CSS/JS 로드) 호출
include_once(G5_THEME_PATH.'/head.sub.php');
?>

<div id="wrapper">
    <div id="container_wr">
        <div id="container">
            <?php if (!defined("_INDEX_")) { ?>
                <h2 id="container_title" style="padding:20px 0; margin:0; font-size:1.5em;">
                    <span title="<?php echo get_text($g5['title']); ?>"><?php echo get_head_title($g5['title']); ?></span>
                </h2>
            <?php } ?>