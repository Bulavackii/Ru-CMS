window.speakPageText = function () {
    const msg = new SpeechSynthesisUtterance();
    msg.text = document.body.innerText;
    window.speechSynthesis.speak(msg);
};

window.setFontSize = function (size) {
    document.body.style.fontSize = size + 'px';
};

window.setLightMode = function () {
    document.body.style.backgroundColor = '#ffffff';
    document.body.style.color = '#000000';
};

window.setDarkMode = function () {
    document.body.style.backgroundColor = '#1a202c';
    document.body.style.color = '#f1f5f9';
};
