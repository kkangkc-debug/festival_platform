<?php
// 1. 그누보드 코어 파일들이 한 칸 위(루트)에 있다고 알려주는 필수 변수입니다.
$g5_path = '..'; 

// 2. 파트너(상점주) 페이지임을 식별하는 커스텀 상수aaa
define('G5_IS_PARTNER', true);

// 3. 반드시 $g5_path 변수를 이용해서 common.php를 호출해야 합니다.
include_once($g5_path . '/common.php');
?>