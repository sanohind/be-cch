<?php

$dir = __DIR__ . '/app/Http/Controllers/Api/';
$files = glob($dir . 'Block*Controller.php');

foreach ($files as $file) {
    preg_match('/Block(\d+)/', basename($file), $m);
    if (!$m) continue;
    $blockNo = $m[1];
    
    $content = file_get_contents($file);
    
    // Check if already injected
    if (strpos($content, 'checkCanViewDraft') !== false) {
        continue;
    }

    $find = "public function show(\$id): JsonResponse\n    {";
    $find2 = "public function show(\$id): JsonResponse\r\n    {";
    
    $injection = "
        \$cch = \\App\\Models\\Cch::find(\$id);
        if (\$cch) {
            \$sphereUser = request()->attributes->get('sphere_user');
            if (\$cch->b{$blockNo}_status === 'draft' && !\\App\\Services\\WorkflowService::checkCanViewDraft(\$cch, \$sphereUser, {$blockNo})) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }
";

    if (strpos($content, $find) !== false) {
        $content = str_replace($find, $find . $injection, $content);
        file_put_contents($file, $content);
        echo "Updated " . basename($file) . "\n";
    } elseif (strpos($content, $find2) !== false) {
        $content = str_replace($find2, $find2 . $injection, $content);
        file_put_contents($file, $content);
        echo "Updated " . basename($file) . "\n";
    } else {
        echo "Failed to match signature in " . basename($file) . "\n";
    }
}
