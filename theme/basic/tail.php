<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 모바일 및 쇼핑몰 분기 로직은 필요에 따라 유지하거나 제거할 수 있습니다.
if (G5_IS_MOBILE) {
    include_once(G5_THEME_MOBILE_PATH.'/tail.php');
    return;
}
?>

    </div> </div> </div> <?php
// 통계 및 분석 스크립트 (관리자 설정에서 입력한 코드)
if ($config['cf_analytics']) {
    echo $config['cf_analytics'];
}

// 최종 시스템 하단 파일 (html, body 태그를 닫고 footer script 포함)
include_once(G5_THEME_PATH."/tail.sub.php");
?>