<?php
// =========================================================================
// [백엔드 영역] PHP 8.0 + 영구 RAG + 표 깨짐 방지 + 토큰 + 모델 선택 기능
// =========================================================================

set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('default_socket_timeout', 300);
error_reporting(E_ALL);
ini_set('display_errors', 0);

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    // [기능 1] 실시간 파일 업로드
    if (isset($_FILES['async_file']) && $_FILES['async_file']['error'] === UPLOAD_ERR_OK) {
        $filename = basename($_FILES['async_file']['name']);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['async_file']['tmp_name'], $target_path)) {
            echo json_encode(['success' => true, 'filename' => $filename]);
        } else {
            echo json_encode(['success' => false, 'error' => '파일 저장 실패 (권한 확인)']);
        }
        exit;
    }

    // [기능 2] 파일 삭제
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $filename = basename($_POST['filename']);
        $target_path = $upload_dir . $filename;
        if (file_exists($target_path)) {
            unlink($target_path);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // [기능 3] 문서 생성
    if (isset($_POST['action']) && $_POST['action'] === 'generate') {
        $api_key = 'sk-br-v1-0a5e6037549c408ea2db7e931e443330_gxYe7DuUhm1YdkaFboD0666nJP_2c099U8gdk5mJ3yk'; 
        
        $model_name = $_POST['model'] ?? 'upstage/solar-pro3';
        $prompt = $_POST['prompt'] ?? '';
        
        if (empty($prompt)) {
            echo json_encode(['success' => false, 'error' => '프롬프트를 입력해주세요.']); exit;
        }

        $document_text = '';
        $files = glob($upload_dir . '*.*');
        foreach ($files as $i => $filepath) {
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $filename = basename($filepath);
            $extracted_text = '';

            if ($ext === 'hml') {
                $hml_content = file_get_contents($filepath);
                preg_match_all('/<CHAR>(.*?)<\/CHAR>/s', $hml_content, $matches);
                $extracted_text = !empty($matches[1]) ? implode("\n", $matches[1]) : strip_tags($hml_content);
            } elseif ($ext === 'txt') {
                $extracted_text = file_get_contents($filepath);
            }

            if ($extracted_text) {
                $document_text .= "\n\n[첨부문서 " . ($i + 1) . ": {$filename}]\n" . $extracted_text . "\n[첨부문서 " . ($i + 1) . " 끝]\n";
            }
        }

        $system_prompt = "당신은 영암군청 기획감사과에 근무하는 최고의 행정 문서 작성 AI 비서입니다.\n";
        $system_prompt .= "문서 내용 중 핵심적인 수치나 비교 데이터는 반드시 마크다운 표(| 항목 | 내용 |) 형식으로 정리해서 작성해 주세요.\n";
        
        if ($document_text !== '') {
            $system_prompt .= "\n=========================================\n";
            $system_prompt .= "[중요 지시사항]\n사용자가 제공한 아래 [참고 문서 데이터]의 내용과 수치를 기반으로 분석하고 문서를 작성하세요.\n\n";
            $system_prompt .= "[참고 문서 데이터 시작]\n";
            
            // OpenAI의 128k 토큰 제한을 넘지 않도록 한글 기준 안전한 30,000자로 컷팅합니다.
            $system_prompt .= mb_substr($document_text, 0, 30000, 'UTF-8'); 
            $system_prompt .= "\n[참고 문서 데이터 끝]\n=========================================\n\n";
        }

        $system_prompt .= "응답은 반드시 아래와 같이 [배경]과 [문제점] 태그로 구분하여 작성하세요. 절대 JSON 형식으로 묶지 마세요.\n";
        $system_prompt .= "[배경]\n(추진 배경 및 목적 내용...)\n\n[문제점]\n(현황 분석 및 문제점 내용, 표 포함...)";

        $data = [
            'model' => $model_name,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.5 
        ];

        $ch = curl_init('https://api.bizrouter.ai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) { echo json_encode(['success' => false, 'error' => "통신 오류: {$err}"]); exit; }
        if ($http_code !== 200) { 
            $error_details = json_decode($response, true);
            $error_msg = isset($error_details['error']['message']) ? $error_details['error']['message'] : $response;
            echo json_encode(['success' => false, 'error' => "API 에러 ({$http_code})\n이유: {$error_msg}"]); 
            exit; 
        }

        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            $content_str = trim($result['choices'][0]['message']['content']);
            $bg_text = '';
            $prob_text = '';

            if (preg_match('/\[배경\](.*?)(?:\[문제점\]|$)/is', $content_str, $m1)) {
                $bg_text = trim($m1[1]);
            }
            if (preg_match('/\[문제점\](.*)/is', $content_str, $m2)) {
                $prob_text = trim($m2[1]);
            }

            if (!$bg_text && !$prob_text) {
                $bg_text = $content_str;
            }

            $usage = isset($result['usage']) ? $result['usage'] : null;

            echo json_encode([
                'success' => true, 
                'data' => ['background' => $bg_text, 'problem' => $prob_text],
                'usage' => $usage,
                'used_model' => $result['model'] ?? $model_name
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'API 결과 없음']);
        }
        exit;
    }
}

