<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 「kobapp でログイン」が途中で失敗したとき。メッセージは主催者にそのまま見せる文。
 */
class KobappAuthException extends RuntimeException {}
