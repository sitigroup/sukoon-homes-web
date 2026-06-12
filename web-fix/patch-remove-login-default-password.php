<?php

$path = '/www/wwwroot/homes.sukoon.group/src/components/modal/LoginModal.jsx';
$content = file_get_contents($path);

$old = "        setShowPasswordInput(true);\n        setPhonePassword(\"123456\");\n        setShowLoader(false);";
$new = "        setShowPasswordInput(true);\n        setPhonePassword(\"\");\n        setShowLoader(false);";

if (strpos($content, $old) === false) {
    if (strpos($content, 'setPhonePassword("123456")') === false) {
        echo "Already patched or pattern missing\n";
        exit(0);
    }
    $content = str_replace('setPhonePassword("123456");', 'setPhonePassword("");', $content);
} else {
    $content = str_replace($old, $new, $content);
}

file_put_contents($path, $content);
echo "OK: removed default login password prefill\n";
