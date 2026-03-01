<?php
#
#   path resolution
#
$requestUri   = $_SERVER['REQUEST_URI'];
$relativePath = parse_url($requestUri, PHP_URL_PATH);
$relativePath = urldecode($relativePath);
$relativePath = str_replace(['../', '..\\'], '', $relativePath);
$relativePath = '/' . trim($relativePath, '/');

$rootDir = __DIR__;

if ($relativePath === '/' || $relativePath === '') {
    $currentDir = $rootDir;
} else {
    $currentDir = $rootDir . $relativePath;
}

$currentDir = realpath($currentDir);


#
#   security & routing
#
if ($currentDir === false || strpos($currentDir, $rootDir) !== 0) {
    http_response_code(403);
    die('Access denied');
}

if (is_file($currentDir)) {
    return false;
}

if (!is_dir($currentDir)) {
    http_response_code(404);
    die('Not found');
}


#
#   scan directory
#
$items = scandir($currentDir);
$files = [];
$dirs  = [];

foreach ($items as $item) {
    if ($item === '.' || $item === 'index.php') continue;
    
    $fullPath = $currentDir . '/' . $item;
    $webPath = rtrim($relativePath, '/') . '/' . $item;
    
    if (is_dir($fullPath)) {
        $dirs[] = [
            'name' => $item,
            'path' => $webPath,
            'type' => 'dir'
        ];
    } else {
        $size = filesize($fullPath);
        $files[] = [
            'name' => $item,
            'path' => $webPath,
            'type' => 'file',
            'size' => $size,
            'modified' => filemtime($fullPath)
        ];
    }
}


#
#   sort & merge
#
sort($dirs);
sort($files);
$allItems = array_merge($dirs, $files);


#
#   detect cli/api request
#
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$accept    = $_SERVER['HTTP_ACCEPT'] ?? '';
$isCli     = (
    stripos($userAgent, 'curl')    !== false ||
    stripos($userAgent, 'wget')    !== false ||
    stripos($userAgent, 'python')  !== false ||
    stripos($userAgent, 'requests') !== false ||
    (stripos($accept, 'text/html') === false && stripos($accept, '*/*') !== false)
);


#
#   base url
#
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$baseUrl  = $protocol . '://' . $host;


#
#   cli mode: plain text output
#
if ($isCli) {
    header('Content-Type: text/plain');
    
    function listAllFiles($dir, $prefix = '') {
        $items  = scandir($dir);
        $result = [];
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'index.php') continue;
            
            $fullPath = $dir . '/' . $item;
            $webPath  = rtrim($prefix, '/') . '/' . $item;
            
            if (is_dir($fullPath)) {
                $result = array_merge($result, listAllFiles($fullPath, $webPath));
            } else {
                $result[] = $webPath;
            }
        }
        
        return $result;
    }
    
    $allFiles = listAllFiles($currentDir, $relativePath === '/' ? '' : $relativePath);
    sort($allFiles);
    
    foreach ($allFiles as $file) {
        echo $file . "\n";
    }
    exit;
}


