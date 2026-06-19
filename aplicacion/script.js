const container = document.querySelector(".container");
const loginBtn = document.querySelector(".btn-login");
const signupBtn = document.querySelector(".btn-signup");
const activeBtns = document.querySelectorAll(".btn-active");

signupBtn.addEventListener("click", () => {
  container.classList.add("log-in");
});

loginBtn.addEventListener("click", () => {
  container.classList.remove("log-in");
});

activeBtns.forEach((btn) => {
  btn.addEventListener("click", () => {
    container.classList.add("active");
  });
});