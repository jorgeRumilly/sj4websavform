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
});