$existing_files = [];
if (is_dir($upload_dir)) {
    foreach(glob($upload_dir . '*.*') as $file) {
        $existing_files[] = basename($file);
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI 기안 작성실 (다중 모델 완벽 지원)</title>
<style>
  body { margin: 0; padding: 0; font-family: 'Pretendard', 'Malgun Gothic', sans-serif; background-color: #f0f2f5; color: #333; }
  .container { display: flex; height: 100vh; flex-direction: column; }
  .header { background-color: #1a2b49; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f1a2e; }
  .logo { font-size: 20px; font-weight: bold; }
  .main-content { flex: 1; display: flex; padding: 25px; gap: 25px; box-sizing: border-box; overflow: hidden; }
  
  .ai-panel { flex: 0.35; background-color: #f8f9fa; border: 1px solid #d1dce5; border-radius: 10px; padding: 25px; display: flex; flex-direction: column; overflow-y: auto;}
  .panel-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #1a2b49; }
  
  .model-select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; margin-bottom: 15px; background-color: #fff; color: #333; cursor: pointer; }
  .model-select:focus { outline: none; border-color: #3b82f6; }

  textarea { width: 100%; height: 150px !important; min-height: 150px; flex-shrink: 0; padding: 15px; border: 1px solid #ccc; border-radius: 6px; resize: vertical; font-size: 15px; box-sizing: border-box; margin-bottom:15px; line-height: 1.5;}
  
  .drop-zone { background-color: #fff; border: 2px dashed #3b82f6; border-radius: 6px; padding: 20px; margin-bottom: 20px; text-align: center; cursor: pointer; transition: background 0.3s; }
  .drop-zone.dragover { background-color: #eef2ff; border-color: #1d4ed8; }
  .drop-zone label { font-weight: bold; color: #3b82f6; pointer-events: none;}
  .rag-notice { font-size: 12px; color: #888; margin-top: 5px; pointer-events: none;}
  
  .file-list { margin-top: 15px; font-size: 13px; color: #1a2b49; text-align: left; background: #eef2ff; padding: 10px; border-radius: 6px; border: 1px solid #c7d2fe; display: none; margin-bottom:15px;}
  .file-item { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dashed #c7d2fe;}
  .file-item:last-child { border-bottom: none; }
  .delete-btn { color: #dc2626; cursor: pointer; font-weight: bold; font-size: 11px; background: #fee2e2; padding: 2px 6px; border-radius: 4px; }
  .delete-btn:hover { background: #fca5a5; }

  .generate-btn { background: linear-gradient(to right, #3b82f6, #1a2b49); color: white; border: none; padding: 14px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px; transition: background 0.3s; margin-top:auto;}
  .generate-btn:disabled { background: #ccc; cursor: not-allowed; }
  
  .token-info { margin-top: 15px; font-size: 13px; color: #4b5563; text-align: center; background: #e5e7eb; padding: 10px; border-radius: 6px; display: none; font-weight: bold; line-height: 1.4;}
  
  .editor-panel { flex: 0.65; background-color: #fff; border: 1px solid #dce1e6; border-radius: 10px; padding: 30px; display: flex; flex-direction: column; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
  .doc-header h2 { margin: 0; font-size: 26px; color: #333; text-align: center; border-bottom: 2px solid #333; padding-bottom: 25px; margin-bottom: 20px;}
  .section-title { font-weight: bold; font-size: 17px; margin-top: 30px; margin-bottom: 12px; color: #1a2b49; }
  
  .ai-typing strong { color: #1a2b49; font-weight: 800; background-color: #e8f0fe; padding: 2px 4px; border-radius: 4px; }
  .ai-typing { background-color: #f8f9fa; padding: 18px; border-radius: 6px; border-left: 4px solid #3b82f6; white-space: pre-wrap; min-height: 50px; line-height: 1.7;}
  .ai-typing table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 14px; text-align: center; background: #fff;}
  .ai-typing th { border: 1px solid #c0c0c0; padding: 10px; background-color: #e8f0fe; font-weight: bold; color:#1a2b49; }
  .ai-typing td { border: 1px solid #c0c0c0; padding: 10px; }

  .button-group { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eaeaea; display: flex; justify-content: flex-end; gap: 10px; }
  .btn { padding: 12px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; border: none; transition: background 0.3s; }
  .btn-hml { background: #2563eb; color: white; }
  .btn-hml:hover { background: #1d4ed8; }
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <div class="logo">영암군 사업관리 통합시스템 (다중 AI 모델 지원)</div>
  </div>

  <div class="main-content">
    <div class="ai-panel">
      <div class="panel-title">🤖 AI 기안 작성실</div>
      
      <label style="font-weight:bold; margin-bottom:8px; font-size:14px; color:#555; display:block;">AI 모델 선택:</label>
      <select id="modelSelect" class="model-select">
          <option value="upstage/solar-pro3" selected>Upstage: Solar Pro 3 Beta (추천)</option>
          <option value="openai/gpt-4o-mini">OpenAI: GPT-4o Mini (가성비/속도)</option>
          <option value="openai/gpt-5.1">OpenAI: GPT-5.1</option>
      </select>

      <label style="font-weight:bold; margin-bottom:10px; font-size:14px; color:#555;">프롬프트 입력:</label>
      <textarea id="promptInput">첨부한 공고문 파일들의 내용을 바탕으로, 기존 사업과 신규 사업의 비교 수치를 표 형식으로 만들어줘.</textarea>
      
      <div class="drop-zone" id="dropZone">
        <label>📎 지식베이스 파일 업로드 (드래그 앤 드롭)</label>
        <div class="rag-notice">등록된 파일은 영구 보존되어 AI가 참고합니다. (무제한)</div>
        <input type="file" id="fileInput" accept=".hml, .txt" multiple style="display:none;">
      </div>
      
      <div class="file-list" id="fileList"></div>

      <button id="generateBtn" class="generate-btn">🚀 문서 자동 생성 시작</button>
      
      <div id="tokenUsage" class="token-info"></div>
    </div>

    <div class="editor-panel">
      <div class="doc-header">
        <h2>[ 2026년도 청년 주거 지원 신규사업 계획(안) ]</h2>
      </div>

      <div class="doc-body" style="overflow-y:auto; flex:1; padding-right: 10px;">
        <div class="section-title">1. 추진 배경 및 목적</div>
        <div id="outputBackground" class="ai-typing" style="color:#888;">대기 중...</div>

        <div class="section-title">2. 현황 분석 및 문제점</div>
        <div id="outputProblem" class="ai-typing" style="color:#888;">대기 중...</div>
      </div>

      <div class="button-group">
        <button id="downloadHmlBtn" class="btn btn-hml">📄 HML로 열어보기 (다운로드)</button>
      </div>
    </div>
  </div>
</div>

<script>
let uploadedFiles = <?php echo json_encode($existing_files); ?>;

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileList = document.getElementById('fileList');

renderFileList();

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.classList.remove('dragover'); });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

function handleFiles(files) {
    Array.from(files).forEach(file => {
        if (file.name.endsWith('.hml') || file.name.endsWith('.txt')) {
            let formData = new FormData();
            formData.append('async_file', file);
            
            fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if(!uploadedFiles.includes(data.filename)) {
                        uploadedFiles.push(data.filename);
                    }
                    renderFileList();
                } else {
                    alert(data.error);
                }
            });
        }
    });
}

function deleteFile(filename) {
    if(!confirm(`'${filename}' 파일을 삭제하시겠습니까?`)) return;
    let formData = new FormData();
    formData.append('action', 'delete');
    formData.append('filename', filename);
    
    fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            uploadedFiles = uploadedFiles.filter(f => f !== filename);
            renderFileList();
        }
    });
}

function renderFileList() {
    if (uploadedFiles.length === 0) {
        fileList.style.display = 'none';
        fileList.innerHTML = '';
    } else {
        fileList.style.display = 'block';
        fileList.innerHTML = '<strong>📚 등록된 RAG 지식베이스</strong><br>' + 
            uploadedFiles.map(f => `
                <div class="file-item">
                    <span>📄 ${f}</span>
                    <span class="delete-btn" onclick="deleteFile('${f}')">삭제</span>
                </div>
            `).join('');
    }
}

let currentBackgroundText = "";
let currentProblemText = "";

document.getElementById('generateBtn').addEventListener('click', function() {
    var promptText = document.getElementById('promptInput').value;
    var selectedModel = document.getElementById('modelSelect').value; 
    var btn = this;
    var bgOutput = document.getElementById('outputBackground');
    var probOutput = document.getElementById('outputProblem');
    var tokenUsage = document.getElementById('tokenUsage');

    if (!promptText.trim()) { alert('프롬프트를 입력해주세요.'); return; }

    btn.innerHTML = '⏳ AI 분석 및 문서 생성 중...'; btn.disabled = true;
    bgOutput.innerHTML = '생성 중...'; probOutput.innerHTML = '생성 중...';
    bgOutput.style.color = '#333'; probOutput.style.color = '#333';
    tokenUsage.style.display = 'none'; 

    var formData = new FormData();
    formData.append('action', 'generate');
    formData.append('prompt', promptText);
    formData.append('model', selectedModel);

    fetch('', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = '🚀 문서 자동 생성 시작'; btn.disabled = false;
        if (data.error) { alert('🚨 오류 발생 🚨\n\n' + data.error); return; }

        if (data.success) {
            currentBackgroundText = data.data.background;
            currentProblemText = data.data.problem;
            
            typeWriterHTML(bgOutput, parseMarkdownAndTable(currentBackgroundText));
            typeWriterHTML(probOutput, parseMarkdownAndTable(currentProblemText));
            
            if (data.usage) {
                tokenUsage.style.display = 'block';
                tokenUsage.innerHTML = `<span style="color:#d97706">사용 모델: ${data.used_model}</span><br>
                                        📊 RAG 입력: <span style="color:#2563eb">${data.usage.prompt_tokens.toLocaleString()}</span> 토큰 | 출력: <span style="color:#16a34a">${data.usage.completion_tokens.toLocaleString()}</span> 토큰<br>
                                        <span style="font-size:11px; color:#888;">(총 소모량: ${data.usage.total_tokens.toLocaleString()} 토큰)</span>`;
            }
        }
    })
    .catch(error => {
        console.error(error);
        btn.innerHTML = '🚀 문서 자동 생성 시작'; btn.disabled = false;
        alert('서버 응답 처리 오류가 발생했습니다.');
    });
});

function parseMarkdownAndTable(text) {
    if (!text) return "";
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    let lines = text.split('\n');
    let inTable = false;
    let htmlLines = [];
    
    for (let line of lines) {
        let trimmed = line.trim();
        if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
            if (trimmed.includes('---')) continue; 
            let cells = trimmed.split('|').map(c => c.trim()).filter((c, i, arr) => i !== 0 && i !== arr.length - 1);
            if (!inTable) {
                htmlLines.push('<table><tr>' + cells.map(c => `<th>${c}</th>`).join('') + '</tr>');
                inTable = true;
            } else {
                htmlLines.push('<tr>' + cells.map(c => `<td>${c}</td>`).join('') + '</tr>');
            }
        } else {
            if (inTable) { htmlLines.push('</table>'); inTable = false; }
            htmlLines.push(line);
        }
    }
    if (inTable) htmlLines.push('</table>');
    return htmlLines.join('\n');
}

function typeWriterHTML(element, htmlText) {
    if(!htmlText) return;
    element.innerHTML = '';
    let currentHTML = '';
    let i = 0; const speed = 10; 
    
    function type() {
        if (i < htmlText.length) {
            let char = htmlText.charAt(i);
            if (char === '<') {
                let endIndex = htmlText.indexOf('>', i);
                if (endIndex !== -1) {
                    currentHTML += htmlText.substring(i, endIndex + 1);
                    i = endIndex + 1;
                } else { currentHTML += char; i++; }
            } else { currentHTML += char; i++; }
            
            element.innerHTML = currentHTML; 
            setTimeout(type, speed);
        }
    }
    type();
}

function buildHmlSection(title, content) {
    let hml = `<P ParaShape="0" Style="0"><TEXT CharShape="0"><CHAR>${title}</CHAR></TEXT></P>`;
    if(!content) return hml;
    let lines = content.replace(/\*\*/g, "").split('\n');
    let inTable = false;
    let tableData = [];
    for (let line of lines) {
        let trimmed = line.trim();
        if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
            if (trimmed.includes('---')) continue;
            let cells = trimmed.split('|').map(c => c.trim()).filter((c, i, arr) => i !== 0 && i !== arr.length - 1);
            tableData.push(cells);
            inTable = true;
        } else {
            if (inTable) {
                hml += generateHmlTable(tableData);
                tableData = []; inTable = false;
            }
            if (trimmed !== '') {
                hml += `<P ParaShape="0" Style="0"><TEXT CharShape="0"><CHAR>${escapeXml(trimmed)}</CHAR></TEXT></P>`;
            } else {
                hml += `<P ParaShape="0" Style="0"><TEXT CharShape="0"><CHAR></CHAR></TEXT></P>`;
            }
        }
    }
    if (inTable) hml += generateHmlTable(tableData);
    return hml;
}

function generateHmlTable(data) {
    let rows = data.length;
    let cols = rows > 0 ? data[0].length : 0;
    if(cols === 0) return "";
    let cellWidth = Math.floor(40000 / cols);
    let xml = `<P ParaShape="0" Style="0"><TEXT CharShape="0">`;
    xml += `<TABLE BorderFill="2" CellSpacing="0" ColCount="${cols}" RowCount="${rows}" PageBreak="Cell" RepeatHeader="true">`;
    xml += `<SHAPEOBJECT InstId="${Math.floor(Math.random()*1000000)}" Lock="false" NumberingType="Table" ZOrder="0">`;
    xml += `<SIZE Height="2000" HeightRelTo="Absolute" Protect="false" Width="40000" WidthRelTo="Absolute"/>`;
    xml += `<POSITION AffectLSpacing="false" AllowOverlap="false" FlowWithText="true" HoldAnchorAndSO="false" HorzAlign="Left" HorzOffset="0" HorzRelTo="Para" TreatAsChar="true" VertAlign="Top" VertOffset="0" VertRelTo="Para"/>`;
    xml += `<OUTSIDEMARGIN Bottom="283" Left="283" Right="283" Top="283"/>`;
    xml += `</SHAPEOBJECT><INSIDEMARGIN Bottom="141" Left="141" Right="141" Top="141"/>`;
    for(let r=0; r<rows; r++) {
        xml += `<ROW>`;
        for(let c=0; c<cols; c++) {
            let cellText = escapeXml(data[r][c] || "");
            let isHeader = (r === 0) ? "true" : "false"; 
            xml += `<CELL BorderFill="2" ColAddr="${c}" ColSpan="1" Dirty="false" Editable="false" HasMargin="false" Header="${isHeader}" Height="2000" Protect="false" RowAddr="${r}" RowSpan="1" Width="${cellWidth}">`;
            xml += `<CELLMARGIN Bottom="141" Left="510" Right="510" Top="141"/>`;
            xml += `<PARALIST LineWrap="Break" LinkListID="0" LinkListIDNext="0" TextDirection="0" VertAlign="Top">`;
            xml += `<P ParaShape="0" Style="0"><TEXT CharShape="0"><CHAR>${cellText}</CHAR></TEXT></P></PARALIST></CELL>`;
        }
        xml += `</ROW>`;
    }
    xml += `</TABLE><CHAR></CHAR></TEXT></P>`;
    return xml;
}

document.getElementById('downloadHmlBtn').addEventListener('click', function() {
    if (!currentBackgroundText && !currentProblemText) {
        alert('먼저 AI 문서를 생성해주세요!'); return;
    }
    let hmlContent = `<P ParaShape="0" Style="0"><TEXT CharShape="0"><CHAR>[ 2026년도 청년 주거 지원 신규사업 계획(안) ]</CHAR></TEXT></P><P ParaShape="0" Style="0"><TEXT CharShape="0"><CHAR></CHAR></TEXT></P>`;
    hmlContent += buildHmlSection("1. 추진 배경 및 목적", currentBackgroundText);
    hmlContent += `<P ParaShape="0" Style="0"><TEXT CharShape="0"><CHAR></CHAR></TEXT></P>`;
    hmlContent += buildHmlSection("2. 현황 분석 및 문제점", currentProblemText);

    let hmlTemplate = '<' + '?xml version="1.0" encoding="UTF-8" standalone="no" ?>\n' +
`<HWPML Style="embed" SubVersion="10.0.0.0" Version="2.91">
  <HEAD SecCnt="1">
    <MAPPINGTABLE>
      <FACENAMELIST>
        <FONTFACE Count="1" Lang="Hangul"><FONT Id="0" Name="맑은 고딕" Type="ttf"/></FONTFACE>
        <FONTFACE Count="1" Lang="Latin"><FONT Id="0" Name="맑은 고딕" Type="ttf"/></FONTFACE>
        <FONTFACE Count="1" Lang="Hanja"><FONT Id="0" Name="맑은 고딕" Type="ttf"/></FONTFACE>
        <FONTFACE Count="1" Lang="Japanese"><FONT Id="0" Name="맑은 고딕" Type="ttf"/></FONTFACE>
        <FONTFACE Count="1" Lang="Other"><FONT Id="0" Name="맑은 고딕" Type="ttf"/></FONTFACE>
        <FONTFACE Count="1" Lang="Symbol"><FONT Id="0" Name="맑은 고딕" Type="ttf"/></FONTFACE>
        <FONTFACE Count="1" Lang="User"><FONT Id="0" Name="맑은 고딕" Type="ttf"/></FONTFACE>
      </FACENAMELIST>
      <BORDERFILLLIST Count="2">
        <BORDERFILL BackSlash="0" BreakCellSeparateLine="0" CenterLine="0" CounterBackSlash="0" CounterSlash="0" CrookedSlash="0" Id="1" Shadow="false" Slash="0" ThreeD="false"><LEFTBORDER Type="None" Width="0.1mm"/><RIGHTBORDER Type="None" Width="0.1mm"/><TOPBORDER Type="None" Width="0.1mm"/><BOTTOMBORDER Type="None" Width="0.1mm"/></BORDERFILL>
        <BORDERFILL BackSlash="0" BreakCellSeparateLine="0" CenterLine="0" CounterBackSlash="0" CounterSlash="0" CrookedSlash="0" Id="2" Shadow="false" Slash="0" ThreeD="false"><LEFTBORDER Type="Solid" Width="0.12mm"/><RIGHTBORDER Type="Solid" Width="0.12mm"/><TOPBORDER Type="Solid" Width="0.12mm"/><BOTTOMBORDER Type="Solid" Width="0.12mm"/></BORDERFILL>
      </BORDERFILLLIST>
      <CHARSHAPELIST Count="1"><CHARSHAPE BorderFillId="1" Height="1000" Id="0" ShadeColor="4294967295" SymMark="0" TextColor="0" UseFontSpace="false" UseKerning="false"><FONTID Hangul="0" Hanja="0" Japanese="0" Latin="0" Other="0" Symbol="0" User="0"/><RATIO Hangul="100" Hanja="100" Japanese="100" Latin="100" Other="100" Symbol="100" User="100"/><CHARSPACING Hangul="0" Hanja="0" Japanese="0" Latin="0" Other="0" Symbol="0" User="0"/><RELSIZE Hangul="100" Hanja="100" Japanese="100" Latin="100" Other="100" Symbol="100" User="100"/><CHAROFFSET Hangul="0" Hanja="0" Japanese="0" Latin="0" Other="0" Symbol="0" User="0"/></CHARSHAPE></CHARSHAPELIST>
      <TABDEFLIST Count="1"><TABDEF AutoTabLeft="false" AutoTabRight="false" Id="0"/></TABDEFLIST>
      <PARASHAPELIST Count="1"><PARASHAPE Align="Justify" Id="0" TabDef="0"><PARAMARGIN Indent="0" Left="0" LineSpacing="160" LineSpacingType="Percent" Next="0" Prev="0" Right="0"/><PARABORDER BorderFill="1" Connect="false" IgnoreMargin="false"/></PARASHAPE></PARASHAPELIST>
      <STYLELIST Count="1"><STYLE CharShape="0" EngName="Normal" Id="0" LangId="1042" LockForm="0" Name="바탕글" NextStyle="0" ParaShape="0" Type="Para"/></STYLELIST>
    </MAPPINGTABLE>
  </HEAD>
  <BODY>
    <SECTION Id="0">
      ${hmlContent}
    </SECTION>
  </BODY>
</HWPML>`;

    let blob = new Blob([hmlTemplate], { type: "application/x-hwpml" });
    let url = URL.createObjectURL(blob);
    let a = document.createElement("a");
    a.href = url;
    a.download = "2026_청년_주거_지원_기안.hml";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
});

function escapeXml(unsafe) {
    return unsafe.replace(/[<>&'"]/g, function (c) {
        switch (c) {
            case '<': return '&lt;'; case '>': return '&gt;';
            case '&': return '&amp;'; case '\'': return '&apos;'; case '"': return '&quot;';
        }
    });
}
</script>
</body>
</html>