<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 1. 등록된 모든 축제 목록 가져오기
// 변수명을 고유한 $fs_list_res 로 변경하여 테마(head.php)와의 충돌을 방지합니다.
$fs_list_sql = " SELECT * FROM rain_festival WHERE fs_status <> '종료' ORDER BY fs_id DESC ";
$fs_list_res = sql_query($fs_list_sql);

// [디버그용] 테마 호출 전에 결과가 있는지 확인 (필요 없으면 삭제 가능)
// var_dump(sql_num_rows($fs_list_res)); 

include_once(G5_THEME_PATH.'/head.php'); // 테마 헤더 포함
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* 포털 전용 스타일 */
    .portal-wrapper { padding: 40px 0; background: #f4f7fa; min-height: 500px; }
    .portal-header { text-align: center; margin-bottom: 40px; }
    .portal-header h2 { font-size: 28px; color: #3f51b5; font-weight: 900; margin-bottom: 10px; }
    
    .fs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .fs-card { 
        background: #fff; border-radius: 15px; padding: 25px; 
        display: flex; flex-direction: column; justify-content: space-between;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-decoration: none; 
        color: #333; transition: 0.3s; border: 1px solid #eee;
    }
    .fs-card:hover { transform: translateY(-5px); border-color: #3f51b5; box-shadow: 0 15px 30px rgba(63, 81, 181, 0.1); }
    
    .fs-info h3 { margin: 10px 0; font-size: 20px; font-weight: bold; line-height: 1.4; }
    .fs-date { font-size: 14px; color: #888; margin-bottom: 15px; }
    .fs-status-badge { 
        display: inline-block; padding: 4px 12px; border-radius: 5px; 
        font-size: 12px; font-weight: bold;
    }
    
    /* DB 상태값 매칭: 진행중, 준비중 */
    .status-준비중 { background: #fff3e0 !important; color: #ef6c00 !important; }
    .status-진행중 { background: #e8f5e9 !important; color: #2e7d32 !important; }

    .btn-enter { background: #3f51b5; color: #fff; text-align: center; padding: 10px; border-radius: 8px; font-weight: bold; margin-top: 10px; transition: 0.2s; }
    .fs-card:hover .btn-enter { background: #283593; }

    .empty-msg { text-align: center; padding: 100px 20px; color: #aaa; grid-column: 1 / -1; }
</style>

<div class="portal-wrapper">
    <div class="portal-header">
        <div style="font-size: 40px; margin-bottom: 10px;">🎡</div>
        <h2>Rain Festival Portal</h2>
        <p>전국의 축제 현장 관리 시스템에 접속하세요</p>
    </div>

    <div class="fs-grid">
        <?php
        $fs_count = 0;
        // 변경된 고유 변수 $fs_list_res 를 사용하여 루프를 돕니다.
        while ($fs_row = sql_fetch_array($fs_list_res)) {
            $target_url = G5_URL . "/rain_festival.php?fs_id=" . (int)$fs_row['fs_id'];
            $st_clean = trim($fs_row['fs_status']); 
            $st_class = "status-" . $st_clean; 
        ?>
        <a href="<?php echo $target_url; ?>" class="fs-card">
            <div class="fs-info">
                <span class="fs-status-badge <?php echo $st_class; ?>">
                    ● <?php echo get_text($st_clean); ?>
                </span>
                <h3><?php echo get_text($fs_row['fs_name']); ?></h3>
                <div class="fs-date">
                    <i class="fa fa-calendar-alt"></i> 
                    <?php echo $fs_row['fs_start_date']; ?> ~ <?php echo $fs_row['fs_end_date']; ?>
                </div>
            </div>
            <div class="btn-enter">축제 입장하기</div>
        </a>
        <?php 
            $fs_count++;
        } 
        ?>

        <?php if ($fs_count == 0) { ?>
        <div class="empty-msg">
            <i class="fa fa-info-circle" style="font-size: 40px; margin-bottom: 15px;"></i>
            <p>현재 운영 중인 축제가 없습니다.</p>
        </div>
        <?php } ?>
    </div>
</div>

<?php
include_once(G5_THEME_PATH.'/tail.php'); // 테마 푸터 포함
?>