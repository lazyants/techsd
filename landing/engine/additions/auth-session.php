<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

/*

Авторизация через php-сессии

1. В каталоге страницы auth/auth-options.php

	$OPTIONS = array(
		'username' => 'max', // логин
		'password' => '123', // пароль
		'text_error' => 'Ошибочные данные', // текст при ошибке
	);

2. В text.php в начале

		require_once(ENGINE_DIR . 'additions/auth-session.php');
		
		if (!mso_auth('<a href="?login">ВХОД</a>')) return;

		... текст доступный только после авторизации ...
		
		Ссылка на <a href="?logout">ВЫХОД</a>

3. В файле auth/auth-login-form.php можно разместить свою форму логина.

*/

function mso_auth($text_login = '<a href="?login">Вход</a>', $OPTIONS = true)
{
	// дефолтные опции
	$def_options = array(
		'username' => 'admin', // логин
		'password' => 'admin', // пароль
		'logout_link' => 'logout', // разлогиневание http://сайт/page?logout
		'login_link' => 'login', // разлогиневание http://сайт/page?login
		'login_form' => CURRENT_PAGE_DIR . 'auth/auth-login-form.php', // файл формы 
		'text_error' => 'Ошибочные данные', // текст при ошибке
	);
	
	// если $OPTIONS === true, то загружаем из auth/auth-options.php текущей page
	if ($OPTIONS === true)
	{
		$OPTIONS = mso_load_options(CURRENT_PAGE_DIR . 'auth/auth-options.php');
	}

	// объединяем с переданными
	$OPTIONS = array_merge($def_options, $OPTIONS);
	
	// все редиректы на эту же страницу без ?-get
	$url_redirect = mso_current_url(false, true, true);
	
	// признак отображения формы
	$show_form = false;
	
	// вход
	if (mso_url_request(false, $OPTIONS['login_link']))
	{
		// если есть post, то проверяем данные
		if ($_POST 	
			and isset($_POST['flogin_user'])
			and isset($_POST['flogin_password'])
			and isset($_POST['flogin_submit']) 
			)
		{
			// сравниваем логин и пароль
			if ( strcmp($_POST['flogin_user'], $OPTIONS['username']) == 0
				 and strcmp($_POST['flogin_password'], $OPTIONS['password']) == 0 )
			{
				// равно
				if (!isset($_SESSION)) session_start();
				
				$_SESSION['username'] = $OPTIONS['username'];
				$_SESSION['password'] = $OPTIONS['password'];
				
				// все ок!
				header('Location:' . $url_redirect);
			}
			else
			{
				// не равно
				echo $OPTIONS['text_error']; // ошибочные данные
				$show_form = true;
			}
		}
		else
		{
			// нет post 
			
			// если уже есть залогиненость, то редиректим
			if (mso_is_auth($OPTIONS)) header('Location:' . $url_redirect);
			
			$show_form = true; // выводим форму
		}
	}
	elseif (mso_url_request(false, $OPTIONS['logout_link'])) // ссылка на выход
	{
		if (!isset($_SESSION)) session_start();
		if (isset($_SESSION['username'])) unset($_SESSION['username']);
		if (isset($_SESSION['password'])) unset($_SESSION['password']);
		
		header('Location:' . $url_redirect);
	}
	
	if ($show_form) 
	{
		if (file_exists($OPTIONS['login_form'])) 
			require($OPTIONS['login_form']);
		else
			mso_auth_form();
	}
	
	// если нет авторизации, то выводим сслыку на ВХОД
	if (strpos($_SERVER['REQUEST_URI'], '?') === FALSE) 
	{
		if (!mso_is_auth($OPTIONS)) echo $text_login;
	}
	
	if (mso_is_auth($OPTIONS))
		return $OPTIONS;
	else
		return false;
}

# проверка залогированности
# возвращает true — если логин и пароль верный
function mso_is_auth($OPTIONS = true)
{
	// если $OPTIONS === true, то загружаем из auth/auth-options.php текущей page
	if ($OPTIONS === true)
	{
		$OPTIONS = mso_load_options(CURRENT_PAGE_DIR . 'auth/auth-options.php');
	}

	if (!isset($_SESSION)) session_start();
	
	if (
		isset($_SESSION['username'])
		and isset($_SESSION['password'])
		and strcmp($_SESSION['username'], $OPTIONS['username']) === 0
		and	strcmp($_SESSION['password'], $OPTIONS['password']) === 0
	)
		return true;
	else
		return false;
}

# Стандартная форма если нет файла формы
function mso_auth_form() 
{
	echo '
<form method="post">
	<p><label>Логин<br><input type="text" value="" name="flogin_user"></label></p>
	<p><label>Пароль<br><input type="password" value="" name="flogin_password"></label></p>
	<p><button type="submit" name="flogin_submit">Вход</button></p>
</form>
';
}

# end of file