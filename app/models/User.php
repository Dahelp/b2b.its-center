<?php
namespace app\models;

use ishop\App;

class User extends AppModel {

    public $attributes = [
        'password' => '',
        'name' => '',
        'email' => '',
        'role' => '',
		'groups' => '',
		'telefon' => '',
		'newsletter' => '',
    ];

    public $rules = [
        'required' => [            
            ['name'],
            ['email'],            
        ],
        'email' => [
            ['email'],
        ],
        'lengthMin' => [
            ['password', 6],
        ]
    ];

    public function checkUnique(){
        $user = \R::findOne('user', 'email = ?', [$this->attributes['email']]);
        if($user){
            if($user->email == $this->attributes['email']){
                $this->errors['unique'][] = 'Этот email уже занят';
            }
            return false;
        }
        return true;
    }
	// Валидация пароля для входа
	public function validatePassword($password) {
		if (strlen($password) < 8) {
			$_SESSION['error'] = 'Пароль должен быть не менее 8 символов.';
			return false;
		}
		if (!preg_match('/[A-Z]/', $password)) {
			$_SESSION['error'] = 'Пароль должен содержать хотя бы одну заглавную букву.';
			return false;
		}
		if (!preg_match('/\d/', $password)) {
			$_SESSION['error'] = 'Пароль должен содержать хотя бы одну цифру.';
			return false;
		}
		if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
			$_SESSION['error'] = 'Пароль должен содержать хотя бы один спецсимвол (!@#$%^&*).';
			return false;
		}
		return true;
	}
	
    public function login($isAdmin = false) {
		$email = !empty(trim($_POST['email'])) ? strtolower(trim($_POST['email'])) : null;
		$password = !empty(trim($_POST['password'])) ? trim($_POST['password']) : null;

		if (!$email || !$password) {
			$_SESSION['error'] = 'Email и пароль обязательны';
			return false;
		}

		// Ограничение по IP для админов
		if ($isAdmin) {
			$allowed_ips = App::$app->getProperty('admin_allowed_ips');
			$client_ip = trim($_SERVER['REMOTE_ADDR']);

			// Проверка IP
			if (!is_array($allowed_ips) || !in_array($client_ip, array_map('trim', $allowed_ips))) {
				$_SESSION['error'] = 'Доступ в админку запрещён с этого IP.';
				return false;
			}
		}

		// Формируем запрос
		$query = "email = ?";
		$params = [$email];

		if ($isAdmin) {
			$query .= " AND role = ?";
			$params[] = 'admin';
		} else {
			$query .= " AND (role = 'b2buser' OR role = 'admin')";
		}

		$user = \R::findOne('user', $query, $params);

		if (!$user) {
			$_SESSION['error'] = 'Пользователь не найден.';
			return false;
		}

		if (!password_verify($password, $user->password)) {
			$_SESSION['error'] = 'Неверный пароль.';
			return false;
		}

		// Устанавливаем сессию
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		session_regenerate_id(true);

		foreach ($user as $k => $v) {
			if ($k != 'password') $_SESSION['b2buser'][$k] = $v;
		}

		unset($_SESSION['error']);
		session_write_close();

		\R::exec("DELETE FROM login_attempts WHERE email = ? AND ip = ?", [$email, $_SERVER['REMOTE_ADDR']]);

		return true;
	}
	
	public function generate_code() {
                
		$hours = date("H"); // час       
		$minuts = substr(date("H"), 0 , 1);// минута 
		$mouns = date("m");    // месяц             
		$year_day = date("z"); // день в году

		$str = $hours . $minuts . $mouns . $year_day; //создаем строку
		$str = md5(md5($str)); //дважды шифруем в md5
		$str = strrev($str);// реверс строки
		$str = substr($str, 3, 6); // извлекаем 6 символов, начиная с 3		

		$array_mix = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
		srand ((float)microtime()*1000000);
		shuffle ($array_mix);		
		return implode("", $array_mix);
	}

    public static function checkAuth(){
        return isset($_SESSION['b2buser']);
    }

    public static function isAdmin(){
		return (isset($_SESSION['b2buser']) && $_SESSION['b2buser']['role'] == 'admin');
	}
	
	public function get_user_hash($hash){

		$res = \R::findOne('recover', 'hash = ?', [$hash]);
		$now = time();
		$times = $res["expire"] - $now;
		if($times < 0) {
			$_SESSION['error'] = 'Ссылка устарела или вы перешли по некорректной ссылке. Пройдите процедуру восстановления заново по <a href="user/recover">ссылке</a>';
			\R::exec("DELETE FROM recover WHERE expire < '".$now."'");
			return false;
		}
		return $res;
	}


}