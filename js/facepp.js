var api_key = '64f0b8d4729734b49f231e5b0c1f4523';
var	api_secret = 'bABwx_lmF99mpbGy9M3ZSzsJqiiAoNpb';
var api = '?api_key=' + api_key + '&api_secret=' + api_secret;

var url = 'https://apius.faceplusplus.com';
var detection_detect_url = url + '/detection/detect';

//face++に情報を送信する
var send_info = function(url, callback) {
	$.ajax({
		url: url,
		type: 'POST',
		success: function(data, dataType) {
			callback();
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			console.log('Error : ' + errorThrown);
		}
	});
};

//face++に画像を送信する
var send_img = function(url, formData, callback) {
	$.ajax({
		url: url,
		type: 'POST',
		data: formData,
		contentType: false,
		processData: false,
		success: function(data, dataType) {
			callback(data);
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			console.log('Error : ' + errorThrown);
		}
	});
}

//face++に人を登録する
var person_create = function(name, face, callback) {
	var request_url = url + '/person/create' + api + '&person_name=' + name + '&face_id=';
	var max = face.length;
	for(var i = 0; i < max; i++) {
		request_url += face[i];
		if(i < max - 1)
			request_url += ',';
	}

	send_info(request_url,　function(){ train_verify(name, callback); });
};

//人をトレーニングする
var train_verify = function(name, callback) {
	var request_url = url + '/train/verify' + api + '&person_name=' + name;
	send_info(request_url, callback);
};

//face++のグループを作成する
var group_create = function(name, person, callback) {
	var request_url = url + '/group/create' + api + '&group_name=' + name + '&person_name=' + person;
	send_info(request_url, function(){ train_identify(name, callback); });
};

//グループをトレーニングする
var train_identify = function(name, callback) {
	var request_url = url + '/train/identify' + api + '&group_name=' + name;
	send_info(request_url, callback);
};