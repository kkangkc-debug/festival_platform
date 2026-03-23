    </div>
    <!-- //컨텐츠 -->

    <!-- 하단 네비게이션 -->
    <nav class="partner_nav">
        <a href="<?php echo G5_PARTNER_URL; ?>/index.php" class="<?php echo $page_menu == 'index' ? 'active' : ''; ?>">
            <i class="fa fa-home"></i>
            <span>홈</span>
        </a>
        <a href="<?php echo G5_PARTNER_URL; ?>/order.php" class="<?php echo $page_menu == 'order' ? 'active' : ''; ?>">
            <i class="fa fa-list"></i>
            <span>주문</span>
        </a>
        <a href="<?php echo G5_PARTNER_URL; ?>/menu.php" class="<?php echo $page_menu == 'menu' ? 'active' : ''; ?>">
            <i class="fa fa-cutlery"></i>
            <span>메뉴</span>
        </a>
        <a href="<?php echo G5_PARTNER_URL; ?>/sales.php" class="<?php echo $page_menu == 'sales' ? 'active' : ''; ?>">
            <i class="fa fa-bar-chart"></i>
            <span>매출</span>
        </a>
        <a href="<?php echo G5_BBS_URL; ?>/logout.php" class="">
            <i class="fa fa-sign-out"></i>
            <span>로그아웃</span>
        </a>
    </nav>
</div>

<script src="<?php echo G5_JS_URL; ?>/jquery-1.12.4.min.js"></script>
<script src="<?php echo G5_JS_URL; ?>/jquery-migrate-1.4.1.min.js"></script>
<script src="<?php echo G5_JS_URL; ?>/common.js"></script>
<script>
// 파트너 페이지 공통 스크립트

// AJAX 요청 헬퍼
function partnerAjax(url, data, successCallback, errorCallback) {
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                if (successCallback) successCallback(res);
            } else {
                if (errorCallback) errorCallback(res);
                else alert(res.message || '오류가 발생했습니다.');
            }
        },
        error: function(xhr) {
            if (errorCallback) errorCallback(xhr);
            else alert('통신 오류가 발생했습니다.');
        }
    });
}

// 알림 표시
function partnerAlert(message, type) {
    type = type || 'info';
    var alertClass = 'alert_info';
    if (type === 'success') alertClass = 'alert_success';
    if (type === 'warning') alertClass = 'alert_warning';
    if (type === 'danger') alertClass = 'alert_danger';

    var alertHtml = '<div class="alert ' + alertClass + '">' + message + '</div>';
    $('.partner_content').prepend(alertHtml);

    setTimeout(function() {
        $('.alert').first().fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// 확인 다이얼로그
function partnerConfirm(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// 페이지 새로고침
function partnerReload() {
    location.reload();
}
</script>

</body>
</html>
<?php
// 디버깅 정보 출력 (개발 모드일 때만)
if (defined('G5_DEBUG') && G5_DEBUG) {
    $g5_debug['php']['end_time'] = get_microtime();
    $g5_debug['php']['run_time'] = $g5_debug['php']['end_time'] - $g5_debug['php']['begin_time'];

    echo "\n<!-- PHP 실행시간: " . number_format($g5_debug['php']['run_time'], 4) . " 초 -->\n";
}
?>
