<?php

class CLI
{
    private static $formats = [
        'reset'  => "\e[0m",
        'bold'   => "\e[1m",
        'red'    => "\e[31m",
        'green'  => "\e[32m",
        'yellow' => "\e[33m",
        'blue'   => "\e[34m",
        'cyan'   => "\e[36m"
    ];

    public static function log(string $message, string $color = 'reset', bool $bold = false): void
    {
        $prefix = self::$formats[$color] ?? self::$formats['reset'];
        if ($bold && $color !== 'reset') {
            $prefix .= self::$formats['bold'];
        } elseif ($bold) {
            $prefix = self::$formats['bold'];
        }

        echo $prefix . $message . self::$formats['reset'] . "\n";
    }
}