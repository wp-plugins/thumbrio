window.onload = function () {
    required_fields = function (bool) {
        input_fields = document.querySelectorAll('input[type="text"]');
        if (bool) {
            for (var i=0; i < 4; i++) {
                input_fields[i].setAttribute('required', 'required');
            } 
            document.getElementsByName('amazon-path')[0].removeAttribute('required');
        } else {
            for (var i=0; i < 4; i++) {
                input_fields[i].removeAttribute('required');
            }
        }
    } ;
    clickLocal = function () {
        document.getElementById('th-custom-origin').style.display='None'; 
        document.getElementById('th-amazon-options').style.display='None';
        required_fields(false);
    };
    clickAmazon = function () {
        var d=document.getElementById('th-amazon-options');
        d.classList.remove('th-highlight');
        d.style.display='block';
        d.classList.add('th-highlight');
        document.getElementById('th-custom-origin').style.display='None';
        required_fields(true);
    };
    clickCustom = function () {
        var d=document.getElementById('th-custom-origin');
        d.classList.remove('th-highlight');
        d.style.display='block';
        d.classList.add('th-highlight'); 
        document.getElementById('th-amazon-options').style.display='None'
        required_fields(false);
    };
    document.querySelector('input[type="radio"][checked]').click();
} 