<?php

use Model\Boosterpack_model;
use Model\Boosterpack_info_model;
use Model\Post_model;
use Model\User_model;
use Model\Login_model;
use Model\Comment_model;

/**
 * Created by PhpStorm.
 * User: mr.incognito
 * Date: 10.11.2018
 * Time: 21:36
 */
class Main_page extends MY_Controller
{

    public function __construct()
    {

        parent::__construct();

        if (is_prod())
        {
            die('In production it will be hard to debug! Run as development environment!');
        }
    }

    public function index()
    {
        $user = User_model::get_user();

        App::get_ci()->load->view('main_page', ['user' => User_model::preparation($user, 'default')]);
    }

    public function get_all_posts()
    {
        $posts =  Post_model::preparation_many(Post_model::get_all(), 'default');
        return $this->response_success(['posts' => $posts]);
    }

    public function get_boosterpacks()
    {
        $posts =  Boosterpack_model::preparation_many(Boosterpack_model::get_all(), 'default');
        return $this->response_success(['boosterpacks' => $posts]);
    }

    public function login()
    {
        // TODO: task 1, аутентификация
        $email = (string)App::get_ci()->input->post('login');
        if(($email = trim($email)) != ''){
            $password = (string)App::get_ci()->input->post('password');
            if(($password = trim($password)) != ''){

                $user_id = Login_model::login($email, $password);
                if($user_id > 0){
                    return $this->response_success(['user' => $user_id]);
                }else{
                    return $this->response_error('Email or password is incorrect');
                }
            }else{
                return $this->response_error('Password field cannot be empty');
            }
        }else{
            return $this->response_error('Login field cannot be empty');
        }
    }

    public function logout()
    {
        // TODO: task 1, аутентификация
        Login_model::logout();
        header('Location: /');
    }

