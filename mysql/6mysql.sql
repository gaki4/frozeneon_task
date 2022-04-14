#Сколько денег потрачено на бустерпаки по каждому паку отдельно, почасовая выборка. Также нужно показать, сколько получили юзеры из каждого пока в эквиваленте $. Выборка должна быть за последние 30 дней.

#стосовно погодинної виборки, як я зрозумів ми збираємо данні за період 30 днів, а потім групуємо данні таким чином щоб подивитись в якій годині купляли паки, скільки грошей було витрачено користувачами на паки в цей час і інші данні
SELECT a.bosterpack_id, a.hour_when_buyed, SUM(a.sum_spended) as sum_spended, SUM(a.total_likes_get) as total_likes_get
FROM (
	SELECT object as bosterpack_id, SUM(amount) as sum_spended, SUM(object_id) as total_likes_get, HOUR(time_created) as hour_when_buyed
	FROM analytics
	WHERE DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= date(time_created) AND action = 'buy_boosterpack'
	GROUP BY object
) a
GROUP BY a.hour_when_buyed, a.bosterpack_id



#Выборка по юзеру, на сколько он пополнил баланс и сколько получил лайков за все время. Текущий остаток баланса в $ и лайков на счету.

#як я зрозумів данні треба зібрати по всім користувачам і згрупувати їх, якщо ні, то просто треба буде додати умови в WHERE для того щоб зібрати данні тільки по одному конкретному користувачу
SELECT a.user_id, SUM(a.total_likes_get) as total_likes_get, SUM(a.likes_balance) as likes_balance, SUM(a.wallet_balance) as wallet_balance, SUM(a.wallet_total_refilled) as wallet_total_refilled
FROM (
	SELECT user_id, object_id as total_likes_get, 0 as likes_balance, 0 as wallet_balance, 0 as wallet_total_refilled
	FROM analytics
	WHERE action = 'buy_boosterpack'

	UNION ALL

	SELECT id as user_id, 0 as total_likes_get, likes_balance, wallet_balance, wallet_total_refilled
	FROM user
) a
GROUP BY a.user_id