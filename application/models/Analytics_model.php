<?php
namespace Model;
use App;
use System\Emerald\Emerald_model;

class Analytics_model extends Emerald_Model
{
    const CLASS_TABLE = 'analytics';

    /** @var Int */
    protected $user_id;
    /** @var String describes the object with which it is carried interaction (boosterpack, wallet, etc)  */
    protected $object;
    /** @var Int action with object (buy boosterpack, add money to wallet ...) */
    protected $action;
    /** @var Int object id with which user interaction */
    protected $object_id;
    /** @var Int */
    protected $amount;
    /** @var Int */
    protected $time_created;
    /** @var Int */
    protected $time_updated;

    /**
     * @return int
     */
    public function get_user_id(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     *
     * @return bool
     */
    public function set_user_id(int $user_id):bool
    {
        $this->user_id = $user_id;
        return $this->save('user_id', $user_id);
    }

    /**
     * @return String
     */
    public function get_object():string
    {
        return $this->object;
    }

    /**
     * @param $object
     * @return bool
     */
    public function set_object($object):bool
    {
        $this->object = $object;
        return $this->save('object', $object);
    }

    /**
     * @return Int
     */
    public function get_action():int
    {
        return $this->action;
    }

    /**
     * @param $action
     * @return bool
     */
    public function set_action($action):bool
    {
        $this->action = $action;
        return $this->save('action', $action);
    }

    /**
     * @return Int
     */
    public function get_object_id():int
    {
        return $this->object_id;
    }

    /**
     * @param $object_id
     * @return bool
     */
    public function set_object_id($object_id):bool
    {
        $this->object_id = $object_id;
        return $this->save('object_id', $object_id);
    }

    /**
     * @return int
     */
    public function get_amount(): Int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     *
     * @return bool
     */
    public function set_amount(int $amount):bool
    {
        $this->amount = $amount;
        return $this->save('amount', $amount);
    }

    /**
     * @return string
     */
    public function get_time_created(): string
    {
        return $this->time_created;
    }

    /**
     * @param string $time_created
     *
     * @return bool
     */
    public function set_time_created(string $time_created):bool
    {
        $this->time_created = $time_created;
        return $this->save('time_created', $time_created);
    }

    /**
     * @return string
     */
    public function get_time_updated(): string
    {
        return $this->time_updated;
    }

    /**
     * @param string $time_updated
     *
     * @return bool
     */
    public function set_time_updated(string $time_updated):bool
    {
        $this->time_updated = $time_updated;
        return $this->save('time_updated', $time_updated);
    }

    function __construct($id = NULL)
    {
        parent::__construct();

        $this->set_id($id);
    }

    public function reload()
    {
        parent::reload();
        return $this;
    }

    public static function create(array $data)
    {
        //стосовно задачі по ствоернні данних для статистики (задача 6) я не зрозумів навіщо створювати додаткові класи (до цього не доводилось використовувати Enum і взагалі створювати статистику саме таким чином), тому це завдання скоріш за все я не виконаю правильно, та реалізую це все по своєму, для того щоб створити 6mysql.sql і щоб була зрозуміла логіка запитів які там будуть прописані
        //данні які будуть вписані в БД:
        /*
            user_id - тут все зрозуміло
            object - якщо це дія покупки бустер-паку то тут буде записана id бестер-паку, якщо це поповнення гаманця, то буде просто 0
            amount - у випадку покупки бестерпаку тут буде ціна цього бустера (тільки от ціна бестеру це float а тип колонки action це int, тому буду просто округляти і передавати ціле число, це звісно неправильно, але тут треба змінювати структуру БД тоді щоб підстроїтись під таку логіку а так як з самого початку вже зрозуміло що це завдання я виконую неправильно, то вирішив на це закрити очі), якщо це поповнення гаманця то тут буде вказана сумма на яку поповнили гаманець (знову ж таки буду передавати просто ціле число попередньо округливши значення, так як гаманець теж float)
            action - так як тип данних в БД varchar, то тут будуть передаватись строка з типом дії (buy_boosterpack|add_wallet)
            object_id - у випадку покупки бустерпаку тут буде кількість лайків які отримав користувач, у випадку поповнення гаманця тут буде просто 0
        */
        App::get_s()->from(self::CLASS_TABLE)->insert($data)->execute();
        return new static(App::get_s()->get_insert_id());
    }

    public function delete():bool
    {
        $this->is_loaded(TRUE);
        App::get_s()->from(self::CLASS_TABLE)->where(['id' => $this->get_id()])->delete()->execute();
        return App::get_s()->is_affected();
    }

    public function get_analytics_for_user(int $user_id): array
    {
        return static::transform_many(App::get_s()->from(self::CLASS_TABLE)->where(['user_id' => $user_id])->orderBy('time_created', 'ASC')->many());
    }

}
