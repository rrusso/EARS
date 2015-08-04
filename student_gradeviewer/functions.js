function tag_inputs() {
    var inputs = concat(document.getElementsByClassName('field_input'),
                        document.getElementsByClassName('other'));

    for(var i=0; i< inputs.length; i++) {
        var original = function(elem) { return document.getElementById(elem.name).value; };

        inputs[i].onfocus = function() {
            if(this.value == original(this)) {
                this.value = '';
            } else {
                this.select();
            }
            this.className = "other";
        };
        
        inputs[i].onblur = function() {            
            if(this.value == '') {
                this.value = original(this);
                this.className = "field_input";
            }
        };
    }
}

// HTMLCollection does not support standard js array functions
function concat() {
    var newarr = [];

    for(var j=0; j< arguments.length; j++) {
        for(var i = 0; i < arguments[j].length; i++) {
            newarr.push(arguments[j][i]);
        }
    }    

    return newarr;
}
