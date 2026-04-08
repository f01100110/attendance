// PASSWORD TOGGLE
// type="password" (hidden) and type="text" (visible).

function initPasswordToggles() {
    // lahat ng buttons with class "toggle-pw" (eye icons) will get this behavior
    const toggleButtons = document.querySelectorAll(".toggle-pw");

    toggleButtons.forEach(function(button) {
        button.addEventListener("click", function() {
            // para malaman kung aling input field ang i-toggle, we use a data attribute galing sa html
            const targetId = this.getAttribute("data-target");
            const input = document.getElementById(targetId);

            if (!input) return; // safety check: if walang input na may ganitong ID, do nothing

            if (input.type === "password") {
                input.type = "text";       // show the password. how does this work? the browser will immediately render the input as visible text instead of dots.
                this.textContent = "🙈";   // change the icon from "bukas na mata" to monke
            } else {
                input.type = "password";   // password is hidden again
                this.textContent = "👁";   // change icon back to
            }
        });
    });
}


// 2. CLIENT-SIDE FORM VALIDATION (register.php)
// runs before the form is sent to the server.
// this gives the user instant feedback without a page reload.
// why do we need this? kasi kung mag-rely lang tayo sa server-side validation (php), every time may mali, magre-refresh yung page and mawawala yung mga data na na-input na nila
// nakakainis yun. with client-side validation, they get instant feedback and can fix it right away without losing their input.

function initRegisterValidation() {
    const form = document.getElementById("registerForm");
    if (!form) return; // only run on pages that have this form

    form.addEventListener("submit", function(event) {
        const password = document.getElementById("password").value;
        const confirm  = document.getElementById("confirm_password").value;
        const idNumber = document.getElementById("id_number").value.trim();

        // check if ID number is empty
        if (idNumber === "") {
            event.preventDefault(); // if empty, stop form from submitting
            showInlineError("id_number", "Please enter your ID number.");
            return;
        }

        // check if password is at least 8 characters long
        if (password.length < 8) {
            event.preventDefault();
            showInlineError("password", "Password must be at least 8 characters.");
            return;
        }

        // check if password and confirm password match
        // pwede rin to sa server-side (php), pero mas okay if may client-side validation din para mas mabilis yung feedback sa user
        if (password !== confirm) {
            event.preventDefault();
            showInlineError("confirm_password", "Passwords do not match.");
            return;
        }
    });
}


// 3. HELPER: SHOW INLINE ERROR BELOW AN INPUT
// Creates a small red message under a specific input field.
// Removes any existing error first so there's no duplication.
// dati, yung error messages naka-array ($errors.php) tapos sa taas ng form. this is much user- friendly kasi mas malapit yung error sa field na may problema
// and they can fix it right away without having to scroll up to see the error list. also, the input field gets a red border to visually indicate where the problem is
function showInlineError(inputId, message) {
    const input = document.getElementById(inputId);
    if (!input) return;

    // dito tinatanggal muna yung existing error message (if any) para hindi magdoble-doble yung error messages kapag nag-submit ulit sila without fixing the problem
    const existing = input.parentElement.querySelector(".inline-error");
    if (existing) existing.remove();

    // error message element
    const errorEl = document.createElement("span");
    errorEl.className = "inline-error";
    errorEl.style.cssText = "color:#dc2626; font-size:0.8rem; display:block; margin-top:4px;"; // direct styling para siguradong red at maliit yung text, at may konting space sa taas
    errorEl.textContent = message;

    // what the helly? this means: "pasok yung error message right after the input field. if may parent na may class na 'input-wrapper', doon ipapasok yung error message,
    // otherwise, right after the input mismo"
    const parent = input.closest(".input-wrapper") || input;
    parent.insertAdjacentElement("afterend", errorEl);

    // highlight
    input.style.borderColor = "#dc2626";

    // this means: focus sa input field para makita agad ng user kung saan yung problema. kasi minsan, lalo na sa mobile, hindi agad halata kung saan yung error, 
    // so this will help guide them to the right place.
    input.focus();

    // clear the error style when the user starts typing again
    input.addEventListener("input", function() {
        input.style.borderColor = "";
        if (errorEl.parentElement) errorEl.remove();
    }, { once: true }); // { once: true } means this only fires one time
}


// 4. AUTO-DISMISS ALERTS
// ff there's a success or error alert box on the page,
// it will automatically fade out after 5 seconds.
// wala lang, mas okay lang may ganito para hindi mag-stay yung mga alert boxes sa page forever, lalo na yung mga success messages na hindi naman kailangan ng user na makita lagi.
function initAutoDismissAlerts() {
    const alerts = document.querySelectorAll(".alert");

    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Fade out smoothly
            alert.style.transition = "opacity 0.6s ease";
            alert.style.opacity = "0";

            // Remove from DOM after the fade finishes
            setTimeout(function() {
                alert.remove();
            }, 600);
        }, 5000); // 5000ms = 5 seconds
    });
}


// ============================================================
// 6. BURGER MENU -- DRAFT MUNA SINCE I LIKE THE WAY IT IS NOW, BUT I MIGHT CHANGE IT LATER
// Finds the burger button and toggles the sidebar open/closed.
// Also closes the sidebar when the overlay is clicked.
// ============================================================

// function initBurgerMenu() {
//    const burger  = document.getElementById('burgerBtn');
//    const sidebar = document.getElementById('sidebar');
//    const overlay = document.getElementById('sidebarOverlay');

    // Only run if these elements exist on the page
//    if (!burger || !sidebar) return;

//    function openSidebar() {
//        sidebar.classList.add('is-open');
//      burger.classList.add('is-open');
//      if (overlay) overlay.classList.add('is-visible');
//       document.body.style.overflow = 'hidden'; // prevent background scroll
//    }

//    function closeSidebar() {
//        sidebar.classList.remove('is-open');
//        burger.classList.remove('is-open');
//        if (overlay) overlay.classList.remove('is-visible');
//        document.body.style.overflow = '';
//    }

//    burger.addEventListener('click', function() {
//        if (sidebar.classList.contains('is-open')) {
//            closeSidebar();
//        } else {
//            openSidebar();
//        }
//    });

    // Close when clicking the dark overlay
//    if (overlay) {
//        overlay.addEventListener('click', closeSidebar);
//    }
//
    // Close sidebar when a nav link is clicked (smooth UX on mobile)
//    const navLinks = sidebar.querySelectorAll('.nav-item');
//    navLinks.forEach(function(link) {
//        link.addEventListener('click', closeSidebar);
//    });
//}

// 6. INIT FUNCTION
// document.addEventListener("DOMContentLoaded") means:
// "wait until the HTML is ready, then run this code"
// ano bang silbi neto? it prevents lang yung mga JavaScript errors na nangyayari kapag sinubukan ng script na i-access yung mga HTML elements bago pa sila nade-define sa page.
document.addEventListener('DOMContentLoaded', function() {
    initPasswordToggles();
    initRegisterValidation();
    initAutoDismissAlerts();
    initBurgerMenu();
});