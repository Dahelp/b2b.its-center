// Убираем через 5 секунд сообщений success
document.addEventListener("DOMContentLoaded", function() {
    const successMessage = document.querySelector(".alert-success");
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.display = "none";
        }, 5000);
    }
	// меняем формы авторизации и востановления пароля
    const showRecoverForm = document.getElementById("showRecoverForm");
    const showLoginForm = document.getElementById("showLoginForm");
    const loginForm = document.getElementById("loginForm");
    const recoverForm = document.getElementById("recoverForm");

    if (showRecoverForm && showLoginForm && loginForm && recoverForm) {
        showRecoverForm.addEventListener("click", function(event) {
            event.preventDefault();
            loginForm.style.display = "none";
            recoverForm.style.display = "block";
        });
        showLoginForm.addEventListener("click", function(event) {
            event.preventDefault();
            recoverForm.style.display = "none";
            loginForm.style.display = "block";
        });
    }
});