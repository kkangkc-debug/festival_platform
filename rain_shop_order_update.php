<?php
include_once('./common.php');

$rt_id = (int)$_POST['rt_id'];
// (실제 결제 모듈 연동 전 단계이므로 데이터 저장 로직만 구현)

$od_no = 'ORD-' . date('Ymd') . '-' . sprintf('%03d', rand(1, 999));
$total_amount = 15000; // 예시 금액

// 1. 주문 마스터 저장
$sql = " INSERT INTO rain_restaurant_order
            SET rt_id = '$rt_id',
                od_no = '$od_no',
                mb_id = 'GUEST', 
                od_total_amount = '$total_amount',
                od_pay_method = '현장결제',
                od_status = '대기중',
                od_datetime = '".G5_TIME_YMDHIS."' ";
sql_query($sql);

$od_id = sql_insert_id();

// 2. 주문 상세(아이템) 저장 로직 추가 가능...

alert("주문이 접수되었습니다! \\n주문번호: {$od_no}", "./rain_shop_order_status.php?od_no={$od_no}");
?>