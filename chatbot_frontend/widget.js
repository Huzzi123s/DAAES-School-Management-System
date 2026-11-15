
/* ==================================
   DAAES CHATBOT WIDGET JAVASCRIPT
================================== */

document.addEventListener("DOMContentLoaded", function () {

    const toggleButton = document.getElementById("chatbotToggle");

    const closeButton = document.getElementById("chatbotClose");

    const popup = document.getElementById("chatbotPopup");

    if (!toggleButton || !closeButton || !popup) {
        return;
    }

    // Open and close chatbot
    toggleButton.addEventListener("click", function () {

        popup.classList.toggle("active");

        if (popup.classList.contains("active")) {

            toggleButton.innerHTML = "×";

            toggleButton.setAttribute(
                "aria-label",
                "Close School Assistant"
            );

        } else {

            toggleButton.innerHTML = "💬";

            toggleButton.setAttribute(
                "aria-label",
                "Open School Assistant"
            );

        }

    });

    // Close chatbot using close button
    closeButton.addEventListener("click", function () {

        popup.classList.remove("active");

        toggleButton.innerHTML = "💬";

        toggleButton.setAttribute(
            "aria-label",
            "Open School Assistant"
        );

    });

});
