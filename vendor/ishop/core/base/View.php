<?php

namespace ishop\base;
use ishop\App;

class View {

    public $route;
    public $controller;
    public $model;
    public $view;
    public $prefix;
    public $layout;
    public $data = [];
    public $meta = [];

    public function __construct($route, $layout = '', $view = '', $meta){
        $this->route = $route;
        $this->controller = $route['controller'];
        $this->view = $view;
        $this->model = $route['controller'];
        $this->prefix = $route['prefix'];
        $this->meta = $meta;
        if($layout === false){
            $this->layout = false;
        }else{
            $this->layout = $layout ?: LAYOUT;
        }
    }

    public function render($data) {
        // Установка значений по умолчанию, если не переданы
        $data['shop_name'] = $data['shop_name'] ?? App::$app->getProperty('shop_name');
        $data['shop_img']  = $data['shop_img']  ?? App::$app->getProperty('shop_img');
        $data['shop_url']  = $data['shop_url']  ?? App::$app->getProperty('shop_url');
    
        if (is_array($data)) extract($data);
    
        $this->prefix = str_replace('\\', '/', $this->prefix);
        $viewFile = APP . "/views/" . TEMPLATE . "/{$this->prefix}{$this->controller}/{$this->view}.php";
    
        if (is_file($viewFile)) {
            ob_start();
            require_once $viewFile;
            $content = ob_get_clean();
        } else {
            throw new \Exception("Не найден вид {$viewFile}", 500);
        }
    
        if (false !== $this->layout) {
            $layoutFile = APP . "/views/" . TEMPLATE . "/layouts/{$this->layout}.php";
            if (is_file($layoutFile)) {
                require_once $layoutFile;
            } else {
                throw new \Exception("Не найден шаблон {$this->layout}", 500);
            }
        }
    }
    

    public function getMeta(){
        $meta = $this->meta;
    
        $output  = '<title>' . ($meta['title'] ?? '') . '</title>' . PHP_EOL;
        $output .= '<meta name="description" content="' . ($meta['desc'] ?? '') . '" />' . PHP_EOL;
        $output .= '<meta name="keywords" content="' . ($meta['keywords'] ?? '') . '" />' . PHP_EOL;
        $output .= '<meta property="og:type" content="' . ($meta['shop_name'] ?? 'website') . '" />' . PHP_EOL;
        $output .= '<meta property="og:locale" content="ru_RU" />' . PHP_EOL;
        $output .= '<meta property="og:title" content="' . ($meta['title'] ?? '') . '" />' . PHP_EOL;
        $output .= '<meta property="og:image" content="' . ($meta['shop_img'] ?? '/images/default-og.png') . '" />' . PHP_EOL;
        $output .= '<meta property="og:description" content="' . ($meta['desc'] ?? '') . '" />' . PHP_EOL;
        $output .= '<meta property="og:url" content="' . ($meta['shop_url'] ?? '/') . '" />' . PHP_EOL;
        $output .= '<link rel="canonical" href="' . ($meta['shop_url'] ?? '/') . '" />' . PHP_EOL;
    
        return $output;
    }

}