#
#   helper: format file size
#
function formatSize($bytes) {
    if ($bytes < 1024)       return $bytes . ' B';
    if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1073741824, 1) . ' GB';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index of <?= htmlspecialchars($relativePath ?: '/') ?></title>
    <style>
        /*
        *   base reset
        */
        * {
            margin:     0;
            padding:    0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background:  #0a0a0a;
            color:       #e4e4e7;
            line-height: 1.6;
            overflow:    hidden;
        }
        
        
        /*
        *   scrollbar styling
        */
        ::-webkit-scrollbar {
            width: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #71717a;
            transition: background 0.2s;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1aa;
        }
        
        * {
            scrollbar-width: thin;
            scrollbar-color: #71717a #0a0a0a;
        }
        
        
        /*
        *   layout
        */
        .container {
            width:   100%;
            height:  100vh;
            margin:  0;
            padding: 0;
        }
        
        .items {
            background:  #18181b;
            overflow-y:  scroll;
            height:      100vh;
        }
        
        
        /*
        *   item rows
        */
        .item-wrapper {
            position:      relative;
            border-bottom: 1px solid #27272a;
        }
        
        .item-wrapper:last-child {
            border-bottom: none;
        }
        
        .item {
            display:         flex;
            align-items:     center;
            padding:         16px 20px;
            text-decoration: none;
            color:           inherit;
            transition:      all 0.2s ease;
            cursor:          pointer;
            position:        relative;
        }
        
        .item-wrapper:hover .item {
            background: #27272a;
        }
        
        
        /*
        *   item elements
        */
        .icon {
            width:         40px;
            height:        40px;
            display:       flex;
            align-items:   center;
            justify-content: center;
            margin-right:  16px;
            font-size:     20px;
            flex-shrink:   0;
            filter:        grayscale(100%) brightness(0.6);
        }
        
        .item-info {
            flex:           1;
            min-width:      0;
            pointer-events: none;
        }
        
        .item-name {
            font-size:      15px;
            font-weight:    500;
            color:          #fafafa;
            display:        block;
            margin-bottom:  4px;
            overflow:       hidden;
            text-overflow:  ellipsis;
            white-space:    nowrap;
        }
        
        .item-wrapper:hover .item-name {
            color: #d4d4d8;
        }
        
        .item-meta {
            font-size: 13px;
            color:     #a1a1aa;
        }
        
        .open-link {
            position:        absolute;
            right:           20px;
            top:             16px;
            color:           #52525b;
            font-size:       18px;
            pointer-events:  all;
            z-index:         10;
            transition:      color 0.15s;
            text-decoration: none;
            padding:         4px 8px;
            filter:          grayscale(100%);
        }
        
        .open-link:hover {
            color: #71717a;
        }
        
        
        /*
        *   commands (hover reveal)
        */
        .commands {
            max-height: 0;
            overflow:   hidden;
            opacity:    0;
            transition: all 0.2s ease;
            padding:    0 20px;
            background: #27272a;
            position:   relative;
        }
        
        .item-wrapper:hover .commands {
            max-height: 200px;
            opacity:    1;
            padding:    0 20px 16px 20px;
        }
        
        .command-row {
            display:       flex;
            align-items:   center;
            gap:           12px;
            margin-bottom: 12px;
            position:      relative;
        }
        
        .command-row:last-child {
            margin-bottom: 0;
        }
        
        .command-label {
            font-size:      12px;
            font-weight:    600;
            color:          #a1a1aa;
            text-transform: uppercase;
            width:          50px;
            flex-shrink:    0;
        }
        
        .command-text {
            flex:           1;
            font-family:    "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, monospace;
            font-size:      12px;
            color:          #d4d4d8;
            background:     #18181b;
            padding:        8px 40px 8px 12px;
            overflow-x:     auto;
            white-space:    nowrap;
            position:       relative;
            cursor:         pointer;
            transition:     background 0.15s;
            pointer-events: all;
        }
        
        .command-text:hover {
            background: #27272a;
        }
        
        .copy-btn {
            position:        absolute;
            right:           8px;
            top:             50%;
            transform:       translateY(-50%);
            background:      transparent;
            color:           #52525b;
            border:          none;
            padding:         4px;
            font-size:       16px;
            cursor:          pointer;
            transition:      all 0.15s;
            pointer-events:  none;
            display:         flex;
            align-items:     center;
            justify-content: center;
            filter:          grayscale(100%);
        }
        
        .command-text:hover .copy-btn {
            color:  #71717a;
            filter: grayscale(100%);
        }
        
        .copy-btn.copied {
            color:  #71717a;
            filter: grayscale(100%);
        }
        
        
        /*
        *   empty state
        */
        .empty {
            text-align: center;
            padding:    60px 20px;
            color:      #71717a;
        }
        
        
        /*
        *   responsive
        */
        @media (max-width: 768px) {
            .item {
                padding: 12px 16px;
            }
            
            .item-meta {
                font-size: 12px;
            }
            
            .command-row {
                flex-direction: column;
                align-items:    stretch;
                gap:            8px;
            }
            
            .command-text {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="items">
            <?php if ($relativePath !== '' && $relativePath !== '/'): ?>
            <div class="item-wrapper">
                <a href="../" class="item">
                    <div class="icon">📁</div>
                    <div class="item-info">
                        <span class="item-name">..</span>
                        <div class="item-meta">Parent directory</div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (empty($allItems)): ?>
            <div class="empty">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <div>This directory is empty</div>
            </div>
            <?php else: ?>
                <?php foreach ($allItems as $item): ?>
                <div class="item-wrapper <?= $item['type'] === 'file' ? 'has-commands' : '' ?>">
                    <a href="<?= htmlspecialchars($item['path']) ?><?= $item['type'] === 'dir' ? '/' : '' ?>" class="item">
                        <div class="icon"><?= $item['type'] === 'dir' ? '📁' : '📄' ?></div>
                        <div class="item-info">
                            <span class="item-name">
                                <?= htmlspecialchars($item['name']) ?>
                            </span>
                            <div class="item-meta">
                                <?php if ($item['type'] === 'file'): ?>
                                    <?= formatSize($item['size']) ?> • <?= date('M j, Y g:i A', $item['modified']) ?>
                                <?php else: ?>
                                    Directory
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($item['type'] === 'file'): ?>
                        <a href="<?= htmlspecialchars($item['path']) ?>" class="open-link" onclick="event.stopPropagation()">↗</a>
                        <?php endif; ?>
                    </a>
                    <?php if ($item['type'] === 'file'): ?>
                    <div class="commands">
                        <div class="command-row">
                            <div class="command-label">curl</div>
                            <div class="command-text" onclick="copyToClipboard(this, 'curl -O <?= htmlspecialchars($baseUrl . $item['path']) ?>')">
                                curl -O <?= htmlspecialchars($baseUrl . $item['path']) ?>
                                <span class="copy-btn">📋</span>
                            </div>
                        </div>
                        <div class="command-row">
                            <div class="command-label">wget</div>
                            <div class="command-text" onclick="copyToClipboard(this, 'wget <?= htmlspecialchars($baseUrl . $item['path']) ?>')">
                                wget <?= htmlspecialchars($baseUrl . $item['path']) ?>
                                <span class="copy-btn">📋</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        //
        //  copy to clipboard
        //
        function copyToClipboard(element, text) {
            event.preventDefault();
            event.stopPropagation();
            
            const icon = element.querySelector('.copy-btn');
            
            navigator.clipboard.writeText(text).then(function() {
                const originalIcon = icon.textContent;
                icon.textContent = '✓';
                icon.classList.add('copied');
                
                setTimeout(function() {
                    icon.textContent = originalIcon;
                    icon.classList.remove('copied');
                }, 2000);
            }).catch(function(err) {
                icon.textContent = '✗';
                setTimeout(function() {
                    icon.textContent = '📋';
                }, 2000);
            });
        }
        
        
        //
        //  prevent row click in commands area
        //
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.commands').forEach(function(commands) {
                commands.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
        });
    </script>
</body>
</html>
