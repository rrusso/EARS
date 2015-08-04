
function registerPreSubmit(initial_id) {
    initial_select = document.getElementById(initial_id);
}

function validate() {
    var error = document.getElementById('error_message');
    error.innerHTML = '';

    if (initial_select.length > 0) {
        error.innerHTML = 'There are still unmoved sections.'; 
        return false;
    }

    if (!validate_buckets(0)) {
        error.innerHTML = 'Not every course has a section.';
        return false;
    }

    if (!validate_label()) {
        error.innerHTML = "Two course shells share the same name";
        return false;
    }

    return true;
}

function validate_buckets(min) {
    var radios = document.getElementsByTagName('INPUT');

    for (var i=0; i < radios.length; i++) {
        var radio = radios[i];
        if (radio.type != 'radio') {
            continue;
        }
            
        var bucket = document.getElementById(radio.value);
        if (bucket.options.length == min) {
            return false;
        }

        for (var j=bucket.options.length -1;j>=0; j--) {
            bucket.options[j].selected=true;
        }
    }

    var avail = document.getElementById('available_sections');
    for (var i =0; i< avail.length; i++) {
        avail[i].selected=true;
    }
    
    return true;    
}

function crosslist_validate() {
    var error = document.getElementById('error_message');
    if (error) {
        error.innerHTML = '';
    }
    
    if (!validate_buckets(1)) {
        error.innerHTML = 'Not every course has two sections.';
        return false;
    }

    if (!validate_label()) {
        error.innerHTML = 'Two course shells share the same name.';
        return false;
    }

    return true;
}

function crosslist_initial_validate() {

    var error = document.getElementById('error_message');
    
    if (error.innerHTML) {
        error.innerHTML = '';
    }

    var arr = grab_selection();

    if (arr.length < 2) {
        error.innerHTML = 'Please select at least two courses.';
        return false;
    }

    if (!validate_selection(arr)) {
        error.innerHTML = 'Please select courses from the same semesters.';
        return false;
    }

    return true;
}

function grab_selection() {
    var inputs = document.getElementsByTagName('INPUT');

    var selected = Array();
    for (var i=0; i<inputs.length ; i++) {
        var checkbox = inputs[i];
        if (checkbox.type != 'checkbox') {
            continue;
        }
        
        if (!checkbox.checked) {
            continue;
        }
        selected.push(checkbox);
    }

    return selected;
}

function validate_selection(selected) {
    while (selected.length > 1) {
        select = selected.pop()
        for (var i=0; i<selected.length; i++) {
            first = select.name.split('_')[0];
            second = selected[i].name.split('_')[0];
            if (first != second) {
                return false;
            }
        }
    }

    return true;    
}

function validate_label() {
    var inputs = document.getElementsByTagName('INPUT');
    
    var labels = Array();

    for (var i=0; i < inputs.length; i++) {
        var label = inputs[i];
        if (label.type != 'text') {
            continue;
        }

        labels.push(label);
    }

    while (labels.length > 1) {
        label = labels.pop();
        for (var i=0; i< labels.length; i++) {
            if (label.value == labels[i].value) {
                return false;
            }
        }
    }

    return true;
}

function findRadio() {
    var radios = document.getElementsByTagName('INPUT');
    
    var error = document.getElementById('error_message');
    error.innerHTML = '';

    for (var i=0; i < radios.length; i++) {
        var radio = radios[i];
        if (radio.type != 'radio') {
            continue;
        }
        
        if (!radio.checked) {
            continue;
        }

        var bucket = document.getElementById(radio.value);
        return bucket;
    }

    error.innerHTML = 'Please select a course.';

    return false;
}

function movement(from, to) {
    for (var x=from.length-1; x>=0; x--) {
        if (from.options[x].selected == true) {
            to.appendChild(from.options[x]);
        }
    }
}

function moveSectionTo() {
    var bucket = findRadio();

    if (bucket) {
        movement(initial_select, bucket);
    }
}

function moveSectionFrom() {
    var bucket = findRadio();    

    if (bucket) {
        movement(bucket, initial_select);
    }
}

function toggleUnwanted(courseid, select) {
    var inputs = document.getElementsByTagName('INPUT');

    for (var i =0; i < inputs.length; i++) {
        var elem = inputs[i];
        if (elem.type != 'checkbox') {
            continue;
        }
       

        if (elem.name.search('_' + courseid) == -1) {
            continue;
        } 
        elem.checked = select;
    }
}

function toggleBucketName(num) {
    input = document.getElementById('bucket_' + num + '_name');
    label = document.getElementById('bucket_' + num + '_label');
    
    // Input not visible; let's make it so
    if (input.style.display == 'none') {
        input.style.display = 'block';
    } else {
        input.style.display = 'none';
    }
}

function nameChanger(num) {
    input = document.getElementById('bucket_' + num + '_name');
    label = document.getElementById('bucket_' + num + '_label');

    label.innerHTML = input.value;
}
