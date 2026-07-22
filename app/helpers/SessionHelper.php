<?php

namespace app\helpers;

class SessionHelper
{
    /**
     * Чистим корзину от некорректных записей
     */
    public static function cleanCart(): void
    {
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $k => $v) {
                if (!is_array($v) || !isset($v['alias'], $v['qty'])) {
                    unset($_SESSION['cart'][$k]);
                }
            }
        }
    }

    /**
     * Чистим form_data от пустых полей
     */
    public static function cleanFormData(): void
    {
        if (!isset($_SESSION['form_data']) || !is_array($_SESSION['form_data'])) {
            unset($_SESSION['form_data']);
        } else {
            $_SESSION['form_data'] = array_filter($_SESSION['form_data'], static fn($v) => $v !== '');
        }
    }

    /**
     * Полная очистка сессий (кроме авторизации)
     */
    public static function cleanAll(): void
    {
        self::cleanCart();
        self::cleanFormData();
    }
}


