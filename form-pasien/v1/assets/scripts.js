
/* Kalau butuh required */
document.getElementById('medicalForm').addEventListener('submit', function (event) {
    let isValid = true;
    const requiredFields = document.querySelectorAll('input[required]');

    requiredFields.forEach(field => {
        if (!field.value) {
            isValid = false;
            field.style.borderColor = 'red';
        } else {
            field.style.borderColor = '';
        }
    });

    if (!isValid) {
        event.preventDefault();
        alert('Please fill out all required fields.');
    }

    /* Untuk form kebiasaan hidup agar harus milih salah satu */
    // Step 1: Select all checkboxes in the form
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');

    // Step 2: Filter checkboxes by name containing 'fisik_kebiasaanhidup'
    const relevantCheckboxes = Array.from(checkboxes).filter(cb =>
        cb.name && cb.name.includes('fisik_kebiasaanhidup')
    );

    // Step 3: Group checkboxes by their data-group attribute
    const groups = {};
    relevantCheckboxes.forEach(cb => {
        const group = cb.getAttribute('data-group');
        if (!groups[group]) {
            groups[group] = [];
        }
        groups[group].push(cb);
    });

    // Step 4: Check each group for at least one selected checkbox
    for (const groupName in groups) {
        const groupCheckboxes = groups[groupName];
        let isChecked = groupCheckboxes.some(cb => cb.checked);
        if (!isChecked) {
            alert(`Please select at least one option in the "${groupName}" group.`);
            event.preventDefault();
            return;
        }
    }

    // If all groups have at least one checkbox selected, allow form submission
    // (No action needed; the form will submit normally)
});

/* Checkbox but like radio. Untuk form kebiasaan hidup */
document.addEventListener('DOMContentLoaded', function () {
    var checkboxes = document.querySelectorAll("input[type='checkbox'][data-group]");
    var groups = {};

    // Group checkboxes by their data-group
    checkboxes.forEach(function (checkbox) {
        var group = checkbox.getAttribute('data-group');
        if (!groups[group]) {
            groups[group] = {
                checkboxes: [],
                textInputs: []
            };
        }
        groups[group].checkboxes.push(checkbox);

        // Find the corresponding text input
        var textInputName = checkbox.name.replace('_chx', '_value');
        var textInput = document.querySelector("input[name='" + textInputName + "']");
        if (textInput) {
            groups[group].textInputs.push({
                checkbox: checkbox,
                textInput: textInput
            });
        }
    });

    // Add change event listener to each checkbox
    Object.keys(groups).forEach(function (group) {
        groups[group].checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                // Uncheck other checkboxes in the same group
                groups[group].checkboxes.forEach(function (cb) {
                    if (cb !== checkbox) {
                        cb.checked = false;
                    }
                });

                // Disable all text inputs in the group
                groups[group].textInputs.forEach(function (pair) {
                    pair.textInput.disabled = true;
                    pair.textInput.value = '';
                });

                // If the current checkbox is checked, enable its text input
                if (checkbox.checked) {
                    var correspondingTextInput = groups[group].textInputs.find(function (pair) {
                        return pair.checkbox === checkbox;
                    });
                    if (correspondingTextInput) {
                        correspondingTextInput.textInput.disabled = false;
                    }
                }
            });
        });
    });
});

// Untuk semua checkbox yang punya input text. Jika checkbox di check, maka input text nya bisa di isi
document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function () {
        const textInput = this.nextElementSibling;
        if (textInput && textInput.type === 'text') {
            if (this.checked) {
                textInput.disabled = false;
            } else {
                textInput.disabled = true;
                textInput.value = '';
            }
        }
    });

    // Trigger change event on page load to set initial state
    checkbox.dispatchEvent(new Event('change'));
});

