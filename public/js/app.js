const STATUS_SUCCESS = 'success';
const STATUS_ERROR = 'error';
var app = new Vue({
	el: '#app',
	data: {
		login: '',
		pass: '',
		post: false,
		failedLogin: false,
		invalidLogin: false,
		invalidPass: false,
		invalidSum: false,
		failedAddSumToWallet: false,
		failedAddNewComment: false,
		posts: [],
		addSum: 0,
		amount: 0,
		likes: 0,
		commentText: '',
		boosterpacks: [],
	},
	computed: {
		test: function () {
			var data = [];
			return data;
		}
	},
	created(){
		var self = this
		axios
			.get('/main_page/get_all_posts')
			.then(function (response) {
				self.posts = response.data.posts;
			})

		axios
			.get('/main_page/get_boosterpacks')
			.then(function (response) {
				self.boosterpacks = response.data.boosterpacks;
			})
	},
	methods: {
		logout: function () {
			console.log ('logout');
		},
		logIn: function () {
			var self= this;
			if(self.login === ''){
				self.invalidLogin = true
			}
			else if(self.pass === ''){
				self.invalidLogin = false
				self.invalidPass = true
			}
			else{
				self.invalidLogin = false
				self.invalidPass = false

				form = new FormData();
				form.append("login", self.login);
				form.append("password", self.pass);

				axios.post('/main_page/login', form)
					.then(function (response) {
						if(response.data.status == STATUS_SUCCESS){
							if(response.data.user) {
								location.reload();
							}
							setTimeout(function () {
								$('#loginModal').modal('hide');
							}, 500);
						}else{
							self.error_message = response.data.error_message;
							self.failedLogin = true;
						}
					})
			}
		},
		addComment: function(id) {
			var self = this;
			if(self.commentText) {

				var comment = new FormData();
				comment.append('postId', id);
				comment.append('commentText', self.commentText);

				axios.post(
					'/main_page/comment',
					comment
				).then(function (response) {
					if(response.data.status == STATUS_SUCCESS){
						alert('Comment successfully added');
						//тут можна додати оновлення блоку з коментарями для того щоб зявився новий коментар, або якось по іншому проінформувати користувача про те що коментарій успішно доданий. так як не використовував vue.js я вирішив не витрачати час на реалізацію функціоналу на front, так як основні завдання ТЗ звязані з беком, та при необходності в подальшому зміг би розібратись з vue.js
					}else{
						self.error_message = response.data.error_message;
						self.failedAddNewComment = true;
					}
				});
			}

		},
		refill: function () {
			var self= this;
			if(self.addSum === 0){
				self.invalidSum = true
			}
			else{
				self.invalidSum = false
				self.failedAddSumToWallet = false
				sum = new FormData();
				sum.append('sum', self.addSum);
				axios.post('/main_page/add_money', sum)
					.then(function (response) {
						if(response.data.status == STATUS_SUCCESS){
							setTimeout(function () {
								$('#addModal').modal('hide');
							}, 500);
						}else{
							self.error_message = response.data.error_message;
							self.failedAddSumToWallet = true;
						}
					})
			}
		},
		openPost: function (id) {
			var self= this;
			axios
				.get('/main_page/get_post/' + id)
				.then(function (response) {
					self.post = response.data.post;
					if(self.post){
						setTimeout(function () {
							$('#postModal').modal('show');
						}, 500);
					}
				})
		},
		addLike: function (type, id) {
			var self = this;
			const url = '/main_page/like_' + type + '/' + id;
			axios
				.get(url)
				.then(function (response) {
					if(response.data.likes)
						self.likes = response.data.likes;
				})

		},
		buyPack: function (id) {
			var self= this;
			var pack = new FormData();
			pack.append('id', id);
			axios.post('/main_page/buy_boosterpack', pack)
				.then(function (response) {
					if(response.data.status == STATUS_SUCCESS){
						self.amount = response.data.amount
						setTimeout(function () {
							$('#amountModal').modal('show');
						}, 500);
					}else{
						alert(response.data.error_message);
					}
				})
		}
	}
});

