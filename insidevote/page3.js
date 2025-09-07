let optionCount = 2;

document.getElementById("addOption").addEventListener("click", function () {
  optionCount++;
  const input = document.createElement("input");
  input.type = "text";
  input.name = "option";
  input.placeholder = "Option " + optionCount;
  input.required = true;

  document.getElementById("optionsContainer").appendChild(input);
});

document.getElementById("pollForm").addEventListener("submit", function (e) {
  e.preventDefault();
  document.getElementById("successMessage").classList.remove("hidden");
});