const loginTab = document.querySelector("#loginTab");
const signupTab = document.querySelector("#signupTab");
const loginForm = document.querySelector("#loginForm");
const signupForm = document.querySelector("#signupForm");
const params = new URLSearchParams(window.location.search);

function showForm(formName) {
  const showSignup = formName === "signup";

  loginForm.hidden = showSignup;
  signupForm.hidden = !showSignup;
  loginTab.classList.toggle("active", !showSignup);
  signupTab.classList.toggle("active", showSignup);

  loginForm.querySelectorAll("input, button").forEach((field) => {
    field.disabled = showSignup;
  });

  signupForm.querySelectorAll("input, button, select").forEach((field) => {
    field.disabled = !showSignup;
  });
}

loginTab.addEventListener("click", () => showForm("login"));
signupTab.addEventListener("click", () => showForm("signup"));

showForm(params.get("form") === "signup" ? "signup" : "login");
