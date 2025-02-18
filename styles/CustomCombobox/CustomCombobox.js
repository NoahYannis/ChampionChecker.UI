// custom-combobox.js
let globalPlayerList = new Set();

function handleComboboxInput(inputElement) {
    const value = inputElement.value.trim();
    const dropdown = inputElement.nextElementSibling;

    // Show the dropdown if the user is typing
    if (value !== "") {
        dropdown.style.display = "block";
    } else {
        dropdown.style.display = "none";
    }

    // Highlight matching players or add them to the global list if they're new
    const playerOptions = dropdown.getElementsByClassName("player-option");
    Array.from(playerOptions).forEach(option => {
        if (option.innerText.toLowerCase().includes(value.toLowerCase())) {
            option.style.display = "block";
        } else {
            option.style.display = "none";
        }
    });

    // Check if the player exists in the list, and add if necessary
    if (!globalPlayerList.has(value) && value !== "") {
        globalPlayerList.add(value);
        updateDropdownOptions(dropdown);
    }
}

function updateDropdownOptions(dropdown) {
    dropdown.innerHTML = ""; // Clear existing options
    globalPlayerList.forEach(player => {
        const option = document.createElement("div");
        option.className = "player-option";
        option.innerText = player;
        option.onclick = function () {
            selectPlayer(option, dropdown);
        };
        dropdown.appendChild(option);
    });
}

function selectPlayer(optionElement, dropdown) {
    const inputElement = dropdown.previousElementSibling;
    inputElement.value = optionElement.innerText.trim();
    dropdown.style.display = "none";
}
