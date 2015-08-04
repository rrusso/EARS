
function tagInput(field, store) {
    // Older versions of YUI like the one we have
    // will need this for autocomplete datastore
    var oDS = new YAHOO.widget.DS_JSArray(store);
    oDS.responseSchema = {fields : [field]};

    var ac = new YAHOO.widget.AutoComplete(field + '_AC', 
                                           field + '_container', oDS);

    // No animations for me please
    ac.prehighlightClassName = "yui-ac-prehighlight";
    ac.animSpeed = 0;
    ac.useShadow = true;

    return {
        oDS : oDS,
        ac : ac
    };
}

function tagInputs() {
    tagInput('year', years);
    tagInput('college', colleges);
    tagInput('major', majors);
}

