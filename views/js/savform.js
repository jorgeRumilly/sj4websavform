document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="nature"]');
    const otherContainer = document.getElementById('nature_other_container');

    if (radios && otherContainer) {
        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (this.value === 'autre') {
                    otherContainer.style.display = 'block';
                } else {
                    otherContainer.style.display = 'none';
                }
            });
        });
    }

    const fileInput = document.getElementById('fileUpload');

    fileInput.addEventListener('change', function () {
        if (this.files.length > 5) {
            alert('Vous ne pouvez uploader que 5 fichiers maximum.');
            this.value = ''; // reset input
        }
    });

    // Validation avant soumission (au cas où)
    const form = document.getElementById('savForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (fileInput.files.length === 0) {
                e.preventDefault();
                alert('Veuillez ajouter au moins une image.');
            }
        });
    }
});