    public function comment()
    {
        // TODO: task 2, комментирование

        //перевіряємо чи залогінений користувач
        if(User_model::is_logged()){
            //за допомогою метода get_post_data моделі Post_model ми можемо перевірити чи є взагалі в БД такий пост (взагалі для таких цілей створений окремий метод check_post_isset , але тут використовується get_post_data так як потрібні будуть данні які цей метод поверне)
            $post_data = Post_model::get_post_data((int)App::get_ci()->input->post('postId'));
            if(count($post_data) > 0){
                $commentText = App::get_ci()->input->post('commentText');
                if(is_string($commentText) && ($commentText = trim($commentText)) != ''){
                    $new_comment_id = (int)Comment_model::create([
                        'user_id' => User_model::get_session_id(),
                        'assign_id' => $post_data['id'],
                        'text' => $commentText,
                        //тут я якщо чесно не зрозумів чому у цього поля за замовчуванням стоїть значення null, тому вписав 0
                        'likes' => 0
                    ]);
                    if($new_comment_id > 0){
                        return $this->response_success();
                    }else{
                        return $this->response_error('Failed set new comment');
                    }
                }else{
                    return $this->response_error('Comment text value cannot be empty');
                }
            }else{
                return $this->response_error('Post not found');
            }
        }else{
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
    }

    public function like_comment(int $comment_id)
    {
        // TODO: task 3, лайк комментария
        //помітив що при лайку коментаря, но фронту оновлюється значення лайків поста чомусь, я так розумію там якась бага на фронту (якщо лайкати пост то все працює правильно)
        if(User_model::is_logged()){
            //перевіряємо чи є такий коментар
            if(Comment_model::checkCommentIsset($comment_id)){
                
                $comment_model = new Comment_model($comment_id);
                if($comment_model->increment_likes(new User_model(User_model::get_session_id()))){
                    //викликаємо метод reload() для того щоб оновити в класі данні по коментарю і можна було використовуючи метод get_likes() повернути на фрон значення кількості лайків коментаря
                    $comment_model->reload();
                    return $this->response_success(['likes' => $comment_model->get_likes()]);
                }else{
                    return $this->response_error('Failed set like on post');
                }
            }else{
                return $this->response_error('Comment not found');
            }
        }else{
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
    }

    public function like_post(int $post_id)
    {
        // TODO: task 3, лайк поста
        if(User_model::is_logged()){
            //перевіряємо чи є такий пост
            if(Post_model::check_post_isset($post_id)){

                $post_model = new Post_model($post_id);
                if($post_model->increment_likes(new User_model(User_model::get_session_id()))){
                    $post_model->reload();
                    return $this->response_success(['likes' => $post_model->get_likes()]);
                }else{
                    return $this->response_error('Failed set like on post');
                }
            }else{
                return $this->response_error('Post not found');
            }
        }else{
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
    }

    public function add_money()
    {
        // TODO: task 4, пополнение баланса
        //для початку перевіряємо чи залогінений користувач
        if(User_model::is_logged()){
            //так як в БД колонки wallet_balance і wallet_total_refilled мають тип decimal і в дробній частині зберігають тільки 2 числа, тоді виконуємо round() для $sum і перевіряємо значення
            $sum = round((float)App::get_ci()->input->post('sum'), 2);
            if($sum > 0){
                $user = new User_model(User_model::get_session_id());
                if($user->add_money($sum)){
                    return $this->response_success();
                }else{
                    return $this->response_error('Failed add money to the wallet');
                }
            }else{
                return $this->response_error('Incorrect sum value, must be bigger then 0.00');
            }
        }else{
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
    }

    public function get_post(int $post_id) {
        // TODO получения поста по id

        //збираємо дані поста із таблиці post
        $post_data = Post_model::get_post_data($post_id);
        if(count($post_data) > 0){
            //збираємо коментарі до посту і групуємо їх
            //використовувати буду метод get_comments моделі Post_model, також буду реалізовувати вложені коментарі, але не задопомогою метода get_all_by_replay_id , а за допомогою циклів, так як уже одним запитом до БД були зібрані всі коментарі поста, немає сенсу викликати get_all_by_replay_id для відправки запитів в БД за вложеними коментарями (це все робиться для того щоб не створювати запитів в циклі)
            //як я зрозумів, в таблиці comment, колонка assign_id відповідає за id поста до якого відноситься коментар, а колонка reply_id вказує на те, на який коммент була відповідь
            $post_all_comments = Comment_model::get_comments_by_post_id($post_id);
            //массив $post_comments буде наповнений коментарями з урахуванням вложеності
            $post_comments = [];
            //массив $comments_connections зберігає в собі так би мовити шлях (ключі) в массиві $post_comments щоб знайти комментар до якого данний коментар являється відповіддю (якщо у коментаря значення reply_id не NULL), ключом буде id поточного коментаря, а значеня, будет строка з ключів розділених ';'
            $comments_connections = [];
            foreach($post_all_comments as $ar){

                $ar['reply_comments'] = [];
                $ar['user'] = ['personaname' => $ar['user_personaname']];
                unset($ar['user_personaname']);

                if(is_null($ar['reply_id'])){
                    $post_comments[] = $ar;
                    //за допомогою функції array_key_last отримуємо значення останнього ключа в массиві $post_comments під яким виходить тільки що були додані данні поточного коментаря ($ar)
                    $comments_connections[$ar['id']] = array_key_last($post_comments);
                }else{
                    //якщо у поточного коментаря значення reply_id не NULL, але при цьому в $comments_connections немає ключа зі значенням із цього поля (при виконані запиту в БД на збір коментарів було використано order by ASC) то це говорить про те що "головного" коментаря немає (можливо був видалений), то і цей комментар пропускаємо, так як вважаю що якщо видалений головний коментар то і всі "дочірні/вложені" мають бути теж видалені (якщо я помиляюсь то це не проблема, можна буде в такому випадку помістити коментар в массив $post_comments так як робимо з коментарями у яких значення поля reply_id NULL)
                    if(isset($comments_connections[$ar['reply_id']])){
                        $status = true;
                        $path = explode(';', $comments_connections[$ar['reply_id']]);
                        /*
                            $post_comments_copies = [
                                [
                                    'reply_comments_key_back' => ключ з reply_comments, використовуються для того щоб розуміти куди саме треба буде повернути на місце, щоб перезаписати данні під тим ключем з якого брався массив з вложеними коментарями,
                                    'array' => массив який "взяли" для подальшого перезапису
                                ],
                                ...
                                []
                            ];
                        */
                        $post_comments_copies = [];
                        foreach($path as $key){
                            //якщо массив пустий, то це означає що це перша ітерація циклу $path
                            if(count($post_comments_copies) == 0){
                                if(isset($post_comments[$key])){
                                    $post_comments_copies[] = [
                                        'reply_comments_key_back' => $key,
                                        'array' => $post_comments[$key]
                                    ];
                                }else{
                                    $status = false;
                                    break 1;
                                }
                            }else{
                                $copy_last_key = array_key_last($post_comments_copies);
                                if(isset($post_comments_copies[$copy_last_key]['array']['reply_comments'], $post_comments_copies[$copy_last_key]['array']['reply_comments'][$key], $post_comments_copies[$copy_last_key]['array']['reply_comments'][$key]['reply_comments'])){

                                    $post_comments_copies[] = [
                                        'reply_comments_key_back' => $key,
                                        'array' => $post_comments_copies[$copy_last_key]['array']['reply_comments'][$key]
                                    ];
                                }else{
                                    $status = false;
                                    break 1;
                                }
                                unset($copy_last_key);
                            }
                        }
                        unset($path);
                        unset($key);

                        if($status){
                            //в массив який знаходиться під останнім ключем в массиві $post_comments_copies в ключ reply_comments додаємо дані коментаря із $ar
                            $copy_last_key = (int)array_key_last($post_comments_copies);
                            $post_comments_copies[$copy_last_key]['array']['reply_comments'][] = $ar;
                            //тепер дізнаємось під яким ключем ми зараз помістили дані коментаря (для того щоб записати "шлях" в массив $comments_connections)
                            $insert_key = array_key_last($post_comments_copies[$copy_last_key]['array']['reply_comments']);

                            //зберігаємо "шлях"
                            $comments_connections[$ar['id']] = $comments_connections[$ar['reply_id']].';'.$insert_key;
                            unset($insert_key);

                            //тепер в зворотньому порядку обробляємо $post_comments_copies вставляючи массиви на свої місця для того щоб перезаписати $post_comments
                            while($copy_last_key >= 0){
                                if($copy_last_key == 0){

                                    $post_comments[$post_comments_copies[0]['reply_comments_key_back']] = $post_comments_copies[0]['array'];

                                    break 1;

                                }else{

                                    $post_comments_copies[($copy_last_key - 1)]['array']['reply_comments'][$post_comments_copies[$copy_last_key]['reply_comments_key_back']] = $post_comments_copies[$copy_last_key]['array'];

                                    unset($post_comments_copies[$copy_last_key]);

                                }
                                $copy_last_key--;
                            }
                            unset($copy_last_key);
                        }
                        unset($post_comments_copies);
                        unset($status);
                    }
                }
            }
            unset($post_all_comments);
            unset($comments_connections);
            unset($ar);

            //щодо результуючого массиву а точніше щодо його структури, я б взагалі данні на сторінку виводив по іншому, але зробив так (підстроївся під фронт, настільки, наскільки це було можливо) змоделювавши ситуацію коли фронт вже міг бути готовим і змінювати його немає часу і тому формування відповіді з беку треба підстроювати під ту логіку розміщення контенту на фронту що вже була там прописана
            //стосовно групування вложених коментарів: згрупувати їх можна було декількома способами, перший це той що я реалізував, другий можна було не використовувати вложені массиви і просто заповнювати результуючий массив так би мовити в стовпчик, наприклад є 5 коментарів з відповідними id від 1 до 5. 3-й є відповіддю на 1-й, 5-й відповіддю на 3-й, то в результуючому массиві коментарі заповлювались би в такому порядку: 1 -> 3 -> 5 -> 2 -> 4 . Так як фронт (наскільки я зміг зрозуміть) не був готовий до функціоналу відображення вложених коментарів (так як на мою думку вони візуально мають відрізнятись від звичайних коментарів по своєму розміщенню. наприклад як на Reddit, там вложені коментарі в залежності від рівня вложеності зміщуються все правіше і правіше відносно звичайних коментарів), я вибрав той варіант який більше всього сподобався і який на мою думку я би зміг красивіше (з точки зору коду) вивести на сторінку використовуючи jQuery та ajax
            $data_array = [
                'id' => $post_data['id'],
                'img' => $post_data['img'],
                'likes' => $post_data['likes'],
                'user' => [
                    'avatarfull' => $post_data['user_avatarfull'],
                    'personaname' => $post_data['user_personaname']
                ],
                'coments' => $post_comments
            ];
            unset($post_data);
            unset($post_comments);

            return $this->response_success(['post' => $data_array]);
        }else{
            return $this->response_error('Post not found');
        }
    }

    public function buy_boosterpack()
    {

        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }

        // TODO: task 5, покупка и открытие бустерпака

        //перевіряємо чи існує такий бустер-пак
        $booster_info = new Boosterpack_model((int)App::get_ci()->input->post('id'));

        //тут я вирішив реалізувати перевірку на те чи існує такий бустер-пак трошки іншим способом в плані реалізації
        if($booster_info->check_isset()){

            //перевіряємо чи вистачає у користувача грошей на балансі для покупки данного пубустер-паку
            $user = new User_model(User_model::get_session_id());
            if($user->get_wallet_balance() >= $booster_info->get_price()){

                //отримуємо список item-ів які знаходяться в цьому бустер-паку
                $boster_contains = Boosterpack_info_model::get_by_boosterpack_id($booster_info->get_id());

                //про всяк-випадок перевіряємо чи не пустий бустер, так як я розумію що такого бути не повинно
                if(count($boster_contains) > 0){
                    //рахуємо максимальну вартість item-у
                    $max_available_likes = $booster_info->get_bank() + ($booster_info->get_price() - $booster_info->get_us());
                    
                    //відбираємо в массив $items ті ітеми, вартість яких не перевищую значення $max_available_likes
                    $items = [];
                    foreach($boster_contains as $item){
                        if($item->get_item()->get_price() <= $max_available_likes){
                            $items[] = [
                                'id' => $item->get_item()->get_id(),
                                'price' => $item->get_item()->get_price()
                            ];
                        }
                    }

                    unset($boster_contains);
                    unset($item);
                    unset($max_available_likes);

                    //перевіряємо чи є хоч один елемент в массиві $items, якщо ні то значить користувач нічого не отримає. Кількість отриманих лайків зберігаємо в $gettedLikes
                    $gettedLikes = 0;
                    if(count($items) > 0){
                        //перетасовуємо массив
                        shuffle($items);
                        //так як 1 лайк = 1$
                        $gettedLikes = (int)$items[0]['price'];
                    }
                    unset($items);

                    if($booster_info->open($user, $gettedLikes)){
                        return $this->response_success(['amount' => $gettedLikes]);
                    }else{
                        return $this->response_error('Operation is failed');
                    }
                }else{
                    return $this->response_error('Boosterpack is empty');
                }
            }else{
                return $this->response_error('You balance is low for this operation');
            }
        }else{
            return $this->response_error('Uknown boosterpack');
        }
    }





    /**
     * @return object|string|void
     */
    public function get_boosterpack_info(int $bootserpack_info)
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }


        //TODO получить содержимое бустерпака
    }
}
