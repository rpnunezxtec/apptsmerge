document.getElementById('newBtn').addEventListener('click', () => {
	// clear all xsitecraft- keys
	localStorage.clear();
	sessionStorage.clear();
	window.location.href = 'index.html';
});

document.getElementById('resumeBtn').addEventListener('click', () => {
	// just proceed without clearing
	window.location.href = 'index.html';
});