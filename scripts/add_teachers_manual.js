(function () {
  const participationToggle = document.getElementById("participationToggle");
  const participationToggleLabel = participationToggle.parentNode;

  function toggleClassDisplay() {
    const isChecked = participationToggle.checked;

    if (isChecked) {
      const classLabel = document.createElement("label");
      classLabel.textContent = "Klassen:";
      classLabel.id = "class-label";

      const classSelect = document.createElement("select");
      classSelect.id = "class-select";
      const option1 = document.createElement("option");
      option1.value = "class1";
      option1.textContent = "Klasse 1";
      const option2 = document.createElement("option");
      option2.value = "class2";
      option2.textContent = "Klasse 2";

      classSelect.appendChild(option1);
      classSelect.appendChild(option2);

      participationToggleLabel.parentNode.insertBefore(
        classLabel,
        participationToggleLabel.nextSibling
      );
      participationToggleLabel.parentNode.insertBefore(
        classSelect,
        classLabel.nextSibling
      );
    } else {
      document.getElementById("class-select")?.remove();
      document.getElementById("class-label")?.remove();
    }
  }

  participationToggle.addEventListener("change", toggleClassDisplay);

  window.checkParticipationToggle = toggleClassDisplay;
})();
