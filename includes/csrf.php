<?php

class CSRF
{
    const SESSION_KEY = '_csrf_token';

    

    public static function generate()
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    

    public static function input()
    {
        return '<input type="hidden" name="' . self::SESSION_KEY . '" value="' . htmlspecialchars(self::generate()) . '">';
    }

    

    public static function validate($token = null)
    {
        if ($token === null) {
            $token = $_POST[self::SESSION_KEY] ?? '';
        }

        if (!isset($_SESSION[self::SESSION_KEY]) || $_SESSION[self::SESSION_KEY] !== $token) {
            return false;
        }

        return true;
    }
}
