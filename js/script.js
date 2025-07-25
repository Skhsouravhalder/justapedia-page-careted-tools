document.addEventListener("contextmenu", (event) => event.preventDefault());
document.onkeydown = function (e) {
  if (e.keyCode == 123) {
    // Prevent F12
    return false;
  }
  if (e.ctrlKey && e.shiftKey && e.keyCode == "I".charCodeAt(0)) {
    // Prevent Ctrl+Shift+I
    return false;
  }
  if (e.ctrlKey && e.shiftKey && e.keyCode == "J".charCodeAt(0)) {
    // Prevent Ctrl+Shift+J
    return false;
  }
  if (e.ctrlKey && e.keyCode == "U".charCodeAt(0)) {
    // Prevent Ctrl+U
    return false;
  }
};
