/*
*StudentID: 101662320
*Student Name: Tzu-Jung CHI
*Course : cos80001
*/

function showTitle(){
	document.getElementById('title_div').style.display = 'block';
	document.getElementById('keyword_div').style.display = 'none';
	document.getElementById('date_div').style.display = 'none';

}

function showKeyword(){
	document.getElementById('title_div').style.display = 'none';
	document.getElementById('keyword_div').style.display = 'block';
	document.getElementById('date_div').style.display = 'none';

}

function showDate(){
	document.getElementById('title_div').style.display = 'none';
	document.getElementById('keyword_div').style.display = 'none';
	document.getElementById('date_div').style.display = 'block';

}