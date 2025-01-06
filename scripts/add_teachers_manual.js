(function () {
  const participationToggle = document.getElementById("participationToggle");
  const participationToggleLabel = participationToggle.parentNode;

  // Verhindern, dass beim Togglen mehrere asynchrone Fetch-Anfragen zeitgleich verarbeitet werden.
  let isFetching = false;

  function toggleClassDisplay() {
    const isChecked = participationToggle.checked;

    if (isChecked && !document.getElementById("class-select") && !isFetching) {
      isFetching = true;

      const classLabel = document.createElement("label");
      classLabel.innerHTML = `<abbr title="Ein Lehrer kann bis zu 2 Klassen, eine Klasse bis zu 2 Lehrer.">Klassen:</abbr>`;
      classLabel.id = "class-label";

      const classSelect = document.createElement("select");
      classSelect.setAttribute(
        "title",
        "Halten Sie STRG gedrückt, um mehrere Optionen auszuwählen."
      );
      classSelect.id = "class-select";
      classSelect.name = "classes[]";
      classSelect.multiple = true;

      fetch("../../Helper/get_available_classes.php", {
        method: "GET",
      })
        .then((response) => response.json())
        .then((data) => {
          data.forEach((classItem) => {
            const option = document.createElement("option");
            option.value = classItem.name;
            option.textContent = `${classItem.name} (${classItem.teacherCount}/2)`;

            if (!classItem.available) {
              option.classList.add("class-unavailable");
              option.disabled = true;
            }

            classSelect.appendChild(option);
          });

          participationToggleLabel.parentNode.insertBefore(
            classLabel,
            participationToggleLabel.nextSibling
          );
          participationToggleLabel.parentNode.insertBefore(
            classSelect,
            classLabel.nextSibling
          );

          classSelect.addEventListener("change", function () {
            const selectedOptions = Array.from(classSelect.selectedOptions);
            if (selectedOptions.length > 2) {
              selectedOptions[selectedOptions.length - 1].selected = false;
              alert("Ein Lehrer kann maximal 2 Klassen betreuen.");
            }
          });
        })
        .catch((error) => {
          console.error("Error fetching classes:", error);
        })
        .finally(() => {
          isFetching = false;
        });
    } else {
      document.getElementById("class-select")?.remove();
      document.getElementById("class-label")?.remove();
    }
  }

  participationToggle.addEventListener("change", toggleClassDisplay);
  window.checkParticipationToggle = toggleClassDisplay;
})();
