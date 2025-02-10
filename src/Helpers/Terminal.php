<?php

namespace WahyuLingu\AutoWAFu\Helpers;

use Closure;
use Exception;

use function Laravel\Prompts\clear;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

class Terminal
{
    public static function showLogo()
    {
        $logo = 'ICAgX19fICAgICAgIF9fICAgICBfICAgICAgX19fX18gICBfX19fIC';
        $logo .= 'AgIAogIC8gXyB8X18gX18vIC9fX19ffCB8IC98IC8gLyBfIHwgLyBf';
        $logo .= 'Xy9fIF9fCiAvIF9fIC8gLy8gLyBfXy8gXyBcIHwvIHwvIC8gX18gfC';
        $logo .= '8gXy8vIC8vIC8KL18vIHxfXF8sXy9cX18vXF9fXy9fXy98X18vXy8g';
        $logo .= 'fF8vXy8gIFxfLF8v';

        info(base64_decode($logo));
    }

    public static function spin(Closure $callback, string $message = '', bool $clear = false): mixed
    {
        if ($clear) {
            return self::clear(fn () => spin($callback, $message));
        }

        return spin($callback, $message);
    }

    public static function clear($value, ?callable $callback = null)
    {
        clear();

        self::showLogo();

        try {
            if (is_callable($value)) {

                if ($callback) {
                    return call_user_func($callback, call_user_func($value));
                }

                return call_user_func($value);

            }

            if ($callback) {
                return call_user_func($callback, $value);
            }

            return $value;
        } catch (Exception $e) {
            self::clear(fn () => error($e->getMessage()));
        }
    }
}
