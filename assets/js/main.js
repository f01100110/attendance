// PARA SA MAHIWAGANG MATA
function initPasswordToggles() {
    // Get all toggle buttons on the page
    const toggleButtons = document.querySelectorAll(".toggle-pw");

    toggleButtons.forEach(function(button) {
        button.addEventListener("click", function() {
            const targetId = this.getAttribute("data-target");
            const input = document.getElementById(targetId);

            if (!input) return; // Safety check

            if (input.type === "password") {
                input.type = "text";       // Show the password
                this.textContent = "🙈";   // Change MATA TO MONKE to indicate "hide"
            } else {
                input.type = "password";   // Hide the password again
                this.textContent = "👁";   // Change icon back
            }
        });
    });
}


// CLIENT-SIDE ABANG 

function initRegisterValidation() {
    const form = document.getElementById("registerForm");
    if (!form) return; // Only run on pages that have this form

    form.addEventListener("submit", function(event) {
        const password = document.getElementById("password").value;
        const confirm  = document.getElementById("confirm_password").value;
        const idNumber = document.getElementById("id_number").value.trim();

        // ID number field is not empty
        if (idNumber === "") {
            event.preventDefault(); // Stop form from submitting
            showInlineError("id_number", "Please enter your ID number.");
            return;
        }

        // password is at least 8 characters
        if (password.length < 8) {
            event.preventDefault();
            showInlineError("password", "Password must be at least 8 characters.");
            return;
        }

        // passwords match // pwede rin naman to sa php pero para mas mabilis yung feedback sa user, client-side na agad natin chinecheck
        if (password !== confirm) {
            event.preventDefault();
            showInlineError("confirm_password", "Passwords do not match.");
            return;
        }
    });
}


// error message the input field. unlike with what we're used to, wala tayong errors.php

function showInlineError(inputId, message) {
    const input = document.getElementById(inputId);
    if (!input) return;

    // again, unlike errors.php, hindi mag-stack on top of the other error msg. if may existing error message na, we remove it first bago mag-add ng new one.
    const existing = input.parentElement.querySelector(".inline-error");
    if (existing) existing.remove();

    // errorel connected sa html, may class na inline-error para madali natin ma-target sa css
    // and we style it directly here para hindi na kailangan mag-edit ng css file.
    const errorEl = document.createElement("span");
    errorEl.className = "inline-error";
    errorEl.style.cssText = "color:#dc2626; font-size:0.8rem; display:block; margin-top:4px;";
    errorEl.textContent = message;

    // insert errmsg after the input field. may parent wrapper kasi so that the error message is visually grouped with the input
    // and para hindi magulo yung layout kapag may error message.
    const parent = input.closest(".input-wrapper") || input;
    parent.insertAdjacentElement("afterend", errorEl);

    input.style.borderColor = "#dc2626";

    // focus lang sayo uwu. fuuuu
    input.focus();

    // clear lang kagad yung errmsg pag nag-type ulit
    input.addEventListener("input", function() {
        input.style.borderColor = "";
        if (errorEl.parentElement) errorEl.remove();
    }, { once: true }); // { once: true } means this only fires one time
}


// auto-dismiss alerts lang. ewan ko kinopya ko lang to somewhere down the road
// pero parang useful din to para hindi mag-pile up yung mga alert messages sa page. 
// especially pag may form na nagre-render ulit ng page with error messages, para di na kailangan i-refresh ng user para mawala yung mga old alerts.
// naalala nyo yan basta stubborn tong errmsg
function initAutoDismissAlerts() {
    const alerts = document.querySelectorAll(".alert");

    alerts.forEach(function(alert) {
        setTimeout(function() {
            // daming arte
            alert.style.transition = "opacity 0.6s ease";
            alert.style.opacity = "0";

            // dom means "document object model" which is basically yung structure ng HTML page.
            // pag sinabi nating "manipulate the DOM" ibig sabihin nito ay baguhin natin yung structure o content ng HTML page gamit ang JavaScript. sa case na to,
            // after natin gawing invisible yung alert, gusto din natin tanggalin siya sa DOM para hindi na siya maka-interact sa page kahit invisible na siya.
            setTimeout(function() {
                alert.remove();
            }, 600);
        }, 5000); // 5000ms = 5 seconds
    });
}

//uyy Event-driven. anong event? DOMContentLoaded. pag fully loaded na yung HTML saka lang tatakbo yung mga functions.
// para di magka-error na "cannot find element" kasi hindi pa loaded yung mga elements sa page.
// for what? para siguradong ready na yung page bago mag-attach ng event listeners at mag-manipulate ng DOM.
document.addEventListener("DOMContentLoaded", function() {
    initPasswordToggles();
    initRegisterValidation();
    initAutoDismissAlerts();
});
