<?php

error_reporting(E_ALL);

function myExceptionHandler()
{
    echo "DB接続に失敗しました\n時間を空けてやり直してください";
}


set_exception_handler('myExceptionHandler');

set_error_handler(function ($level, $message, $file = '', $line = 0) {
    throw new ErrorException($message, 0, $level, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        myExceptionHandler();
    }
});