<?php

namespace Model;

use App;
use Exception;
use System\Core\CI_Model;

class Login_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

    }

    public static function logout()
    {
        App::get_ci()->session->unset_userdata('id');
    }

    /**
     * @return int
     * @throws Exception
     */
    public static function login(string $email, string $password): int
    {
        $response = 0;
        // TODO: task 1, аутентификация
        $user_data = User_model::find_user_by_email($email);
        //якщо в БД був знайдений користувач з таким значенням email як і в змінній $email, то ми отримаємо массив з данними по такому користувачу, якщо ж такого користувача немає то отримаємо пустий массив
        if(count($user_data) > 0 && isset($user_data['id'], $user_data['password'])){
            //тепер перевіряємо чи співпадають значення паролів
            if($password == $user_data['password']){
                self::start_session((int)$user_data['id']);
                $response = (int)$user_data['id'];
            }
        }
        unset($user_data);

        return $response;
    }

    public static function start_session(int $user_id)
    {
        // если перенедан пользователь
        if (empty($user_id))
        {
            throw new Exception('No id provided!');
        }

        App::get_ci()->session->set_userdata('id', $user_id);
    }
}
