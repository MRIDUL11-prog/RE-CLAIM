
document.addEventListener("DOMContentLoaded", function () {

    const feedbackDialog = document.querySelector("#feedback-thanks");

    if (!feedbackDialog) {
        return;
    }

    // Open success popup in the center
    if (typeof feedbackDialog.showModal === "function") {
        feedbackDialog.showModal();
    } else {
        feedbackDialog.setAttribute("open", "");
    }

    // Close popup using Done button
    const closeButton = feedbackDialog.querySelector("[data-close-feedback]");

    if (closeButton) {
        closeButton.addEventListener("click", function () {
            if (typeof feedbackDialog.close === "function") {
                feedbackDialog.close();
            } else {
                feedbackDialog.removeAttribute("open");
            }
        });
    }

    // Close popup when clicking outside its content
    feedbackDialog.addEventListener("click", function (event) {
        if (event.target === feedbackDialog) {
            if (typeof feedbackDialog.close === "function") {
                feedbackDialog.close();
            } else {
                feedbackDialog.removeAttribute("open");
            }
        }
    });

});
